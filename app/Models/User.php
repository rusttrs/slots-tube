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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

    public const NICKNAME_CHANGE_DAYS = 180;

    /** Built-in avatars offered by "Random Avatar"; stored in avatar_path as is. */
    public const AVATAR_PRESETS = [
        'assets/images/profile/avatar-sample.png',
        'assets/images/profile/avatar-r1.svg',
        'assets/images/profile/avatar-r2.svg',
        'assets/images/profile/avatar-r3.svg',
    ];

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if (blank($user->slug) || $user->isDirty(['nickname', 'name'])) {
                $user->slug = static::uniqueSlug($user->displayName(), $user->id);
            }
        });
        static::saved(fn (User $user) => MediaMirror::mirrorChangedPath($user, 'avatar_path'));
        static::deleting(fn (User $user) => app(LikeService::class)->forgetUser($user));
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin && $this->isActive();
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

    public function hasAvatar(): bool
    {
        return filled($this->avatar_path);
    }

    public function avatarUrl(): string
    {
        if (! $this->hasAvatar()) {
            return asset('assets/images/header/profile.svg');
        }

        return media_url((string) $this->avatar_path);
    }

    public function initials(): string
    {
        $words = preg_split('/[\s._\-@]+/u', trim($this->displayName()), -1, PREG_SPLIT_NO_EMPTY) ?: ['U'];
        $letters = count($words) > 1
            ? mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1)
            : mb_substr($words[0], 0, 2);

        return mb_strtoupper($letters);
    }

    public static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: 'user';
        $candidate = $slug;

        for ($i = 2; static::query()->where('slug', $candidate)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $candidate = $slug.'-'.$i;
        }

        return $candidate;
    }

    public function publicUrl(?string $locale = null): string
    {
        return localized_url($locale, 'users/'.$this->slug);
    }

    public function hasPublicProfile(): bool
    {
        return $this->isActive() && filled($this->slug);
    }

    /** Own reviews and comments lead to the account page, everybody else's — to the public profile. */
    public function profileUrl(): string
    {
        return (int) Auth::id() === (int) $this->id ? localized_url(null, 'profile') : $this->publicUrl();
    }

    public function nicknameChangeAvailableAt(): ?Carbon
    {
        if ($this->needsOnboarding() || $this->nickname_changed_at === null) {
            return null;
        }

        $available = $this->nickname_changed_at->copy()->addDays(self::NICKNAME_CHANGE_DAYS);

        return $available->isFuture() ? $available : null;
    }

    public function canChangeNickname(): bool
    {
        return $this->nicknameChangeAvailableAt() === null;
    }

    public function slotReviews(): HasMany
    {
        return $this->hasMany(SlotReview::class);
    }

    public function postComments(): HasMany
    {
        return $this->hasMany(PostComment::class);
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
            'is_admin' => 'boolean',
            'newsletter_opt_in' => 'boolean',
            'age_confirmed' => 'boolean',
            'onboarding_completed_at' => 'datetime',
            'nickname_changed_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }
}
