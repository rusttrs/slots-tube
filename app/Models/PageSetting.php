<?php

namespace App\Models;

use App\Support\MediaMirror;
use App\Support\PageAboutBlocks;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-editable SEO and FAQ of a registered page (see config/page_settings.php).
 *
 * @property string $key
 * @property array<string, string>|null $meta_title
 * @property array<string, string>|null $meta_description
 * @property bool $noindex
 * @property array<string, list<array{question: string, answer: string}>>|null $faq
 * @property array<string, array<string, string>>|null $texts
 * @property array<string, list<array{type: string, data: array<string, mixed>}>>|null $blocks
 * @property list<array{title?: array<string, string>, text?: array<string, string>, author_ids?: list<int|string>, show_join?: bool}>|null $sections
 */
class PageSetting extends Model
{
    public const LOCALES = ['en', 'de', 'fr'];

    protected $fillable = ['key', 'meta_title', 'meta_description', 'noindex', 'faq', 'blocks', 'texts', 'sections'];

    protected function casts(): array
    {
        return [
            'meta_title' => 'array',
            'meta_description' => 'array',
            'noindex' => 'boolean',
            'faq' => 'array',
            'blocks' => 'array',
            'texts' => 'array',
            'sections' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (PageSetting $page): void {
            if ($page->wasChanged('blocks') || $page->wasRecentlyCreated) {
                foreach (PageAboutBlocks::imagePaths($page->blocks ?? []) as $path) {
                    MediaMirror::mirrorPath($path);
                }
            }
        });
    }

    /**
     * @return array<string, array{label: string, group: string, path: string, faq: bool, team_sections?: bool, texts?: array<string, array{label: string, default: string, multiline?: bool, help?: string}>}>
     */
    public static function registry(): array
    {
        return config('page_settings', []);
    }

    public static function for(string $key): self
    {
        if (! array_key_exists($key, static::registry())) {
            throw new \InvalidArgumentException("Page setting [{$key}] is not registered in config/page_settings.php.");
        }

        return static::query()->firstOrCreate(['key' => $key]);
    }

    public static function syncRegistry(): void
    {
        foreach (array_keys(static::registry()) as $key) {
            static::query()->firstOrCreate(['key' => $key]);
        }
    }

    public function scopeRegistered(Builder $query): Builder
    {
        return $query->whereIn('key', array_keys(static::registry()));
    }

    /**
     * @return array{label: string, group: string, path: string, faq: bool}
     */
    public function definition(): array
    {
        return static::registry()[$this->key] ?? ['label' => $this->key, 'group' => '—', 'path' => '', 'faq' => false];
    }

    public function label(): string
    {
        return $this->definition()['label'];
    }

    public function path(): string
    {
        return '/'.ltrim($this->definition()['path'], '/');
    }

    public function hasFaq(): bool
    {
        return (bool) $this->definition()['faq'];
    }

    /**
     * Editable page texts declared under `texts` in config/page_settings.php.
     *
     * @return array<string, array{label: string, default: string, multiline?: bool, help?: string}>
     */
    public function textFields(): array
    {
        return $this->definition()['texts'] ?? [];
    }

    public function hasBlocks(): bool
    {
        return (bool) ($this->definition()['blocks'] ?? false);
    }

    /**
     * Description cards (partials/page-about) for the locale; an empty locale falls back to English.
     *
     * @return list<array{title: string, hero: string, pills: list<string>, guides: bool, elements: list<array<string, mixed>>}>
     */
    public function blocksFor(?string $locale = null): array
    {
        if (! $this->hasBlocks()) {
            return [];
        }

        $blocks = is_array($this->blocks) ? $this->blocks : [];

        return PageAboutBlocks::sections($blocks[$locale ?: app()->getLocale()] ?? []) ?: PageAboutBlocks::sections($blocks['en'] ?? []);
    }

    public function hasTeamSections(): bool
    {
        return (bool) ($this->definition()['team_sections'] ?? false);
    }

    /**
     * Author groups of the team page as edited in the admin.
     *
     * @return list<array{title: array<string, string>, text: array<string, string>, author_ids: list<int>, show_join: bool}>
     */
    public function teamSections(): array
    {
        return array_values(array_map(fn ($section): array => [
            'title' => is_array($section['title'] ?? null) ? $section['title'] : [],
            'text' => is_array($section['text'] ?? null) ? $section['text'] : [],
            'author_ids' => array_values(array_unique(array_filter(array_map('intval', (array) ($section['author_ids'] ?? []))))),
            'show_join' => (bool) ($section['show_join'] ?? false),
        ], array_filter(is_array($this->sections) ? $this->sections : [], 'is_array')));
    }

    /**
     * Admin text for the locale, then the English one, then the translation key from the registry.
     */
    public function text(string $field, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $texts = is_array($this->texts) ? $this->texts : [];

        foreach (array_unique([$locale, 'en']) as $candidate) {
            $value = trim((string) ($texts[$candidate][$field] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $default = $this->textFields()[$field]['default'] ?? null;

        return $default ? (string) __($default, [], $locale) : '';
    }

    public function metaTitle(string $fallback, ?string $locale = null): string
    {
        return $this->translated('meta_title', $locale) ?: $fallback;
    }

    public function metaDescription(string $fallback, ?string $locale = null): string
    {
        return $this->translated('meta_description', $locale) ?: $fallback;
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    public function faqFor(?string $locale = null): array
    {
        if (! $this->hasFaq()) {
            return [];
        }

        return $this->faqItems($locale ?: app()->getLocale()) ?: $this->faqItems('en');
    }

    private function translated(string $attribute, ?string $locale): string
    {
        $values = is_array($this->{$attribute}) ? $this->{$attribute} : [];
        $locale = $locale ?: app()->getLocale();

        return trim((string) ($values[$locale] ?? '')) ?: trim((string) ($values['en'] ?? ''));
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function faqItems(string $locale): array
    {
        $items = is_array($this->faq[$locale] ?? null) ? $this->faq[$locale] : [];

        return array_values(array_filter(array_map(fn ($item): array => [
            'question' => trim((string) ($item['question'] ?? '')),
            'answer' => trim((string) ($item['answer'] ?? '')),
        ], $items), fn (array $item): bool => $item['question'] !== '' && $item['answer'] !== ''));
    }
}
