<?php

namespace App\Support;

use App\Models\Author;
use App\Models\Post;
use Carbon\CarbonInterface;

/**
 * schema.org JSON-LD graph for a publication page (/content/{slug}/).
 * Ids line up with the author page (#person) and the slot pages (/#organization).
 */
class PostSchema
{
    private const TYPES = [
        'news' => 'NewsArticle',
        'blog' => 'BlogPosting',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function build(Post $post, string $canonical, int $commentsCount = 0, ?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();
        $site = rtrim((string) config('app.url'), '/');
        $title = $post->displayTitle($locale);
        $description = trim(strip_tags($post->displayExcerpt($locale)));
        $cover = $post->coverUrl();
        $published = $post->created_at;
        $modified = self::modifiedAt($post);
        $section = __('content.sections.'.$post->type, [], $locale);
        $body = trim(strip_tags($post->renderedBody($locale)));

        $article = self::compact([
            '@type' => self::TYPES[$post->type] ?? 'Article',
            '@id' => $canonical.'#article',
            'headline' => mb_strimwidth($title, 0, 110, '…'),
            'description' => $description ?: null,
            'image' => $cover ? [$cover] : null,
            'datePublished' => $published?->toIso8601String(),
            'dateModified' => ($modified ?: $published)?->toIso8601String(),
            'author' => self::person($post->author, $locale) ?? ['@id' => $site.'/#organization'],
            'editor' => $modified ? self::person($post->updatedByAuthor, $locale) : null,
            'publisher' => ['@id' => $site.'/#organization'],
            'mainEntityOfPage' => ['@id' => $canonical.'#webpage'],
            'isPartOf' => ['@id' => $canonical.'#webpage'],
            'articleSection' => $section,
            'inLanguage' => $locale,
            'wordCount' => $body !== '' ? count(preg_split('/\s+/u', $body)) : null,
            'about' => $post->provider
                ? ['@type' => 'Organization', 'name' => $post->provider->getTranslation('name', $locale) ?: $post->provider->getTranslation('name', 'en')]
                : null,
            'commentCount' => $commentsCount,
            'interactionStatistic' => [
                ['@type' => 'InteractionCounter', 'interactionType' => 'https://schema.org/LikeAction', 'userInteractionCount' => (int) $post->likes_count],
                ['@type' => 'InteractionCounter', 'interactionType' => 'https://schema.org/CommentAction', 'userInteractionCount' => $commentsCount],
            ],
            'isAccessibleForFree' => true,
        ]);

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $site.'/#organization',
                    'name' => 'slots.tube',
                    'url' => $site.'/',
                    'logo' => $site.'/assets/icons/logo-7.svg',
                ],
                self::compact([
                    '@type' => 'WebPage',
                    '@id' => $canonical.'#webpage',
                    'url' => $canonical,
                    'name' => $title,
                    'description' => $description ?: null,
                    'inLanguage' => $locale,
                    'primaryImageOfPage' => $cover ? ['@type' => 'ImageObject', 'url' => $cover] : null,
                    'datePublished' => $published?->toIso8601String(),
                    'dateModified' => ($modified ?: $published)?->toIso8601String(),
                    'breadcrumb' => ['@id' => $canonical.'#breadcrumb'],
                    'mainEntity' => ['@id' => $canonical.'#article'],
                ]),
                $article,
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => $canonical.'#breadcrumb',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => __('slot.home', [], $locale), 'item' => rtrim(localized_url($locale, '/'), '/').'/'],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => __('content.title', [], $locale).' '.__('content.title_accent', [], $locale), 'item' => rtrim(localized_url($locale, 'content'), '/').'/'],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $section, 'item' => rtrim($post->sectionUrl($locale), '/').'/'],
                        ['@type' => 'ListItem', 'position' => 4, 'name' => $title, 'item' => $canonical],
                    ],
                ],
            ],
        ];
    }

    /**
     * The update date is stored without time; never let it precede the publication moment.
     */
    public static function modifiedAt(Post $post): ?CarbonInterface
    {
        if (! $post->showsContentUpdate()) {
            return null;
        }

        $published = $post->created_at;

        return $published && $post->content_updated_on->lt($published) ? $published : $post->content_updated_on;
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

        return self::compact([
            '@type' => 'Person',
            '@id' => $url ? $url.'#person' : null,
            'name' => $author->displayName($locale),
            'url' => $url,
            'image' => $author->avatarUrl(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function compact(array $data): array
    {
        return array_filter($data, fn ($value) => $value !== null && $value !== '');
    }
}
