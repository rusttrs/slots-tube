<?php

namespace App\Models;

use App\Models\Concerns\HasLikes;
use App\Models\Concerns\Trashable;
use App\Support\MediaMirror;
use App\Support\PostBody;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Translatable\HasTranslations;

class Post extends Model
{
    use HasLikes;
    use HasTranslations;
    use Trashable;

    public array $translatable = ['title', 'excerpt', 'body'];

    protected $fillable = [
        'author_id', 'provider_id', 'type',
        'title', 'slug', 'cover_path', 'excerpt', 'body',
        'is_published', 'is_featured', 'published_at', 'content_updated_on', 'updated_by_author_id',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'excerpt' => 'array',
            'body' => 'array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'content_updated_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Post $post): void {
            // Keep published_at for sorting/legacy; site “Published” uses created_at like slots.
            if ($post->is_published && $post->published_at === null) {
                $post->published_at = $post->created_at ?: now();
            }

            if ($post->content_updated_on === null) {
                $post->updated_by_author_id = null;
            }
        });

        static::saved(function (Post $post): void {
            if (filled($post->cover_path)) {
                MediaMirror::mirrorPath((string) $post->cover_path);
            }

            PostBody::mirrorEmbedded($post->getTranslations('body'));
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function updatedByAuthor(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'updated_by_author_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function showsContentUpdate(): bool
    {
        return $this->content_updated_on !== null;
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            'news' => 'News',
            'blog' => 'Blogs',
            'guide' => 'Guides',
            'streamer' => 'Streamers',
        ];
    }

    /**
     * Reserved section paths under /content/ — cannot be used as post slugs.
     *
     * @return list<string>
     */
    public static function reservedSlugs(): array
    {
        return ['news', 'blogs', 'guides', 'streamers'];
    }

    public function sectionPath(): string
    {
        return match ($this->type) {
            'news' => 'content/news',
            'blog' => 'content/blogs',
            'guide' => 'content/guides',
            'streamer' => 'content/streamers',
            default => 'content/guides',
        };
    }

    public function sectionUrl(?string $locale = null): string
    {
        return localized_url($locale, $this->sectionPath());
    }

    public function backLabel(): string
    {
        $key = 'post.back.'.$this->type;

        return trans()->has($key) ? __($key) : __('post.back.guide');
    }

    public function scopeMatchingProvider(Builder $query, Provider $provider): Builder
    {
        $names = collect($provider->getTranslations('name') ?: [])
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->values();

        if ($names->isEmpty() && filled($provider->slug)) {
            $names = collect([str_replace('-', ' ', $provider->slug)]);
        }

        return $query->where('is_published', true)->where(function (Builder $q) use ($names): void {
            foreach ($names as $name) {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $name).'%';
                foreach (['en', 'de', 'fr'] as $locale) {
                    $q->orWhereRaw("title->>? ILIKE ? ESCAPE '\\'", [$locale, $term]);
                }
            }
        });
    }

    public function displayTitle(?string $locale = null): string
    {
        return (string) ($this->getTranslation('title', $locale ?: app()->getLocale())
            ?: $this->getTranslation('title', 'en')
            ?: $this->slug);
    }

    public function coverUrl(): ?string
    {
        return filled($this->cover_path) ? media_url($this->cover_path) : null;
    }

    public function displayExcerpt(?string $locale = null): string
    {
        return (string) ($this->getTranslation('excerpt', $locale ?: app()->getLocale())
            ?: $this->getTranslation('excerpt', 'en')
            ?: '');
    }

    public function renderedBody(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $content = $this->getTranslation('body', $locale, false);
        if (blank($content)) {
            $content = $this->getTranslation('body', 'en', false);
        }

        return PostBody::render($content);
    }

    public function publicUrl(?string $locale = null): string
    {
        return localized_url($locale, 'content/'.$this->slug);
    }
}
