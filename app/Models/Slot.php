<?php

namespace App\Models;

use App\Models\Concerns\Trashable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Slot extends Model
{
    use HasTranslations;
    use Trashable;

    public array $translatable = [
        'title', 'excerpt', 'body', 'intro', 'quick_verdict', 'about_text', 'rtp_blurb',
        'review_overview', 'pros', 'cons', 'audience_intro', 'best_for', 'not_ideal_for',
        'bonus_features_body', 'experience_title', 'experience_body', 'responsible_play',
        'responsible_steps',
        'symbols_intro', 'symbols_mid', 'symbols_outro', 'rtp_section_text', 'interpret_cards', 'screenshots_intro',
        'mobile_intro', 'mobile_checklist', 'similar_intro', 'similar_outro',
        'similar_new_intro', 'similar_bonus_fit', 'similar_watch_out', 'similar_note',
        'methodology_status', 'methodology_points', 'methodology_body', 'faq',
        'meta_title', 'meta_description',
    ];

    protected $fillable = [
        'provider_id', 'author_id', 'reviewer_id',
        'title', 'slug', 'cover_path', 'about_cover_path', 'demo_url', 'excerpt', 'body',
        'rtp', 'volatility', 'release_date', 'game_type', 'grid', 'win_system', 'rtp_text',
        'rtp_min', 'rtp_max', 'max_win', 'stake_range', 'technology', 'wild_symbol',
        'free_spins', 'progressive', 'bonus_buy', 'tumbling_wins', 'gamble_feature',
        'scatter_symbol', 'features_text', 'theme_text', 'filter_bonus_buy',
        'filter_free_spins', 'filter_tumbling', 'filter_scatter', 'filter_gamble',
        'filter_progressive', 'editorial_score', 'show_session_data', 'intro',
        'quick_verdict', 'about_text', 'rtp_blurb', 'review_overview', 'pros', 'cons',
        'audience_intro', 'best_for', 'not_ideal_for', 'bonus_features_body',
        'experience_title', 'experience_body', 'responsible_play', 'responsible_steps', 'symbols_intro',
        'symbols_mid', 'symbols_outro',
        'rtp_section_text', 'interpret_cards', 'screenshots_intro', 'mobile_intro',
        'mobile_checklist', 'similar_intro', 'similar_outro', 'similar_new_intro',
        'similar_bonus_fit', 'similar_watch_out', 'similar_note',
        'methodology_status', 'methodology_points', 'methodology_body', 'faq', 'meta_title', 'meta_description',
        'symbols', 'paytable_rows', 'paytable_headers', 'screenshots', 'mobile_screenshots',
        'is_published', 'is_featured', 'is_new', 'is_popular', 'published_at', 'content_updated_on', 'updated_by_author_id',
        'last_reviewed_on', 'review_focus',
    ];

    protected function casts(): array
    {
        $json = [
            'title', 'excerpt', 'body', 'intro', 'quick_verdict', 'about_text', 'rtp_blurb',
            'review_overview', 'pros', 'cons', 'audience_intro', 'best_for', 'not_ideal_for',
            'bonus_features_body', 'experience_title', 'experience_body', 'responsible_play',
            'responsible_steps',
            'symbols_intro', 'symbols_mid', 'symbols_outro', 'rtp_section_text', 'interpret_cards', 'screenshots_intro',
            'mobile_intro', 'mobile_checklist', 'similar_intro', 'similar_outro',
            'similar_new_intro', 'similar_bonus_fit', 'similar_watch_out', 'similar_note',
            'methodology_status', 'methodology_points', 'methodology_body', 'faq',
            'meta_title', 'meta_description', 'symbols', 'paytable_rows', 'paytable_headers', 'screenshots',
            'mobile_screenshots',
        ];

        $casts = [
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'is_popular' => 'boolean',
            'filter_bonus_buy' => 'boolean',
            'filter_free_spins' => 'boolean',
            'filter_tumbling' => 'boolean',
            'filter_scatter' => 'boolean',
            'filter_gamble' => 'boolean',
            'filter_progressive' => 'boolean',
            'show_session_data' => 'boolean',
            'published_at' => 'datetime',
            'content_updated_on' => 'date',
            'last_reviewed_on' => 'date',
            'release_date' => 'date',
            'rtp' => 'decimal:2',
            'rtp_min' => 'decimal:2',
            'rtp_max' => 'decimal:2',
            'editorial_score' => 'decimal:1',
        ];

        foreach ($json as $field) {
            $casts[$field] = 'array';
        }

        return $casts;
    }

    protected static function booted(): void
    {
        static::saving(function (Slot $slot): void {
            if ($slot->content_updated_on === null) {
                $slot->updated_by_author_id = null;
            }
        });

        static::saved(function (Slot $slot): void {
            \App\Support\MediaMirror::mirrorSlotMedia($slot);
        });
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'reviewer_id');
    }

    public function updatedByAuthor(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'updated_by_author_id');
    }

    public function showsEditorialPledge(): bool
    {
        return $this->author_id !== null
            || $this->reviewer_id !== null
            || $this->last_reviewed_on !== null
            || filled($this->review_focus);
    }

    public function editorialRoleLabel(): ?string
    {
        if (! $this->author) {
            return null;
        }

        $role = trim((string) ($this->author->role ?? ''));
        if ($role === '' || in_array($role, ['author', 'staff'], true)) {
            $role = __('slot.slot_analyst');
        }

        if (str_contains($role, 'SlotsTube')) {
            return $role;
        }

        return __('slot.role_suffix', ['role' => $role]);
    }

    public function showsContentUpdate(): bool
    {
        return $this->content_updated_on !== null;
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class);
    }

    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(Theme::class);
    }

    public function bonuses(): BelongsToMany
    {
        return $this->belongsToMany(Bonus::class)
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('bonuses.id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(SlotReview::class);
    }

    public function publishedReviews(): HasMany
    {
        return $this->reviews()->where('is_published', true)->latest();
    }

    /**
     * @param  iterable<int, SlotReview>  $reviews
     * @return array{cards: \Illuminate\Support\Collection<int, SlotReview>, visibleIds: list<int>, wideIds: list<int>}
     */
    public static function layoutPlayerReviews(iterable $reviews): array
    {
        $cards = collect($reviews)->values();
        $visible = $cards->take(4)->values();
        $visibleIds = $visible->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $wideIds = $visible->count() % 2 === 1
            ? [(int) $visible->last()->id]
            : [];

        return [
            'cards' => $cards,
            'visibleIds' => $visibleIds,
            'wideIds' => $wideIds,
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function displayTitle(?string $locale = null): string
    {
        return (string) ($this->getTranslation('title', $locale ?: app()->getLocale())
            ?: $this->getTranslation('title', 'en')
            ?: $this->slug);
    }

    public function h1Title(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return $this->displayTitle($locale).' '.__('slot.h1_suffix', [], $locale);
    }

    public function rtpPercentLabel(): ?string
    {
        if ($this->rtp === null || $this->rtp === '') {
            return null;
        }

        return number_format((float) $this->rtp, 2, '.', '').'%';
    }

    public function similarReturnLabel(): string
    {
        $rtp = '';
        if ($this->rtp_min !== null && $this->rtp_max !== null) {
            $from = number_format((float) $this->rtp_min, 2, '.', '').'%';
            $to = number_format((float) $this->rtp_max, 2, '.', '').'%';
            $rtp = $from === $to ? $from : $from.'-'.$to;
        } elseif ($this->rtp !== null) {
            $rtp = $this->rtpPercentLabel() ?? '';
        }

        $max = trim((string) $this->max_win);
        if ($rtp !== '' && $max !== '') {
            return $rtp.' / '.$max;
        }

        return $rtp !== '' ? $rtp : ($max !== '' ? $max : '—');
    }

    public function maxWinLabel(): string
    {
        $raw = trim((string) $this->max_win);
        if ($raw === '') {
            return '—';
        }

        $digits = preg_replace('/\D/', '', $raw) ?? '';
        if ($digits !== '' && (int) $digits > 0) {
            return number_format((int) $digits, 0, '.', ',').'×';
        }

        return $raw;
    }

    public function riskProfileLabel(): string
    {
        $vol = trim((string) $this->volatility);
        if ($vol === '') {
            return '—';
        }

        if (str_ends_with(Str::lower($vol), 'volatility') || str_ends_with(Str::lower($vol), 'volatilität') || str_ends_with(Str::lower($vol), 'volatilité')) {
            return $vol;
        }

        return __('slot.risk_profile_value', ['vol' => $vol]);
    }

    public function autoSubtitle(): string
    {
        $provider = $this->provider?->getTranslation('name', app()->getLocale())
            ?: $this->provider?->getTranslation('name', 'en')
            ?: __('slot.unknown_provider');
        $rtp = $this->rtpPercentLabel() ?: '—';
        $vol = $this->volatility
            ? __('slot.risk_profile_value', ['vol' => Str::lower($this->volatility)])
            : '—';
        $max = $this->maxWinLabel();

        return __('slot.auto_subtitle', [
            'provider' => $provider,
            'rtp' => $rtp,
            'vol' => $vol,
            'max' => $max,
        ]);
    }

    public function subtitle(): string
    {
        $intro = trim((string) ($this->getTranslation('intro', app()->getLocale())
            ?: $this->getTranslation('intro', 'en')
            ?: ''));

        return $intro !== '' ? $intro : $this->autoSubtitle();
    }

    public function coverUrl(): ?string
    {
        return filled($this->cover_path) ? media_url((string) $this->cover_path) : null;
    }

    public function aboutCoverUrl(): ?string
    {
        $path = $this->about_cover_path ?: $this->cover_path;

        return filled($path) ? media_url((string) $path) : null;
    }

    public function responsibleSteps(): array
    {
        $steps = $this->responsible_steps;
        if (! is_array($steps)) {
            return [];
        }

        return array_values(array_filter($steps, function ($step) {
            return is_array($step) && filled($step['title'] ?? null);
        }));
    }

    /**
     * @return list<array{title: string, body: string}>
     */
    public function methodologyPoints(): array
    {
        $points = $this->methodology_points;
        if (! is_array($points)) {
            return [];
        }

        $out = [];
        foreach ($points as $point) {
            if (! is_array($point)) {
                continue;
            }
            $title = trim((string) ($point['title'] ?? ''));
            $body = trim((string) ($point['body'] ?? $point['text'] ?? ''));
            if ($title === '' && $body === '') {
                continue;
            }
            $out[] = [
                'title' => $title,
                'body' => $body,
            ];
        }

        return $out;
    }

    public function showsMethodology(): bool
    {
        return filled($this->methodology_status)
            || $this->methodologyPoints() !== []
            || filled(trim(html_entity_decode(strip_tags((string) $this->methodology_body))));
    }

    public const INTERPRET_TITLES = [
        'Dry-spell test',
        'Mobile panel check',
        'Low-RTP warning',
    ];

    /**
     * @param  array<int, mixed>  $cards
     * @return list<array{title: string, body: string}>
     */
    public static function normalizeInterpretCards(array $cards): array
    {
        $out = [];
        foreach (self::INTERPRET_TITLES as $index => $title) {
            $item = is_array($cards[$index] ?? null) ? $cards[$index] : [];
            $out[] = [
                'title' => $title,
                'body' => trim((string) ($item['body'] ?? $item['text'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{title: string, body: string}>
     */
    public function interpretGuideCards(): array
    {
        $raw = is_array($this->interpret_cards) ? $this->interpret_cards : [];

        return array_values(array_filter(
            self::normalizeInterpretCards($raw),
            fn (array $card): bool => $card['body'] !== ''
        ));
    }

    /**
     * Normalize legacy string captions into {en, de, fr}.
     *
     * @param  mixed  $caption
     * @return array{en: string, de: string, fr: string}
     */
    public static function normalizeShotCaption(mixed $caption): array
    {
        if (is_string($caption)) {
            return ['en' => $caption, 'de' => '', 'fr' => ''];
        }
        if (! is_array($caption)) {
            return ['en' => '', 'de' => '', 'fr' => ''];
        }

        return [
            'en' => trim((string) ($caption['en'] ?? '')),
            'de' => trim((string) ($caption['de'] ?? '')),
            'fr' => trim((string) ($caption['fr'] ?? '')),
        ];
    }

    public static function captionForLocale(mixed $caption, ?string $locale = null): string
    {
        $normalized = self::normalizeShotCaption($caption);
        $locale = $locale ?: app()->getLocale();
        $value = trim((string) ($normalized[$locale] ?? ''));
        if ($value !== '') {
            return $value;
        }

        return trim((string) ($normalized['en'] ?? ''));
    }

    /**
     * @param  list<array<string, mixed>>  $shots
     * @return list<array{path: string, url: string, caption: string}>
     */
    public static function mediaGallery(array $shots): array
    {
        $items = [];
        foreach ($shots as $shot) {
            if (! is_array($shot)) {
                continue;
            }
            $path = $shot['path'] ?? $shot['image'] ?? null;
            if (! filled($path)) {
                continue;
            }
            $items[] = [
                'path' => (string) $path,
                'url' => media_url((string) $path),
                'caption' => self::captionForLocale($shot['caption'] ?? null),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{path: string, url: string, caption: string}>
     */
    public function screenshotGallery(): array
    {
        return self::mediaGallery(is_array($this->screenshots) ? $this->screenshots : []);
    }

    /**
     * @return list<array{path: string, url: string, caption: string}>
     */
    public function mobileScreenshotGallery(): array
    {
        return self::mediaGallery(is_array($this->mobile_screenshots) ? $this->mobile_screenshots : []);
    }

    /**
     * @return list<array{name: string, image: ?string, payouts: ?list<array{mult: string, val: string}>, text: ?string}>
     */
    public function symbolCards(): array
    {
        $cards = [];
        foreach (is_array($this->symbols) ? $this->symbols : [] as $symbol) {
            if (! is_array($symbol) || ! filled($symbol['name'] ?? null)) {
                continue;
            }

            $payoutSource = $symbol['payout_text'] ?? null;
            $text = $symbol['description'] ?? null;
            $payouts = filled($payoutSource) ? parse_symbol_payouts((string) $payoutSource) : null;
            if ($payouts === null && filled($payoutSource) && ! filled($text)) {
                $text = $payoutSource;
            }

            $cards[] = [
                'name' => (string) $symbol['name'],
                'image' => $symbol['image'] ?? null,
                'payouts' => $payouts,
                'text' => filled($text) ? (string) $text : null,
            ];
        }

        return $cards;
    }

    /**
     * @return array{symbol: string, columns: list<string>, rows: list<array{image: ?string, label: string, cells: list<string>}>}
     */
    public function paytableView(): array
    {
        $headers = is_array($this->paytable_headers) ? $this->paytable_headers : [];
        $columns = [];
        if (! empty($headers['columns']) && is_array($headers['columns'])) {
            foreach ($headers['columns'] as $column) {
                if (is_array($column)) {
                    $columns[] = (string) ($column['label'] ?? $column['value'] ?? '');
                } else {
                    $columns[] = (string) $column;
                }
            }
        }
        if ($columns === []) {
            foreach (['col1', 'col2', 'col3', 'col4'] as $key) {
                if (filled($headers[$key] ?? null)) {
                    $columns[] = (string) $headers[$key];
                }
            }
        }

        $rows = [];
        foreach (is_array($this->paytable_rows) ? $this->paytable_rows : [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $cells = [];
            if (! empty($row['cells']) && is_array($row['cells'])) {
                foreach ($row['cells'] as $cell) {
                    $cells[] = is_array($cell) ? (string) ($cell['value'] ?? $cell['label'] ?? '') : (string) $cell;
                }
            } else {
                foreach (['col1', 'col2', 'col3', 'col4'] as $key) {
                    $cells[] = (string) ($row[$key] ?? '');
                }
                while ($cells !== [] && end($cells) === '') {
                    array_pop($cells);
                }
            }

            $label = (string) ($row['label'] ?? $row['symbol'] ?? $row['name'] ?? '');
            $image = $row['image'] ?? null;
            if (! filled($image) && $label === '' && collect($cells)->every(fn ($cell) => $cell === '')) {
                continue;
            }

            $rows[] = [
                'image' => $image,
                'label' => $label,
                'cells' => $cells,
            ];
        }

        $maxCells = 0;
        foreach ($rows as $row) {
            $maxCells = max($maxCells, count($row['cells']));
        }
        while (count($columns) < $maxCells) {
            $columns[] = '';
        }

        return [
            'symbol' => filled($headers['symbol'] ?? null) ? (string) $headers['symbol'] : 'Symbol',
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    public function averageRating(): ?float
    {
        $avg = $this->publishedReviews()->avg('rating');

        return $avg !== null ? round((float) $avg, 1) : null;
    }

    public function reviewsCount(): int
    {
        return (int) $this->publishedReviews()->count();
    }

    public function publicUrl(?string $locale = null): string
    {
        return localized_url($locale, 'slots/'.$this->slug);
    }

    public static function makeSlugFromTitle(string $title): string
    {
        $slug = Str::slug($title);

        return $slug !== '' ? $slug : 'slot-'.Str::lower(Str::random(6));
    }

    public function ensureUniqueSlug(?string $slug = null): string
    {
        $base = $slug ?: static::makeSlugFromTitle($this->displayTitle('en') ?: 'slot');
        $candidate = $base;
        $i = 2;
        while (static::query()
            ->where('slug', $candidate)
            ->when($this->exists, fn ($q) => $q->where('id', '!=', $this->id))
            ->exists()) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }
}
