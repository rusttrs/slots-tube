<?php

namespace App\Models;

use App\Models\Concerns\Trashable;
use App\Support\MediaMirror;
use App\Support\PostBody;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Author extends Model
{
    use HasTranslations;
    use Trashable;

    public const LATEST_SLOTS = 12;

    public const LATEST_POSTS = 6;

    /**
     * Groups on the team page (/authors/), in display order.
     *
     * @var array<string, string>
     */
    public const TEAM_GROUPS = [
        'editors' => 'Our Editors — редакция',
        'team' => 'Other Team Members — остальная команда',
    ];

    /**
     * Profile networks for schema.org sameAs (not rendered on the page).
     *
     * @var array<string, string>
     */
    public const SOCIAL_NETWORKS = [
        'linkedin' => 'LinkedIn',
        'x' => 'X (Twitter)',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
        'twitch' => 'Twitch',
        'kick' => 'Kick',
        'telegram' => 'Telegram',
        'website' => 'Личный сайт',
        'other' => 'Другое',
    ];

    public array $translatable = [
        'name', 'page_title', 'position', 'bio', 'traits',
        'favorites_title', 'latest_slots_title', 'latest_posts_title',
        'meta_title', 'meta_description',
    ];

    protected $fillable = [
        'name', 'page_title', 'slug', 'role', 'position', 'avatar_path', 'bio', 'traits', 'started_at', 'is_published', 'sort_order', 'team_group',
        'favorites_title', 'favorite_slot_ids', 'red_flag_slot_ids', 'top_streamers', 'favorite_post_ids',
        'show_latest_slots', 'latest_slots_title', 'latest_slot_ids',
        'show_latest_posts', 'latest_posts_title', 'latest_post_ids',
        'meta_title', 'meta_description', 'noindex', 'social_links',
    ];

    protected $attributes = [
        'team_group' => 'editors',
        'show_latest_slots' => true,
        'show_latest_posts' => true,
        'noindex' => false,
    ];

    protected static function booted(): void
    {
        static::saved(fn (Author $author) => MediaMirror::mirrorChangedPath($author, 'avatar_path'));
    }

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'page_title' => 'array',
            'position' => 'array',
            'bio' => 'array',
            'traits' => 'array',
            'favorites_title' => 'array',
            'latest_slots_title' => 'array',
            'latest_posts_title' => 'array',
            'meta_title' => 'array',
            'meta_description' => 'array',
            'favorite_slot_ids' => 'array',
            'red_flag_slot_ids' => 'array',
            'top_streamers' => 'array',
            'favorite_post_ids' => 'array',
            'latest_slot_ids' => 'array',
            'latest_post_ids' => 'array',
            'social_links' => 'array',
            'show_latest_slots' => 'boolean',
            'show_latest_posts' => 'boolean',
            'noindex' => 'boolean',
            'is_published' => 'boolean',
            'started_at' => 'date',
        ];
    }

    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Published authors split by team group, each group ordered by sort_order then name.
     *
     * @return array<string, Collection<int, Author>>
     */
    public static function teamGroups(): array
    {
        $authors = static::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return collect(array_keys(self::TEAM_GROUPS))
            ->mapWithKeys(fn (string $group): array => [
                $group => $authors->filter(fn (Author $author): bool => $author->teamGroup() === $group)->values(),
            ])
            ->all();
    }

    public function teamGroup(): string
    {
        return array_key_exists((string) $this->team_group, self::TEAM_GROUPS) ? $this->team_group : 'editors';
    }

    public function displayName(?string $locale = null): string
    {
        return (string) ($this->getTranslation('name', $locale ?: app()->getLocale())
            ?: $this->getTranslation('name', 'en')
            ?: $this->slug);
    }

    public function pageTitle(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return $this->translated('page_title', $locale)
            ?: __('author.default_title', ['name' => $this->displayName($locale)], $locale);
    }

    public function metaTitle(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return $this->translated('meta_title', $locale) ?: $this->pageTitle($locale).' | slots.tube';
    }

    public function metaDescription(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $description = $this->translated('meta_description', $locale);
        if ($description !== '') {
            return $description;
        }

        $bio = trim(preg_replace('/\s+/u', ' ', strip_tags($this->renderedBio($locale))) ?? '');

        return $bio !== ''
            ? Str::limit($bio, 155)
            : __('author.default_description', ['name' => $this->displayName($locale)], $locale);
    }

    /**
     * Job title shown under the name and in the slot editorial pledge.
     * Falls back to the legacy free-text `role`; "author" / "staff" there are not titles.
     */
    public function positionLabel(?string $locale = null): ?string
    {
        $position = $this->translated('position', $locale ?: app()->getLocale());
        if ($position !== '') {
            return $position;
        }

        $role = trim((string) $this->role);

        return $role === '' || in_array($role, ['author', 'staff'], true) ? null : $role;
    }

    public function favoritesTitle(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return $this->translated('favorites_title', $locale) ?: __('author.favorites_title', [], $locale);
    }

    public function latestSlotsTitle(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return $this->translated('latest_slots_title', $locale)
            ?: __('author.latest_slots', ['name' => $this->displayName($locale)], $locale);
    }

    public function latestPostsTitle(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return $this->translated('latest_posts_title', $locale)
            ?: __('author.latest_posts', ['name' => $this->displayName($locale)], $locale);
    }

    /**
     * @return list<string>
     */
    public function tags(?string $locale = null): array
    {
        $all = $this->getTranslations('traits');
        $tags = $all[$locale ?: app()->getLocale()] ?? null;
        if (! is_array($tags) || $tags === []) {
            $tags = $all['en'] ?? [];
        }

        return array_values(array_filter(array_map(fn ($tag) => trim((string) $tag), (array) $tags), fn (string $tag) => $tag !== ''));
    }

    public function renderedBio(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $bio = $this->getTranslation('bio', $locale, false);
        if (blank($bio)) {
            $bio = $this->getTranslation('bio', 'en', false);
        }

        return PostBody::render($bio);
    }

    /**
     * @return Collection<int, Slot>
     */
    public function favoriteSlots(): Collection
    {
        return self::pickedSlots($this->favorite_slot_ids);
    }

    /**
     * @return Collection<int, Slot>
     */
    public function redFlagSlots(): Collection
    {
        return self::pickedSlots($this->red_flag_slot_ids);
    }

    /**
     * @return Collection<int, Post>
     */
    public function favoritePosts(): Collection
    {
        return self::pickedPosts($this->favorite_post_ids);
    }

    /**
     * Hand-picked slots if set in the admin, otherwise the newest slots this author wrote.
     *
     * @return Collection<int, Slot>
     */
    public function latestSlots(): Collection
    {
        if (! $this->show_latest_slots) {
            return new Collection;
        }

        if (self::ids($this->latest_slot_ids) !== []) {
            return self::pickedSlots($this->latest_slot_ids)->take(self::LATEST_SLOTS)->values();
        }

        return Slot::query()
            ->published()
            ->where('author_id', $this->id)
            ->latest('created_at')
            ->limit(self::LATEST_SLOTS)
            ->get();
    }

    /**
     * Hand-picked publications if set in the admin, otherwise the newest ones by this author.
     *
     * @return Collection<int, Post>
     */
    public function latestPosts(): Collection
    {
        if (! $this->show_latest_posts) {
            return new Collection;
        }

        if (self::ids($this->latest_post_ids) !== []) {
            return self::pickedPosts($this->latest_post_ids)->take(self::LATEST_POSTS)->values();
        }

        return Post::query()
            ->where('is_published', true)
            ->where('author_id', $this->id)
            ->with('author')
            ->latest('created_at')
            ->limit(self::LATEST_POSTS)
            ->get();
    }

    /**
     * @return list<array{name: string, url: ?string}>
     */
    public function topStreamers(): array
    {
        return collect(is_array($this->top_streamers) ? $this->top_streamers : [])
            ->map(fn ($row): array => [
                'name' => trim((string) ($row['name'] ?? '')),
                'url' => filled($row['url'] ?? null) ? trim((string) $row['url']) : null,
            ])
            ->filter(fn (array $row): bool => $row['name'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function sameAsUrls(): array
    {
        return collect(is_array($this->social_links) ? $this->social_links : [])
            ->map(fn ($row): string => trim((string) ($row['url'] ?? '')))
            ->filter(fn (string $url): bool => preg_match('#^https?://#i', $url) === 1)
            ->unique()
            ->values()
            ->all();
    }

    public function avatarUrl(): ?string
    {
        return filled($this->avatar_path) ? media_url($this->avatar_path) : null;
    }

    public function publicUrl(?string $locale = null): string
    {
        return localized_url($locale, 'authors/'.$this->slug);
    }

    /**
     * @return Collection<int, Slot>
     */
    private static function pickedSlots(mixed $value): Collection
    {
        $ids = self::ids($value);
        if ($ids === []) {
            return new Collection;
        }

        return Slot::query()->published()->whereIn('id', $ids)->get()
            ->sortBy(fn (Slot $slot) => array_search($slot->id, $ids, true))
            ->values();
    }

    /**
     * @return Collection<int, Post>
     */
    private static function pickedPosts(mixed $value): Collection
    {
        $ids = self::ids($value);
        if ($ids === []) {
            return new Collection;
        }

        return Post::query()->where('is_published', true)->whereIn('id', $ids)->with('author')->get()
            ->sortBy(fn (Post $post) => array_search($post->id, $ids, true))
            ->values();
    }

    /**
     * @return list<int>
     */
    private static function ids(mixed $value): array
    {
        return array_values(array_unique(array_map('intval', array_filter(is_array($value) ? $value : []))));
    }

    private function translated(string $attribute, string $locale): string
    {
        $values = $this->getTranslations($attribute);

        return trim((string) ($values[$locale] ?? '')) ?: trim((string) ($values['en'] ?? ''));
    }
}
