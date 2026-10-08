<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarStorageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_profile_avatar_is_stored_in_r2_and_mirrored_locally(): void
    {
        Storage::fake('r2');
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile', [
                'nickname' => 'avatar-tester',
                'avatar' => UploadedFile::fake()->image('me.png', 64, 64),
            ])
            ->assertRedirect();

        $path = (string) $user->fresh()->avatar_path;
        $this->assertStringStartsWith('avatars/', $path);
        Storage::disk('r2')->assertExists($path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame(Storage::disk('public')->url($path), $user->fresh()->avatarUrl());
    }

    public function test_avatar_set_outside_the_profile_form_is_mirrored_on_save(): void
    {
        Storage::fake('r2');
        Storage::fake('public');
        Storage::disk('r2')->put('avatars/admin-upload.png', 'png-bytes');
        $user = User::factory()->create();

        $user->update(['avatar_path' => 'avatars/admin-upload.png']);

        Storage::disk('public')->assertExists('avatars/admin-upload.png');
    }

    public function test_media_push_uploads_only_missing_local_files(): void
    {
        Storage::fake('r2');
        Storage::fake('public');
        Storage::disk('public')->put('avatars/old.jpg', 'old-bytes');
        Storage::disk('public')->put('avatars/.gitignore', '*');
        Storage::disk('public')->put('slots/cover.png', 'cover');
        Storage::disk('r2')->put('avatars/synced.jpg', 'r2-bytes');
        Storage::disk('public')->put('avatars/synced.jpg', 'local-bytes');

        $this->artisan('media:push', ['directory' => 'avatars'])
            ->expectsOutputToContain('Uploaded: 1, already in R2: 1, failed: 0.')
            ->assertSuccessful();

        $this->assertSame('old-bytes', Storage::disk('r2')->get('avatars/old.jpg'));
        $this->assertSame('r2-bytes', Storage::disk('r2')->get('avatars/synced.jpg'));
        Storage::disk('r2')->assertMissing('avatars/.gitignore');
        Storage::disk('r2')->assertMissing('slots/cover.png');
    }
}
