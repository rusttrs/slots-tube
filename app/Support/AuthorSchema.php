<?php

namespace App\Support;

use App\Models\Author;
use App\Models\Post;
use App\Models\Slot;
use Illuminate\Support\Collection;

/**
 * schema.org JSON-LD graphs for the author page (/authors/{slug}/) and the team page (/authors/).
 * Person ids (…/authors/{slug}/#person) match the author references in PostSchema and SlotSchema.
 * The team page FAQPage block is emitted separately by content/partials/faq.
 */
class AuthorSchema
{
    /**
     * @param  array<string, mixed>  $graph
     */
    public static function json(array $graph): string
    {
        return (string) json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    }

    /**
     * @param  Collection<int, Slot>  $slots
     * @param  Collection<int, Post>  $posts
     * @return array<string, mixed>
     */
    public static function profile(Author $author, string $canonical, Collection $slots, Collection $posts, int $worksCount, ?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();
        $site = self::site();
        $avatar = $author->avatarUrl();
        $position = $author->positionLabel($locale);
        $tags = $author->tags($locale);
        $team = self::teamUrl($locale);

        $page = self::filled([
            '@type' => 'ProfilePage',
            '@id' => $canonical.'#webpage',
            'url' => $canonical,
            'name' => $author->pageTitle($locale),
            'description' => $author->metaDescription($locale),
            'inLanguage' => $locale,
            'isPartOf' => ['@id' => $site.'/#website'],
            'breadcrumb' => ['@id' => $canonical.'#breadcrumb'],
            'mainEntity' => ['@id' => $canonical.'#person'],
            'primaryImageOfPage' => $avatar ? ['@type' => 'ImageObject', 'url' => $avatar] : null,
            'dateCreated' => $author->created_at?->toIso8601String(),
            'dateModified' => $author->updated_at?->toIso8601String(),
            'hasPart' => array_values(array_filter([
                $slots->isNotEmpty() ? ['@id' => $canonical.'#latest-slots'] : null,
                $posts->isNotEmpty() ? ['@id' => $canonical.'#latest-posts'] : null,
            ])) ?: null,
        ]);

        $person = self::filled([
            '@type' => 'Person',
            '@id' => $canonical.'#person',
            'name' => $author->displayName($locale),
            'url' => $canonical,
            'image' => $avatar ? ['@type' => 'ImageObject', 'url' => $avatar] : null,
            'jobTitle' => $position,
            'description' => $author->metaDescription($locale),
            'knowsAbout' => $tags ?: null,
            'sameAs' => $author->sameAsUrls() ?: null,
            'worksFor' => self::filled([
                '@type' => 'EmployeeRole',
                'roleName' => $position,
                'startDate' => $author->started_at?->toDateString(),
                'worksFor' => ['@id' => $site.'/#organization'],
            ]),
            'mainEntityOfPage' => ['@id' => $canonical.'#webpage'],
            'agentInteractionStatistic' => $worksCount > 0 ? [
                '@type' => 'InteractionCounter',
                'interactionType' => 'https://schema.org/WriteAction',
                'userInteractionCount' => $worksCount,
            ] : null,
        ]);

        $graph = [PostSchema::organization(), self::website($locale), $page, $person];

        if ($slots->isNotEmpty()) {
            $graph[] = self::itemList($canonical.'#latest-slots', $author->latestSlotsTitle($locale), $canonical,
                $slots->map(fn (Slot $slot): array => [rtrim($slot->publicUrl($locale), '/').'/', $slot->displayTitle($locale)]));
        }
        if ($posts->isNotEmpty()) {
            $graph[] = self::itemList($canonical.'#latest-posts', $author->latestPostsTitle($locale), $canonical,
                $posts->map(fn (Post $post): array => [rtrim($post->publicUrl($locale), '/').'/', $post->displayTitle($locale)]));
        }

        $graph[] = self::breadcrumbs($canonical, [
            [__('author.team', [], $locale), $team],
            [$author->pageTitle($locale), $canonical],
        ], $locale);

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    /**
     * @param  list<array{title: string, text: string, authors: Collection<int, Author>, show_join: bool}>  $sections
     * @return array<string, mixed>
     */
    public static function team(string $canonical, string $name, string $description, array $sections, ?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();
        $site = self::site();
        $authors = collect($sections)->flatMap(fn (array $section) => $section['authors'])->values();

        $page = self::filled([
            '@type' => ['AboutPage', 'CollectionPage'],
            '@id' => $canonical.'#webpage',
            'url' => $canonical,
            'name' => $name,
            'description' => trim($description) ?: null,
            'inLanguage' => $locale,
            'isPartOf' => ['@id' => $site.'/#website'],
            'about' => ['@id' => $site.'/#organization'],
            'breadcrumb' => ['@id' => $canonical.'#breadcrumb'],
            'mainEntity' => $authors->isNotEmpty() ? ['@id' => $canonical.'#itemlist'] : null,
        ]);

        $graph = [PostSchema::organization(), self::website($locale), $page];

        if ($authors->isNotEmpty()) {
            $graph[] = [
                '@type' => 'ItemList',
                '@id' => $canonical.'#itemlist',
                'name' => $name,
                'numberOfItems' => $authors->count(),
                'itemListElement' => $authors->map(fn (Author $author, int $i): array => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'item' => self::filled([
                        '@type' => 'Person',
                        '@id' => rtrim($author->publicUrl($locale), '/').'/#person',
                        'name' => $author->displayName($locale),
                        'url' => rtrim($author->publicUrl($locale), '/').'/',
                        'image' => $author->avatarUrl(),
                        'jobTitle' => $author->positionLabel($locale),
                        'worksFor' => ['@id' => $site.'/#organization'],
                    ]),
                ])->all(),
            ];
        }

        $graph[] = self::breadcrumbs($canonical, [[__('author.team', [], $locale), $canonical]], $locale);

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    /**
     * @return array<string, mixed>
     */
    private static function website(string $locale): array
    {
        $site = self::site();

        return [
            '@type' => 'WebSite',
            '@id' => $site.'/#website',
            'name' => 'slots.tube',
            'url' => $site.'/',
            'inLanguage' => $locale,
            'publisher' => ['@id' => $site.'/#organization'],
        ];
    }

    /**
     * @param  Collection<int, array{0: string, 1: string}>  $items
     * @return array<string, mixed>
     */
    private static function itemList(string $id, string $name, string $canonical, Collection $items): array
    {
        return [
            '@type' => 'ItemList',
            '@id' => $id,
            'name' => $name,
            'isPartOf' => ['@id' => $canonical.'#webpage'],
            'numberOfItems' => $items->count(),
            'itemListElement' => $items->values()->map(fn (array $item, int $i): array => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'url' => $item[0],
                'name' => $item[1],
            ])->all(),
        ];
    }

    /**
     * @param  list<array{0: string, 1: string}>  $crumbs
     * @return array<string, mixed>
     */
    private static function breadcrumbs(string $canonical, array $crumbs, string $locale): array
    {
        $crumbs = [[__('slot.home', [], $locale), rtrim(localized_url($locale, '/'), '/').'/'], ...$crumbs];

        return [
            '@type' => 'BreadcrumbList',
            '@id' => $canonical.'#breadcrumb',
            'itemListElement' => array_map(fn (array $crumb, int $i): array => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb[0],
                'item' => $crumb[1],
            ], $crumbs, array_keys($crumbs)),
        ];
    }

    private static function teamUrl(string $locale): string
    {
        return rtrim(localized_url($locale, 'authors'), '/').'/';
    }

    private static function site(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function filled(array $data): array
    {
        return array_filter($data, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }
}
