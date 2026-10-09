<?php

namespace Tests\Feature;

use App\Mail\EmailChangeMail;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\Slot;
use App\Models\SlotReview;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use DatabaseTransactions;

    private function onboarded(array $attributes = []): User
    {
        return User::factory()->create(['onboarding_completed_at' => now(), ...$attributes]);
    }

    public function test_slug_follows_nickname_and_stays_unique(): void
    {
        $first = $this->onboarded(['nickname' => 'Slug Tester']);
        $second = $this->onboarded(['nickname' => 'slug tester']);

        $this->assertSame('slug-tester', $first->slug);
        $this->assertSame('slug-tester-2', $second->slug);
        $this->assertSame(url('/users/slug-tester').'/', $first->publicUrl('en'));
        $this->assertSame(url('/de/users/slug-tester').'/', $first->publicUrl('de'));

        $first->update(['nickname' => 'Slug Tester Pro']);
        $this->assertSame('slug-tester-pro', $first->fresh()->slug);
    }

    public function test_public_profile_lists_published_reviews_and_comments(): void
    {
        $user = $this->onboarded(['nickname' => 'Review Writer']);
        $slot = Slot::query()->create(['title' => ['en' => 'Profile Test Slot'], 'slug' => 'profile-test-slot', 'is_published' => true]);
        $hiddenSlot = Slot::query()->create(['title' => ['en' => 'Hidden Slot'], 'slug' => 'profile-hidden-slot', 'is_published' => false]);
        $post = Post::query()->create(['type' => 'guide', 'slug' => 'profile-test-post', 'title' => ['en' => 'Profile Test Post'], 'is_published' => true]);

        SlotReview::query()->create(['slot_id' => $slot->id, 'user_id' => $user->id, 'play_mode' => 'real', 'rating' => 4, 'body' => 'Visible review text', 'is_published' => true]);
        SlotReview::query()->create(['slot_id' => $hiddenSlot->id, 'user_id' => $user->id, 'play_mode' => 'demo', 'rating' => 2, 'body' => 'Review of a hidden slot', 'is_published' => true]);
        $comment = PostComment::query()->create(['post_id' => $post->id, 'user_id' => $user->id, 'body' => 'Visible comment text', 'is_published' => true]);
        PostComment::query()->create(['post_id' => $post->id, 'user_id' => $user->id, 'body' => 'Moderated comment', 'is_published' => false]);

        $this->get('/users/review-writer/')
            ->assertOk()
            ->assertSee('Review Writer')
            ->assertSee('Profile Test Slot')
            ->assertSee('Visible review text')
            ->assertSee('Visible comment text')
            ->assertSee('/content/profile-test-post/#comment-'.$comment->id, false)
            ->assertDontSee('Review of a hidden slot')
            ->assertDontSee('Moderated comment')
            ->assertDontSee($user->email);

        $this->get('/fr/users/review-writer/')->assertOk();
    }

    public function test_deactivated_and_unknown_users_have_no_public_profile(): void
    {
        $user = $this->onboarded(['nickname' => 'Gone User']);
        $user->deactivate();

        $this->get('/users/gone-user/')->assertNotFound();
        $this->get('/users/nobody-here/')->assertNotFound();
        $this->assertFalse($user->fresh()->hasPublicProfile());
    }

    public function test_comment_author_links_to_profile_and_own_comment_to_account_page(): void
    {
        $author = $this->onboarded(['nickname' => 'Comment Author']);
        $viewer = $this->onboarded(['nickname' => 'Comment Viewer']);
        $post = Post::query()->create(['type' => 'guide', 'slug' => 'profile-links-post', 'title' => ['en' => 'Links'], 'is_published' => true]);
        PostComment::query()->create(['post_id' => $post->id, 'user_id' => $author->id, 'body' => 'Hello', 'is_published' => true]);

        $this->get('/content/profile-links-post/')
            ->assertOk()
            ->assertSee('href="'.url('/users/comment-author').'/"', false);

        $this->actingAs($author)->get('/content/profile-links-post/')
            ->assertSee('href="'.url('/profile').'/"', false);

        $this->actingAs($viewer)->get('/de/content/profile-links-post/')
            ->assertSee('href="'.url('/de/users/comment-author').'/"', false);
    }

    public function test_nickname_can_be_changed_once_per_180_days(): void
    {
        $user = $this->onboarded(['nickname' => 'First Name']);

        $this->actingAs($user)->post('/profile/username', ['nickname' => 'Second Name'])
            ->assertRedirect(url('/profile').'/')
            ->assertSessionHasNoErrors();
        $this->assertSame('Second Name', $user->fresh()->nickname);
        $this->assertNotNull($user->fresh()->nickname_changed_at);

        $this->actingAs($user->fresh())->post('/profile/username', ['nickname' => 'Third Name'])
            ->assertSessionHasErrors('nickname', null, 'username');
        $this->assertSame('Second Name', $user->fresh()->nickname);

        $this->travel(User::NICKNAME_CHANGE_DAYS + 1)->days();
        $this->actingAs($user->fresh())->post('/profile/username', ['nickname' => 'Third Name'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Third Name', $user->fresh()->nickname);
    }

    public function test_onboarding_nickname_does_not_start_the_cooldown(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => null]);

        $this->actingAs($user)->post('/profile', ['nickname' => '@newbie'])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('newbie', $user->nickname);
        $this->assertNull($user->nickname_changed_at);
        $this->assertNotNull($user->onboarding_completed_at);
        $this->assertTrue($user->canChangeNickname());
    }

    public function test_avatar_modal_accepts_only_known_presets(): void
    {
        $user = $this->onboarded(['nickname' => 'Preset User']);
        $preset = User::AVATAR_PRESETS[2];

        $this->actingAs($user)->post('/profile', ['nickname' => 'Preset User', 'avatar_preset' => $preset])
            ->assertSessionHasNoErrors();
        $this->assertSame($preset, $user->fresh()->avatar_path);
        $this->assertSame(asset($preset), $user->fresh()->avatarUrl());

        $this->actingAs($user)->post('/profile', ['nickname' => 'Preset User', 'avatar_preset' => 'https://evil.example/x.png'])
            ->assertSessionHasErrors('avatar_preset', null, 'avatar');
        $this->assertSame($preset, $user->fresh()->avatar_path);
    }

    public function test_email_change_requires_confirmation_link(): void
    {
        Mail::fake();
        $user = $this->onboarded(['email' => 'old-address@example.com']);
        $this->onboarded(['email' => 'taken@example.com']);

        $this->actingAs($user)->post('/profile/email', ['email' => 'taken@example.com', 'email_confirmation' => 'taken@example.com'])
            ->assertSessionHasErrors('email', null, 'email');
        $this->actingAs($user)->post('/profile/email', ['email' => 'new@example.com', 'email_confirmation' => 'other@example.com'])
            ->assertSessionHasErrors('email', null, 'email');

        $this->actingAs($user)->post('/profile/email', ['email' => 'New-Address@example.com', 'email_confirmation' => 'new-address@example.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('email_change_sent', 'new-address@example.com');
        $this->assertSame('old-address@example.com', $user->fresh()->email);

        $url = null;
        Mail::assertSent(EmailChangeMail::class, function (EmailChangeMail $mail) use (&$url) {
            $url = $mail->url;

            return $mail->hasTo('new-address@example.com');
        });

        $this->actingAs($user)->get($url)->assertRedirect(url('/profile').'/');
        $this->assertSame('new-address@example.com', $user->fresh()->email);

        $this->actingAs($user->fresh())->get($url)->assertRedirect(url('/profile').'/');
    }

    public function test_stale_or_tampered_email_links_are_rejected(): void
    {
        $user = $this->onboarded(['email' => 'stale-old@example.com']);

        $stale = URL::temporarySignedRoute('profile.email.confirm', now()->addHour(), [
            'user' => $user->id, 'email' => 'first-choice@example.com', 'from' => 'not-the-current-one', 'lang' => 'en',
        ]);
        $this->get($stale)->assertRedirect(url('/'));
        $this->assertSame('stale-old@example.com', $user->fresh()->email);
        $this->assertGuest();

        $this->get(str_replace('first-choice', 'attacker', $stale))->assertForbidden();
    }
}
