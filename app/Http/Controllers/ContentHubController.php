<?php

namespace App\Http\Controllers;

use App\Models\PageSetting;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ContentHubController extends Controller
{
    private const PER_PAGE = 12;

    private const SECTION_SLUGS = [
        'news' => 'news',
        'blog' => 'blogs',
        'guide' => 'guides',
        'streamer' => 'streamers',
    ];

    public function index(): View
    {
        $base = fn (): Builder => Post::query()
            ->where('is_published', true)
            ->with('author');

        $top = $base()->where('is_featured', true)->latest('created_at')->limit(2)->get();
        if ($top->count() < 2) {
            $top = $top->merge(
                $base()->whereNotIn('id', $top->modelKeys())->latest('created_at')->limit(2 - $top->count())->get()
            );
        }

        $popular = $base()
            ->whereNotIn('id', $top->modelKeys())
            ->orderByDesc('likes_count')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('content.hub', [
            'top' => $top,
            'popular' => $popular,
            'latest' => $base()->latest('created_at')->limit(6)->get(),
            'guides' => $base()->where('type', 'guide')->latest('created_at')->limit(3)->get(),
            'sections' => $this->sections(),
            'page' => PageSetting::for('content'),
            'canonical' => rtrim(localized_url(null, 'content'), '/').'/',
        ]);
    }

    public function section(Request $request): View
    {
        $section = (string) $request->route('section');
        $type = array_search($section, self::SECTION_SLUGS, true);
        abort_if($type === false, 404);

        $posts = Post::query()
            ->where('is_published', true)
            ->where('type', $type)
            ->with('author')
            ->latest('created_at')
            ->paginate(self::PER_PAGE)
            ->withPath(rtrim(localized_url(null, 'content/'.$section), '/').'/');

        abort_if($posts->currentPage() > 1 && $posts->isEmpty(), 404);

        $canonical = $posts->currentPage() > 1 ? $posts->url($posts->currentPage()) : $posts->path();

        return view('content.section', [
            'type' => $type,
            'posts' => $posts,
            'sections' => $this->sections(),
            'page' => PageSetting::for('content.'.$section),
            'canonical' => $canonical,
        ]);
    }

    /**
     * @return Collection<int, array{type: string, label: string, url: string, icon: string}>
     */
    private function sections(): Collection
    {
        $icons = [
            'news' => 'section-news',
            'blog' => 'section-blogs',
            'guide' => 'section-guides',
            'streamer' => 'section-streamers',
        ];

        return collect(array_keys(Post::typeOptions()))->map(fn (string $type): array => [
            'type' => $type,
            'label' => __('content.sections.'.$type),
            'url' => (new Post(['type' => $type]))->sectionUrl(),
            'icon' => $icons[$type] ?? 'section-news',
        ]);
    }
}
