<?php

namespace Tests\Feature;

use App\Http\Middleware\CanonicalUrl;
use Tests\TestCase;

class CanonicalUrlTest extends TestCase
{
    public function test_pages_redirect_to_lowercase_with_trailing_slash_in_one_hop(): void
    {
        $this->get('/slots/book-of-dead')->assertStatus(301)->assertHeader('Location', url('/slots/book-of-dead').'/');
        $this->get('/Slots/Book-Of-Dead')->assertStatus(301)->assertHeader('Location', url('/slots/book-of-dead').'/');
        $this->get('/Slots/Book-Of-Dead/')->assertStatus(301)->assertHeader('Location', url('/slots/book-of-dead').'/');
        $this->get('/DE/Authors')->assertStatus(301)->assertHeader('Location', url('/de/authors').'/');
        $this->get('/de')->assertStatus(301)->assertHeader('Location', url('/de').'/');
    }

    public function test_query_string_is_kept_as_is(): void
    {
        $this->get('/Content/News?page=2&Sort=New')
            ->assertStatus(301)
            ->assertHeader('Location', url('/content/news').'/?page=2&Sort=New');
    }

    public function test_canonical_and_service_urls_are_not_redirected(): void
    {
        foreach (['/', '/authors/', '/de/slots/book-of-dead/', '/search', '/up', '/admin/login', '/auth/magic/1', '/newsletter/unsubscribe/AbC', '/robots.txt', '/storage/Avatars/A.png'] as $path) {
            $this->assertSame($path, CanonicalUrl::canonicalPath($path), $path);
        }
    }

    public function test_post_requests_are_not_redirected(): void
    {
        $this->assertNotSame(301, $this->post('/Profile/Username')->getStatusCode());
        $this->assertNotSame(301, $this->post('/profile')->getStatusCode());
    }

    public function test_localized_url_builds_canonical_links(): void
    {
        $this->assertSame(url('/'), localized_url('en', ''));
        $this->assertSame(url('/de').'/', localized_url('de', ''));
        $this->assertSame(url('/authors').'/', localized_url('en', 'authors'));
        $this->assertSame(url('/fr/slots/x').'/', localized_url('fr', 'slots/x/'));
        $this->assertSame(url('/search'), localized_url('en', 'search'));
        $this->assertSame(url('/de/search'), localized_url('de', 'search'));
    }

    public function test_legacy_section_urls_keep_locale(): void
    {
        $this->get('/news/')->assertStatus(301)->assertHeader('Location', url('/content/news').'/');
        $this->get('/de/news/')->assertStatus(301)->assertHeader('Location', url('/de/content/news').'/');
    }
}
