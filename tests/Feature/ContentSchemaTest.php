<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Support\ContentSchema;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ContentSchemaTest extends TestCase
{
    private function makePost(int $id, string $slug, string $type = 'news'): Post
    {
        $post = new Post(['type' => $type, 'slug' => $slug, 'title' => ['en' => ucfirst($slug)]]);
        $post->id = $id;

        return $post;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function nodes(array $schema): array
    {
        return collect($schema['@graph'])->keyBy('@type')->all();
    }

    public function test_hub_lists_unique_posts_and_sections(): void
    {
        $canonical = url('/content').'/';
        $first = $this->makePost(1, 'first');
        $sections = collect([['type' => 'news', 'label' => 'News', 'url' => url('/content/news'), 'icon' => 'section-news']]);

        $nodes = $this->nodes(ContentSchema::hub($canonical, 'Slots.tube News', 'All publications', collect([$first, $this->makePost(2, 'second'), $first]), $sections, 'en'));

        $this->assertSame($canonical.'#itemlist', $nodes['CollectionPage']['mainEntity']['@id']);
        $this->assertSame(url('/content/news').'/', $nodes['CollectionPage']['hasPart'][0]['url']);
        $this->assertSame([url('/content/first').'/', url('/content/second').'/'], array_column($nodes['ItemList']['itemListElement'], 'url'));
        $this->assertSame([url('/').'/', $canonical], array_column($nodes['BreadcrumbList']['itemListElement'], 'item'));
    }

    public function test_section_positions_continue_across_pages(): void
    {
        $canonical = url('/content/news').'/?page=2';
        $paginator = new LengthAwarePaginator([$this->makePost(13, 'thirteenth'), $this->makePost(14, 'fourteenth')], 14, 12, 2);

        $nodes = $this->nodes(ContentSchema::section('news', $canonical, 'News — Page 2', 'Latest news', $paginator, 'en'));

        $this->assertSame([13, 14], array_column($nodes['ItemList']['itemListElement'], 'position'));
        $this->assertSame(url('/content').'/#webpage', $nodes['CollectionPage']['isPartOf']['@id']);
        $this->assertSame([url('/').'/', url('/content').'/', $canonical], array_column($nodes['BreadcrumbList']['itemListElement'], 'item'));
    }

    public function test_empty_section_has_no_item_list(): void
    {
        $nodes = $this->nodes(ContentSchema::section('guide', url('/content/guides').'/', 'Guides', '', new LengthAwarePaginator([], 0, 12, 1), 'en'));

        $this->assertArrayNotHasKey('ItemList', $nodes);
        $this->assertArrayNotHasKey('mainEntity', $nodes['CollectionPage']);
        $this->assertArrayNotHasKey('description', $nodes['CollectionPage']);
    }
}
