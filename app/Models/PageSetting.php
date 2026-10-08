<?php

namespace App\Models;

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
 */
class PageSetting extends Model
{
    public const LOCALES = ['en', 'de', 'fr'];

    protected $fillable = ['key', 'meta_title', 'meta_description', 'noindex', 'faq'];

    protected function casts(): array
    {
        return [
            'meta_title' => 'array',
            'meta_description' => 'array',
            'noindex' => 'boolean',
            'faq' => 'array',
        ];
    }

    /**
     * @return array<string, array{label: string, group: string, path: string, faq: bool}>
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
