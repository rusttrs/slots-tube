<?php

namespace Tests\Feature;

use App\Http\Middleware\CanonicalUrl;
use Tests\TestCase;

class NotFoundPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The test client strips trailing slashes, so the canonical redirect would never reach the 404.
        $this->withoutMiddleware(CanonicalUrl::class);
    }

    public function test_unknown_url_renders_branded_404(): void
    {
        $this->get('/definitely-missing-page')
            ->assertNotFound()
            ->assertSee('Page Not Found')
            ->assertSee('Go back Home')
            ->assertSee('/assets/images/404/digits.png', false)
            ->assertSee('href="'.localized_url('en', '').'"', false);
    }

    public function test_404_uses_locale_from_url_prefix(): void
    {
        $this->get('/de/definitely-missing-page')
            ->assertNotFound()
            ->assertSee('<html lang="de">', false)
            ->assertSee('Seite nicht gefunden')
            ->assertSee('href="'.localized_url('de', '').'"', false);

        $this->get('/fr/definitely-missing-page')
            ->assertNotFound()
            ->assertSee('Page introuvable');
    }

    public function test_json_404_is_not_html(): void
    {
        $this->getJson('/definitely-missing-page')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }
}
