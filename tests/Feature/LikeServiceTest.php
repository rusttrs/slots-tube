<?php

namespace Tests\Feature;

use App\Models\Like;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use App\Services\LikeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LikeServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function makePost(): Post
    {
        return Post::query()->create([
            'type' => 'guide',
            'slug' => 'likes-test',
            'title' => ['en' => 'Likes test'],
            'is_published' => true,
        ]);
    }

    public function test_toggle_keeps_counter_in_sync(): void
    {
        $post = $this->makePost();
        [$a, $b] = User::factory()->count(2)->create();
        $likes = app(LikeService::class);

        $this->assertSame(['liked' => true, 'count' => 1], $likes->toggle($a, $post));
        $this->assertSame(['liked' => true, 'count' => 2], $likes->toggle($b, $post));
        $this->assertSame(['liked' => false, 'count' => 1], $likes->toggle($a, $post));

        $this->assertSame(1, (int) $post->fresh()->likes_count);
        $this->assertDatabaseHas('likes', ['likeable_type' => 'post', 'likeable_id' => $post->id, 'user_id' => $b->id]);
    }

    public function test_repeated_like_does_not_double_count(): void
    {
        $post = $this->makePost();
        $user = User::factory()->create();
        $likes = app(LikeService::class);

        $likes->like($user, $post);
        $likes->like($user, $post);

        $this->assertSame(1, (int) $post->fresh()->likes_count);
        $this->assertSame(1, Like::query()->count());
    }

    public function test_likes_of_post_and_comment_stay_separate(): void
    {
        $post = $this->makePost();
        $user = User::factory()->create();
        $comment = PostComment::query()->create(['post_id' => $post->id, 'user_id' => $user->id, 'body' => 'hi']);
        $likes = app(LikeService::class);

        $likes->like($user, $comment);

        $this->assertSame(0, (int) $post->fresh()->likes_count);
        $this->assertSame(1, (int) $comment->fresh()->likes_count);
        $this->assertSame('Комментарий к статье', Like::query()->first()->typeLabel());
        $this->assertTrue(PostComment::query()->withLikedBy($user->id)->first()->liked_by_me);
    }

    public function test_deleting_user_decrements_counters(): void
    {
        $post = $this->makePost();
        $user = User::factory()->create();
        app(LikeService::class)->like($user, $post);

        $user->delete();

        $this->assertSame(0, (int) $post->fresh()->likes_count);
        $this->assertSame(0, Like::query()->count());
    }

    public function test_sync_removes_orphans_and_fixes_counters(): void
    {
        $post = $this->makePost();
        $user = User::factory()->create();
        app(LikeService::class)->like($user, $post);
        DB::table('posts')->where('id', $post->id)->update(['likes_count' => 7]);
        Like::query()->insert(['user_id' => $user->id, 'likeable_type' => 'post', 'likeable_id' => 999999, 'created_at' => now()]);

        $result = app(LikeService::class)->sync();

        $this->assertSame(1, $result['orphans']);
        $this->assertSame(1, (int) $post->fresh()->likes_count);
    }

    public function test_like_endpoint_requires_login_and_toggles(): void
    {
        $post = $this->makePost();
        $user = User::factory()->create(['onboarding_completed_at' => now()]);

        $this->postJson(route('posts.like', $post))->assertUnauthorized();

        $this->actingAs($user)->postJson(route('posts.like', $post))
            ->assertOk()
            ->assertJson(['liked' => true, 'count' => 1]);

        $this->actingAs($user)->postJson(route('posts.like', $post))
            ->assertOk()
            ->assertJson(['liked' => false, 'count' => 0]);
    }
}
