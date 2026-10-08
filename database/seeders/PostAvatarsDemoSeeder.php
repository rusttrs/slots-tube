<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Post;
use App\Models\Like;
use App\Models\PostComment;
use App\Models\User;
use App\Services\LikeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PostAvatarsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $avatars = [
            'assets/images/avatars/user-01.jpg',
            'assets/images/avatars/user-02.jpg',
            'assets/images/avatars/user-03.jpg',
            'assets/images/avatars/user-04.jpg',
            'assets/images/avatars/user-05.jpg',
            'assets/images/avatars/user-06.jpg',
            'assets/images/avatars/user-07.jpg',
            'assets/images/avatars/user-08.jpg',
        ];

        $community = [
            ['nickname' => 'kuminaboy', 'name' => 'kuminaboy', 'email' => 'kuminaboy@slots.tube.demo', 'avatar' => 0],
            ['nickname' => 'ukiteng', 'name' => 'ukiteng', 'email' => 'ukiteng@slots.tube.demo', 'avatar' => 2],
            ['nickname' => 'slotfox', 'name' => 'slotfox', 'email' => 'slotfox@slots.tube.demo', 'avatar' => 3],
            ['nickname' => 'spinqueen', 'name' => 'spinqueen', 'email' => 'spinqueen@slots.tube.demo', 'avatar' => 4],
            ['nickname' => 'maxwinmike', 'name' => 'maxwinmike', 'email' => 'maxwinmike@slots.tube.demo', 'avatar' => 5],
            ['nickname' => 'reelfast', 'name' => 'reelfast', 'email' => 'reelfast@slots.tube.demo', 'avatar' => 6],
        ];

        $users = [];
        foreach ($community as $row) {
            $user = User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'nickname' => $row['nickname'],
                    'avatar_path' => $avatars[$row['avatar']],
                    'password' => Hash::make(Str::password(24)),
                    'email_verified_at' => now(),
                    'onboarding_completed_at' => now(),
                ]
            );
            $users[$row['nickname']] = $user;
        }

        // Fill missing avatars for everyone else (fans, authors-as-users, smoke accounts).
        $index = 0;
        User::query()
            ->where(function ($q) {
                $q->whereNull('avatar_path')->orWhere('avatar_path', '');
            })
            ->orderBy('id')
            ->each(function (User $user) use ($avatars, &$index): void {
                $user->avatar_path = $avatars[$index % count($avatars)];
                if (blank($user->nickname) && filled($user->name) && ! str_contains((string) $user->name, '+')) {
                    $user->nickname = Str::slug($user->name, '');
                }
                $user->save();
                $index++;
            });

        Author::query()->where('slug', 'marcus-hale')->update([
            'avatar_path' => $avatars[0],
        ]);
        Author::query()->where('slug', 'elena-voss')->update([
            'avatar_path' => $avatars[2],
        ]);

        $post = Post::query()->where('slug', 'how-to-play-roulette-daily-bonus')->first();
        if (! $post) {
            return;
        }

        $likes = app(LikeService::class);

        Like::query()
            ->where('likeable_type', 'post_comment')
            ->whereIn('likeable_id', PostComment::withTrashed()->where('post_id', $post->id)->select('id'))
            ->delete();
        PostComment::withTrashed()->where('post_id', $post->id)->forceDelete();
        $post->likes()->delete();
        $post->forceFill(['likes_count' => 0])->saveQuietly();

        // Latest likes surface first in "liked by …" — put ukiteng last (like Figma).
        $likers = [
            $users['kuminaboy'],
            $users['slotfox'],
            $users['spinqueen'],
            $users['maxwinmike'],
            $users['reelfast'],
            $users['ukiteng'],
        ];
        foreach ($likers as $liker) {
            $likes->like($liker, $post);
        }

        $root = PostComment::query()->create([
            'post_id' => $post->id,
            'user_id' => $users['kuminaboy']->id,
            'parent_id' => null,
            'body' => 'wow amazing 300FS won today',
            'is_published' => true,
            'created_at' => now()->subDays(7),
            'updated_at' => now()->subDays(7),
        ]);

        $reply = PostComment::query()->create([
            'post_id' => $post->id,
            'user_id' => $users['ukiteng']->id,
            'parent_id' => $root->id,
            'body' => 'great man!',
            'is_published' => true,
            'created_at' => now()->subDays(7)->addMinutes(20),
            'updated_at' => now()->subDays(7)->addMinutes(20),
        ]);

        $second = PostComment::query()->create([
            'post_id' => $post->id,
            'user_id' => $users['kuminaboy']->id,
            'parent_id' => null,
            'body' => 'Daily roulette tip worked — claimed my spins this morning. Thanks for the guide!',
            'is_published' => true,
            'created_at' => now()->subDays(6),
            'updated_at' => now()->subDays(6),
        ]);

        foreach ([$users['ukiteng'], $users['slotfox'], $users['spinqueen']] as $liker) {
            $likes->like($liker, $root);
        }
        $likes->like($users['kuminaboy'], $reply);
        $likes->like($users['reelfast'], $second);
        $likes->like($users['maxwinmike'], $second);
    }
}
