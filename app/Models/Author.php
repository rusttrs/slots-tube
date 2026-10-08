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

    public array $translatable = ['name', 'page_title', 'bio', 'traits', 'meta_title', 'meta_description'];

    protected $fillable = [
        'name', 'page_title', 'slug', 'role', 'avatar_path', 'bio', 'traits', 'started_at', 'is_published', 'sort_order',
        'favorite_slot_ids', 'red_flag_slot_ids', 'top_streamers', 'favorite_post_ids',
        'meta_title', 'meta_description',
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
            'bio' => 'array',
            'traits' => 'array',
            'meta_title' => 'array',
            'meta_description' => 'array',
            'favorite_slot_ids' => 'array',
            'red_flag_slot_ids' => 'array',
            'top_streamers' => 'array',
            'favorite_post_ids' => 'array',
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
     * Job title shown under the name; the legacy "author" / "staff" values are not titles.
     */
    public function positionLabel(): ?string
    {
        $role = trim((string) $this->role);

        return $role === '' || in_array($role, ['author', 'staff'], true) ? null : $role;
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
        $ids = self::ids($this->favorite_post_ids);
        if ($ids === []) {
            return new Collection;
        }

        return Post::query()->where('is_published', true)->whereIn('id', $ids)->get()
            ->sortBy(fn (Post $post) => array_search($post->id, $ids, true))
            ->values();
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
