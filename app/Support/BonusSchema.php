<?php

namespace App\Support;

use App\Models\Bonus;
use Illuminate\Support\Collection;

/**
 * schema.org JSON-LD graph for the bonuses page (/bonuses/): the page, the list of casino offers and breadcrumbs.
 * The FAQPage block is emitted separately by content/partials/faq.
 */
class BonusSchema
{
    /**
     * @param  Collection<int, Bonus>  $bonuses
     * @return array<string, mixed>
     */
    public static function page(string $canonical, string $name, string $description, Collection $bonuses, ?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();
        $site = rtrim((string) config('app.url'), '/');
        $home = rtrim(localized_url($locale, '/'), '/').'/';

        $page = array_filter([
            '@type' => 'CollectionPage',
            '@id' => $canonical.'#webpage',
            'url' => $canonical,
            'name' => $name,
            'description' => trim($description) ?: null,
            'inLanguage' => $locale,
            'isPartOf' => ['@type' => 'WebSite', '@id' => $site.'/#website', 'url' => $site.'/', 'name' => 'slots.tube'],
            'publisher' => ['@id' => $site.'/#organization'],
            'breadcrumb' => ['@id' => $canonical.'#breadcrumb'],
            'mainEntity' => $bonuses->isNotEmpty() ? ['@id' => $canonical.'#offers'] : null,
        ], fn ($value) => $value !== null);

        $graph = [PostSchema::organization(), $page];

        if ($bonuses->isNotEmpty()) {
            $graph[] = [
                '@type' => 'ItemList',
                '@id' => $canonical.'#offers',
                'name' => $name,
                'itemListOrder' => 'https://schema.org/ItemListUnordered',
                'numberOfItems' => $bonuses->count(),
                'itemListElement' => $bonuses->values()->map(fn (Bonus $bonus, int $i): array => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'item' => self::offer($bonus, $locale),
                ])->all(),
            ];
        }

        $graph[] = [
            '@type' => 'BreadcrumbList',
            '@id' => $canonical.'#breadcrumb',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('slot.home', [], $locale), 'item' => $home],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('bonus.page.title_accent', [], $locale), 'item' => $canonical],
            ],
        ];

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    /**
     * @return array<string, mixed>
     */
    private static function offer(Bonus $bonus, string $locale): array
    {
        $regions = $bonus->isAllCountries() ? [] : $bonus->countryCodes();

        return array_filter([
            '@type' => 'Offer',
            'name' => $bonus->offerTitle($locale) ?: $bonus->casino_name,
            'category' => 'Casino bonus',
            'description' => __('bonus.terms', [], $locale),
            'url' => $bonus->cta_url ?: null,
            'eligibleRegion' => $regions !== []
                ? array_map(fn (string $code): array => ['@type' => 'Country', 'identifier' => $code], $regions)
                : null,
            'offeredBy' => array_filter([
                '@type' => 'Organization',
                'name' => $bonus->casino_name,
                'url' => $bonus->website_url ?: null,
                'logo' => $bonus->logo_path ? media_url($bonus->logo_path) : null,
            ]),
        ], fn ($value) => $value !== null && $value !== '');
    }
}
