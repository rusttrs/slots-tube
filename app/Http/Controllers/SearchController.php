<?php

namespace App\Http\Controllers;

use App\Models\Bonus;
use App\Models\Country;
use App\Models\Post;
use App\Models\Slot;
use App\Support\VisitorCountry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Живой поиск в оверлее шапки / нижнего меню: отдаёт HTML-фрагмент со списком результатов.
 */
class SearchController extends Controller
{
    public const TYPES = ['all', 'slots', 'streamers', 'publications', 'bonuses'];

    private const MIN_QUERY = 2;

    public function __invoke(Request $request): View
    {
        $query = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $type = in_array($request->query('type'), self::TYPES, true) ? (string) $request->query('type') : 'all';
        $country = VisitorCountry::code($request);

        if (mb_strlen($query) < self::MIN_QUERY) {
            $recommended = Country::recommendedBonuses($country);
            if ($recommended->isEmpty()) {
                $recommended = $this->bonuses($country)->take(Country::SEARCH_BONUS_LIMIT)->values();
            }

            return view('partials.site-search-results', [
                'query' => '',
                'recommended' => $recommended,
                'groups' => [],
            ]);
        }

        $single = $type !== 'all';
        $limit = $single ? 12 : 6;
        $locale = app()->getLocale();
        $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query).'%';

        $groups = [];

        if (in_array($type, ['all', 'slots'], true)) {
            $slots = Slot::query()
                ->published()
                ->where(fn (Builder $q) => $this->matchTitle($q, $term, $locale))
                ->with('provider')
                ->orderBy('slug');
            $groups['slots'] = $this->group(__('search.groups.slots'), $slots, $limit, fn (Slot $slot): array => [
                'url' => rtrim($slot->publicUrl(), '/').'/',
                'title' => $slot->displayTitle(),
                'meta' => $slot->provider?->displayName() ?? __('search.groups.slots'),
                'image' => $slot->coverUrl(),
                'icon' => 'nav-slots',
            ]);
        }

        foreach (['streamers' => ['streamer'], 'publications' => ['news', 'blog', 'guide']] as $key => $postTypes) {
            if (! in_array($type, ['all', $key], true)) {
                continue;
            }
            $posts = Post::query()
                ->where('is_published', true)
                ->whereIn('type', $postTypes)
                ->where(fn (Builder $q) => $this->matchTitle($q, $term, $locale))
                ->latest('created_at');
            $groups[$key] = $this->group(__('search.groups.'.$key), $posts, $limit, fn (Post $post): array => [
                'url' => rtrim($post->publicUrl(), '/').'/',
                'title' => $post->displayTitle(),
                'meta' => __('content.sections.'.$post->type),
                'image' => $post->coverUrl(),
                'icon' => 'meta-news',
            ]);
        }

        if (in_array($type, ['all', 'bonuses'], true)) {
            $bonuses = $this->bonuses($country, $term, $locale);
            $groups['bonuses'] = [
                'label' => __('search.groups.bonuses'),
                'total' => $bonuses->count(),
                'bonuses' => $bonuses->take($single ? 9 : 3)->values(),
            ];
        }

        return view('partials.site-search-results', [
            'query' => $query,
            'recommended' => collect(),
            'groups' => array_filter($groups, fn (array $group): bool => $group['total'] > 0),
        ]);
    }

    private function matchTitle(Builder $query, string $term, string $locale): void
    {
        foreach (array_unique([$locale, 'en']) as $titleLocale) {
            $query->orWhereRaw("title->>? ILIKE ? ESCAPE '\\'", [$titleLocale, $term]);
        }
        $query->orWhere('slug', 'ilike', $term);
    }

    /**
     * @param  callable(mixed): array{url: string, title: string, meta: string, image: ?string, icon: string}  $map
     * @return array{label: string, total: int, items: Collection<int, array<string, mixed>>}
     */
    private function group(string $label, Builder $query, int $limit, callable $map): array
    {
        $total = (clone $query)->toBase()->getCountForPagination();

        return [
            'label' => $label,
            'total' => $total,
            'items' => $total > 0 ? $query->limit($limit)->get()->map($map) : collect(),
        ];
    }

    /**
     * @return Collection<int, Bonus>
     */
    private function bonuses(string $country, ?string $term = null, ?string $locale = null): Collection
    {
        $bonuses = Bonus::query()
            ->where('is_published', true)
            ->when($term !== null, fn (Builder $q) => $q->where(function (Builder $q) use ($term, $locale): void {
                $q->where('casino_name', 'ilike', $term)
                    ->orWhere('extra_text', 'ilike', $term);
                foreach (array_unique([(string) $locale, 'en']) as $textLocale) {
                    $q->orWhereRaw("title->>? ILIKE ? ESCAPE '\\'", [$textLocale, $term])
                        ->orWhereRaw("short_text->>? ILIKE ? ESCAPE '\\'", [$textLocale, $term]);
                }
            }))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return Bonus::forVisitorCountry($bonuses, $country);
    }
}
