<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Support\PostBody;
use Tests\TestCase;

class PostPublicationTest extends TestCase
{
    public function test_public_url_is_under_content(): void
    {
        $post = new Post(['type' => 'streamer', 'slug' => 'ace-high']);

        $this->assertSame(url('/content/ace-high').'/', $post->publicUrl('en'));
        $this->assertSame(url('/content/streamers').'/', $post->sectionUrl('en'));

        $post->type = 'news';
        $this->assertSame(url('/content/ace-high').'/', $post->publicUrl('en'));
        $this->assertSame(url('/content/news').'/', $post->sectionUrl('en'));

        $post->type = 'blog';
        $this->assertSame(url('/content/blogs').'/', $post->sectionUrl('en'));

        $post->type = 'guide';
        $this->assertSame(url('/content/guides').'/', $post->sectionUrl('en'));
    }

    public function test_back_label_follows_section_type(): void
    {
        $post = new Post(['type' => 'news']);
        $this->assertSame('Back to news', $post->backLabel());

        $post->type = 'blog';
        $this->assertSame('Back to blogs', $post->backLabel());

        $post->type = 'guide';
        $this->assertSame('Back to guides', $post->backLabel());

        $post->type = 'streamer';
        $this->assertSame('Back to streamers', $post->backLabel());
    }

    public function test_content_show_route_is_named(): void
    {
        $this->assertSame(url('/content/hello'), route('content.show', 'hello'));
        $this->assertSame(url('/content/news'), route('news'));
        $this->assertSame(url('/content/blogs'), route('blogs'));
        $this->assertSame(url('/content/guides'), route('guides'));
        $this->assertSame(url('/content/streamers'), route('streamers'));
    }

    public function test_image_alt_becomes_caption(): void
    {
        $html = PostBody::render('<p>Hello</p><img src="https://example.com/a.jpg" alt="Jackpot cash" data-id="posts/body/a.jpg">');

        $this->assertStringContainsString('<figcaption>Jackpot cash</figcaption>', $html);
        $this->assertStringContainsString('posts/body/a.jpg', $html);
    }
}
