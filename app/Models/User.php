<?php

namespace App\Models;

use App\Services\LikeService;
use App\Support\MediaMirror;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'google_id',
    'nickname',
    'avatar_path',
    'newsletter_opt_in',
    'age_confirmed',
    'onboarding_completed_at',
    'deactivated_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saved(fn (User $user) => MediaMirror::mirrorChangedPath($user, 'avatar_path'));
        static::deleting(fn (User $user) => app(LikeService::class)->forgetUser($user));
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isActive();
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    public function deactivate(): void
    {
        $this->forceFill(['deactivated_at' => now()])->save();
    }

    public function reactivate(): void
    {
        $this->forceFill(['deactivated_at' => null])->save();
    }

    public function needsOnboarding(): bool
    {
        return $this->onboarding_completed_at === null;
    }

    public function displayName(): string
    {
        return $this->nickname ?: $this->name ?: strstr($this->email, '@', true) ?: 'User';
    }

    public function avatarUrl(): string
    {
        if (! filled($this->avatar_path)) {
            return asset('assets/images/header/profile.svg');
        }

        return media_url((string) $this->avatar_path);
    }

    public function authProvider(): string
    {
        if ($this->google_id) {
            return 'Google';
        }

        return 'Email';
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'newsletter_opt_in' => 'boolean',
            'age_confirmed' => 'boolean',
            'onboarding_completed_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }
}
