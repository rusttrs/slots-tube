<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Post;
use App\Support\PostSchema;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PostSchemaTest extends TestCase
{
    private function makePost(string $type, array $attributes = []): Post
    {
        $post = new Post(array_merge([
            'type' => $type,
            'slug' => 'new-slot-releases',
            'title' => ['en' => 'New slot releases this week'],
            'excerpt' => ['en' => 'Fresh launches and demos.'],
            'body' => ['en' => '<p>Colt Lightning is a high volatility slot.</p>'],
        ], $attributes));
        $post->created_at = Carbon::parse('2026-10-08 10:00:00', 'UTC');
        $post->likes_count = 4;

        $author = new Author(['slug' => 'elena-voss', 'name' => ['en' => 'Elena Voss'], 'is_published' => true]);
        $post->setRelation('author', $author);
        $post->setRelation('updatedByAuthor', null);
        $post->setRelation('provider', null);

        return $post;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function nodes(array $schema): array
    {
        return collect($schema['@graph'])->keyBy('@type')->all();
    }

    public function test_article_type_follows_section(): void
    {
        $canonical = url('/content/new-slot-releases').'/';

        $this->assertArrayHasKey('NewsArticle', $this->nodes(PostSchema::build($this->makePost('news'), $canonical, 0, 'en')));
        $this->assertArrayHasKey('BlogPosting', $this->nodes(PostSchema::build($this->makePost('blog'), $canonical, 0, 'en')));
        $this->assertArrayHasKey('Article', $this->nodes(PostSchema::build($this->makePost('guide'), $canonical, 0, 'en')));
        $this->assertArrayHasKey('Article', $this->nodes(PostSchema::build($this->makePost('streamer'), $canonical, 0, 'en')));
    }

    public function test_article_links_author_page_and_breadcrumbs(): void
    {
        $canonical = url('/content/new-slot-releases').'/';
        $nodes = $this->nodes(PostSchema::build($this->makePost('news'), $canonical, 3, 'en'));
        $article = $nodes['NewsArticle'];

        $this->assertSame('New slot releases this week', $article['headline']);
        $this->assertSame('Elena Voss', $article['author']['name']);
        $this->assertSame(url('/authors/elena-voss').'/#person', $article['author']['@id']);
        $this->assertSame('2026-10-08T10:00:00+00:00', $article['datePublished']);
        $this->assertSame($article['datePublished'], $article['dateModified']);
        $this->assertArrayNotHasKey('editor', $article);
        $this->assertSame(3, $article['commentCount']);
        $this->assertSame(7, $article['wordCount']);

        $crumbs = array_column($nodes['BreadcrumbList']['itemListElement'], 'item');
        $this->assertSame([url('/').'/', url('/content').'/', url('/content/news').'/', $canonical], $crumbs);
    }

    public function test_update_date_and_editor_come_from_content_update(): void
    {
        $post = $this->makePost('guide', ['content_updated_on' => '2026-10-09']);
        $post->setRelation('updatedByAuthor', new Author(['slug' => 'marcus-hale', 'name' => ['en' => 'Marcus Hale'], 'is_published' => false]));

        $article = $this->nodes(PostSchema::build($post, url('/content/x/'), 0, 'en'))['Article'];

        $this->assertStringStartsWith('2026-10-09', $article['dateModified']);
        $this->assertSame(['@type' => 'Person', 'name' => 'Marcus Hale'], $article['editor']);
    }

    public function test_same_day_update_never_precedes_publication(): void
    {
        $post = $this->makePost('news', ['content_updated_on' => '2026-10-08']);

        $article = $this->nodes(PostSchema::build($post, url('/content/x/'), 0, 'en'))['NewsArticle'];

        $this->assertSame($article['datePublished'], $article['dateModified']);
    }
}
