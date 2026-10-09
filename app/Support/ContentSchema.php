<?php

namespace App\Support;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * schema.org JSON-LD graph for the publications hub (/content/) and its sections (/content/{section}/).
 * The FAQPage block is emitted separately by content/partials/faq.
 */
class ContentSchema
{
    /**
     * @param  Collection<int, Post>  $posts
     * @param  Collection<int, array{type: string, label: string, url: string, icon: string}>  $sections
     * @return array<string, mixed>
     */
    public static function hub(string $canonical, string $name, string $description, Collection $posts, Collection $sections, ?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();

        $page = self::page($canonical, $name, $description, $locale) + [
            'hasPart' => $sections->map(fn (array $section): array => [
                '@type' => 'CollectionPage',
                '@id' => rtrim($section['url'], '/').'/#webpage',
                'url' => rtrim($section['url'], '/').'/',
                'name' => $section['label'],
            ])->values()->all(),
        ];

        return self::graph($canonical, $page, $posts->unique('id')->values(), 1, [
            [self::hubName($locale), $canonical],
        ], $locale);
    }

    /**
     * @param  LengthAwarePaginator<int, Post>  $posts
     * @return array<string, mixed>
     */
    public static function section(string $type, string $canonical, string $name, string $description, LengthAwarePaginator $posts, ?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();
        $hub = rtrim(localized_url($locale, 'content'), '/').'/';

        $page = self::page($canonical, $name, $description, $locale) + [
            'isPartOf' => ['@id' => $hub.'#webpage'],
        ];

        return self::graph($canonical, $page, collect($posts->items()), $posts->firstItem() ?? 1, [
            [self::hubName($locale), $hub],
            [__('content.sections.'.$type, [], $locale), $canonical],
        ], $locale);
    }

    /**
     * @param  array<string, mixed>  $page
     * @param  Collection<int, Post>  $posts
     * @param  list<array{0: string, 1: string}>  $crumbs
     * @return array<string, mixed>
     */
    private static function graph(string $canonical, array $page, Collection $posts, int $firstPosition, array $crumbs, string $locale): array
    {
        $crumbs = [[__('slot.home', [], $locale), rtrim(localized_url($locale, '/'), '/').'/'], ...$crumbs];

        $graph = [PostSchema::organization()];

        if ($posts->isNotEmpty()) {
            $page['mainEntity'] = ['@id' => $canonical.'#itemlist'];
        }
        $graph[] = $page;

        if ($posts->isNotEmpty()) {
            $graph[] = [
                '@type' => 'ItemList',
                '@id' => $canonical.'#itemlist',
                'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
                'numberOfItems' => $posts->count(),
                'itemListElement' => $posts->values()->map(fn (Post $post, int $i): array => [
                    '@type' => 'ListItem',
                    'position' => $firstPosition + $i,
                    'url' => rtrim($post->publicUrl($locale), '/').'/',
                    'name' => $post->displayTitle($locale),
                ])->all(),
            ];
        }

        $graph[] = [
            '@type' => 'BreadcrumbList',
            '@id' => $canonical.'#breadcrumb',
            'itemListElement' => array_map(fn (array $crumb, int $i): array => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb[0],
                'item' => $crumb[1],
            ], $crumbs, array_keys($crumbs)),
        ];

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    /**
     * @return array<string, mixed>
     */
    private static function page(string $canonical, string $name, string $description, string $locale): array
    {
        $site = rtrim((string) config('app.url'), '/');

        return array_filter([
            '@type' => 'CollectionPage',
            '@id' => $canonical.'#webpage',
            'url' => $canonical,
            'name' => $name,
            'description' => trim($description) ?: null,
            'inLanguage' => $locale,
            'publisher' => ['@id' => $site.'/#organization'],
            'breadcrumb' => ['@id' => $canonical.'#breadcrumb'],
        ], fn ($value) => $value !== null);
    }

    private static function hubName(string $locale): string
    {
        return __('content.title', [], $locale).' '.__('content.title_accent', [], $locale);
    }
}
