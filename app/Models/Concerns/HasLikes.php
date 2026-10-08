<?php

namespace App\Models\Concerns;

use App\Models\Like;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Likes live in the shared `likes` table; `likes_count` on the model is the cached total
 * and must only be changed through App\Services\LikeService.
 */
trait HasLikes
{
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function scopeWithLikedBy(Builder $query, ?int $userId): Builder
    {
        return $query->withExists([
            'likes as liked_by_me' => fn (Builder $likes) => $likes->where('user_id', $userId ?? 0),
        ]);
    }
}
