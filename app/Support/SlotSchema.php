<?php

namespace App\Support;

use App\Models\Author;
use App\Models\Slot;

/**
 * schema.org JSON-LD graph for the public slot page (slots/show).
 * Every value must also be visible on the page — Google treats hidden structured data as spam.
 */
class SlotSchema
{
    public static function json(Slot $slot, string $canonical, ?float $rating, int $ratingCount): string
    {
        return (string) json_encode(
            self::graph($slot, $canonical, $rating, $ratingCount),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function graph(Slot $slot, string $canonical, ?float $rating, int $ratingCount): array
    {
        $locale = app()->getLocale();
        $site = rtrim((string) config('app.url'), '/');
        $title = $slot->displayTitle();
        $h1 = $slot->h1Title();
        $description = $slot->metaDescription();
        $cover = $slot->coverUrl();
        $published = $slot->created_at;
        $modified = $slot->showsContentUpdate() ? $slot->content_updated_on : $published;

        $ids = [
            'organization' => $site.'/#organization',
            'website' => $site.'/#website',
            'webpage' => $canonical.'#webpage',
            'breadcrumb' => $canonical.'#breadcrumb',
            'review' => $canonical.'#review',
            'game' => $canonical.'#game',
            'faq' => $canonical.'#faq',
        ];

        $graph = [
            [
                '@type' => 'Organization',
                '@id' => $ids['organization'],
                'name' => 'SlotsTube',
                'url' => $site.'/',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $site.'/assets/icons/logo-7.svg',
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => $ids['website'],
                'name' => 'SlotsTube',
                'url' => $site.'/',
                'publisher' => ['@id' => $ids['organization']],
            ],
            self::filled([
                '@type' => 'WebPage',
                '@id' => $ids['webpage'],
                'url' => $canonical,
                'name' => $h1,
                'description' => $description,
                'inLanguage' => $locale,
                'isPartOf' => ['@id' => $ids['website']],
                'breadcrumb' => ['@id' => $ids['breadcrumb']],
                'primaryImageOfPage' => $cover ? ['@type' => 'ImageObject', 'url' => $cover] : null,
                'datePublished' => $published?->toIso8601String(),
                'dateModified' => $modified?->toIso8601String(),
                'about' => ['@id' => $ids['game']],
                'mainEntity' => ['@id' => $ids['review']],
                'reviewedBy' => self::person($slot->reviewer, $locale),
                'lastReviewed' => $slot->last_reviewed_on?->toDateString(),
            ]),
            [
                '@type' => 'BreadcrumbList',
                '@id' => $ids['breadcrumb'],
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('slot.home'), 'item' => localized_url($locale, '/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => __('slot.slots'), 'item' => localized_url($locale, 'free-slots')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $title, 'item' => $canonical],
                ],
            ],
            self::filled([
                '@type' => 'Review',
                '@id' => $ids['review'],
                'name' => $h1,
                'headline' => $h1,
                'description' => $description,
                'reviewBody' => self::plain($slot->review_overview) ?: null,
                'inLanguage' => $locale,
                'image' => $cover,
                'datePublished' => $published?->toIso8601String(),
                'dateModified' => $modified?->toIso8601String(),
                'itemReviewed' => ['@id' => $ids['game']],
                'reviewRating' => $slot->editorial_score !== null
                    ? [
                        '@type' => 'Rating',
                        'ratingValue' => (float) $slot->editorial_score,
                        'bestRating' => 5,
                        'worstRating' => 1,
                    ]
                    : null,
                'positiveNotes' => self::notes(lines_to_array($slot->pros)),
                'negativeNotes' => self::notes(lines_to_array($slot->cons)),
                'author' => self::person($slot->author, $locale) ?? ['@id' => $ids['organization']],
                'editor' => $slot->showsContentUpdate() ? self::person($slot->updatedByAuthor, $locale) : null,
                'publisher' => ['@id' => $ids['organization']],
                'mainEntityOfPage' => ['@id' => $ids['webpage']],
            ]),
            self::game($slot, $canonical, $ids, $title, $cover, $rating, $ratingCount),
        ];

        $faq = self::faq($slot);
        if ($faq !== []) {
            $graph[] = [
                '@type' => 'FAQPage',
                '@id' => $ids['faq'],
                'isPartOf' => ['@id' => $ids['webpage']],
                'mainEntity' => $faq,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }

    /**
     * @param  array<string, string>  $ids
     * @return array<string, mixed>
     */
    private static function game(Slot $slot, string $canonical, array $ids, string $title, ?string $cover, ?float $rating, int $ratingCount): array
    {
        $provider = $slot->provider;
        $studio = $provider ? self::filled([
            '@type' => 'Organization',
            'name' => $provider->displayName(),
            'url' => $provider->is_published ? rtrim($provider->publicUrl(), '/').'/' : null,
            'logo' => $provider->logoUrl(),
        ]) : null;

        $screenshots = collect([...$slot->screenshotGallery(), ...$slot->mobileScreenshotGallery()])
            ->unique('url')
            ->map(fn (array $shot): array => self::filled([
                '@type' => 'ImageObject',
                'url' => $shot['url'],
                'caption' => $shot['caption'],
            ]))
            ->values()
            ->all();

        $browser = stripos((string) $slot->technology, 'html5') !== false;

        return self::filled([
            '@type' => 'VideoGame',
            '@id' => $ids['game'],
            'name' => $title,
            'url' => $canonical,
            'description' => $slot->subtitle(),
            'image' => $cover,
            'screenshot' => $screenshots,
            'genre' => $slot->game_type ?: 'Video slot',
            'applicationCategory' => 'GameApplication',
            'gamePlatform' => $browser ? 'Web browser' : null,
            'operatingSystem' => $browser ? 'Web browser (HTML5)' : null,
            'playMode' => 'https://schema.org/SinglePlayer',
            'keywords' => trim((string) $slot->theme_text) ?: null,
            'datePublished' => $slot->release_date?->format('Y-m'),
            'author' => $studio,
            'publisher' => $studio,
            'offers' => filled($slot->demo_url)
                ? [
                    '@type' => 'Offer',
                    'name' => __('slot.play_for_free'),
                    'price' => 0,
                    'priceCurrency' => 'USD',
                    'availability' => 'https://schema.org/InStock',
                    'url' => $canonical,
                ]
                : null,
            'aggregateRating' => ($rating && $ratingCount)
                ? [
                    '@type' => 'AggregateRating',
                    'ratingValue' => $rating,
                    'ratingCount' => $ratingCount,
                    'reviewCount' => $ratingCount,
                    'bestRating' => 5,
                    'worstRating' => 1,
                ]
                : null,
            'review' => ['@id' => $ids['review']],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function person(?Author $author, string $locale): ?array
    {
        if (! $author) {
            return null;
        }

        $url = $author->is_published ? rtrim($author->publicUrl($locale), '/').'/' : null;

        return self::filled([
            '@type' => 'Person',
            '@id' => $url ? $url.'#person' : null,
            'name' => $author->displayName(),
            'url' => $url,
            'jobTitle' => $author->positionLabel(),
            'image' => $author->avatarUrl(),
            'sameAs' => $author->sameAsUrls(),
        ]);
    }

    /**
     * @param  list<string>  $lines
     * @return array<string, mixed>|null
     */
    private static function notes(array $lines): ?array
    {
        if ($lines === []) {
            return null;
        }

        return [
            '@type' => 'ItemList',
            'itemListElement' => array_map(
                fn (string $line, int $i): array => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $line],
                $lines,
                array_keys($lines)
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function faq(Slot $slot): array
    {
        $out = [];
        foreach (is_array($slot->faq) ? $slot->faq : [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $question = self::plain($item['question'] ?? $item['q'] ?? '');
            $answer = self::plain($item['answer'] ?? $item['a'] ?? '');
            if ($question === '' || $answer === '') {
                continue;
            }
            $out[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
            ];
        }

        return $out;
    }

    private static function plain(mixed $html): string
    {
        $text = html_entity_decode(preg_replace('/<[^>]*>/', ' ', (string) $html) ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function filled(array $data): array
    {
        return array_filter($data, fn ($value): bool => $value !== null && $value !== '' && $value !== []);
    }
}
