<?php

namespace App\Services;

use App\Models\Like;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LikeService
{
    /**
     * Toggle the user's like on a model. Safe against double clicks: the counter only moves
     * when a row was actually inserted or deleted.
     *
     * @return array{liked: bool, count: int}
     */
    public function toggle(User $user, Model $likeable): array
    {
        return DB::transaction(function () use ($user, $likeable): array {
            $key = $this->key($user, $likeable);

            if (Like::query()->where($key)->delete() > 0) {
                $this->adjust($likeable, -1);
                $liked = false;
            } else {
                if (Like::query()->insertOrIgnore($key + ['created_at' => now()]) > 0) {
                    $this->adjust($likeable, 1);
                }
                $liked = true;
            }

            return ['liked' => $liked, 'count' => $this->count($likeable)];
        });
    }

    public function like(User $user, Model $likeable): void
    {
        DB::transaction(function () use ($user, $likeable): void {
            if (Like::query()->insertOrIgnore($this->key($user, $likeable) + ['created_at' => now()]) > 0) {
                $this->adjust($likeable, 1);
            }
        });
    }

    public function remove(Like $like): void
    {
        DB::transaction(function () use ($like): void {
            if (Like::query()->whereKey($like->getKey())->delete() > 0) {
                $this->adjustRaw($like->likeable_type, (int) $like->likeable_id, -1);
            }
        });
    }

    /** Called before a user is deleted: the FK cascade would otherwise leave counters too high. */
    public function forgetUser(User $user): void
    {
        Like::query()->where('user_id', $user->id)->get()->each(fn (Like $like) => $this->remove($like));
    }

    /**
     * Drop likes whose target was hard-deleted and recalculate every cached counter.
     *
     * @return array{orphans: int, fixed: int}
     */
    public function sync(): array
    {
        $orphans = 0;
        $fixed = 0;

        foreach (Like::TYPES as $alias => $class) {
            $table = (new $class)->getTable();

            $orphans += Like::query()
                ->where('likeable_type', $alias)
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from($table)->whereColumn("{$table}.id", 'likes.likeable_id'))
                ->delete();

            $fixed += DB::update(
                "UPDATE {$table} SET likes_count = sub.total
                 FROM (
                     SELECT t.id, COUNT(l.id) AS total
                     FROM {$table} t
                     LEFT JOIN likes l ON l.likeable_type = ? AND l.likeable_id = t.id
                     GROUP BY t.id
                 ) sub
                 WHERE {$table}.id = sub.id AND {$table}.likes_count <> sub.total",
                [$alias],
            );
        }

        return ['orphans' => $orphans, 'fixed' => $fixed];
    }

    /** @return array{user_id: int, likeable_type: string, likeable_id: int} */
    private function key(User $user, Model $likeable): array
    {
        return [
            'user_id' => (int) $user->getKey(),
            'likeable_type' => $likeable->getMorphClass(),
            'likeable_id' => (int) $likeable->getKey(),
        ];
    }

    private function adjust(Model $likeable, int $delta): void
    {
        $this->adjustRaw($likeable->getMorphClass(), (int) $likeable->getKey(), $delta);
    }

    private function adjustRaw(string $alias, int $id, int $delta): void
    {
        $class = Like::TYPES[$alias] ?? null;
        if (! $class) {
            return;
        }

        $query = DB::table((new $class)->getTable())->where('id', $id);

        $delta > 0
            ? $query->increment('likes_count', $delta)
            : $query->where('likes_count', '>', 0)->decrement('likes_count', -$delta);
    }

    private function count(Model $likeable): int
    {
        return (int) DB::table($likeable->getTable())->where('id', $likeable->getKey())->value('likes_count');
    }
}
