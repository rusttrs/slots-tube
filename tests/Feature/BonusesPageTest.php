<?php

namespace Tests\Feature;

use App\Models\Bonus;
use App\Models\PageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BonusesPageTest extends TestCase
{
    use RefreshDatabase;

    private function bonus(string $name, array $attributes = []): Bonus
    {
        return Bonus::create($attributes + [
            'casino_name' => $name,
            'short_text' => ['en' => '100% Bonus up to €1000', 'de' => '100% Bonus bis 1000 €'],
            'extra_text' => '+ 200 Free Spins',
            'cta_url' => 'https://example.com/go',
            'is_published' => true,
            'countries' => ['ALL'],
        ]);
    }

    public function test_page_lists_published_bonuses_in_admin_order(): void
    {
        $this->bonus('Second Casino', ['sort_order' => 2]);
        $this->bonus('First Casino', ['sort_order' => 1, 'website_url' => 'https://first.example', 'features' => ['live_chat', 'bogus'], 'no_kyc' => true]);
        $this->bonus('Hidden Casino', ['is_published' => false]);

        $this->get('/bonuses/')
            ->assertOk()
            ->assertSee('Top Online Casinos 2026')
            ->assertSeeInOrder(['First Casino', 'Second Casino'])
            ->assertSee('100% Bonus up to €1000 + 200 Free Spins')
            ->assertSee('https://first.example')
            ->assertSee('Live chat')
            ->assertDontSee('VIP program')
            ->assertSee('No KYC!')
            ->assertDontSee('Hidden Casino');
    }

    public function test_country_bonuses_are_listed_together_with_all_countries(): void
    {
        $this->bonus('World Casino', ['sort_order' => 1]);
        $this->bonus('Canada Casino', ['countries' => ['CA', 'US'], 'sort_order' => 2]);
        $this->bonus('German Casino', ['countries' => ['DE'], 'sort_order' => 3]);

        $this->get('/bonuses/?country=CA')
            ->assertSeeInOrder(['World Casino', 'Canada Casino'])
            ->assertDontSee('German Casino');
        $this->get('/bonuses/?country=FR')
            ->assertSee('World Casino')
            ->assertDontSee('Canada Casino')
            ->assertDontSee('German Casino');
    }

    public function test_texts_seo_and_faq_come_from_page_settings(): void
    {
        PageSetting::for('bonuses')->update([
            'texts' => ['de' => ['hero_title' => 'Top-Casinos']],
            'meta_title' => ['en' => 'Custom bonuses title'],
            'faq' => ['en' => [['question' => 'Is it free?', 'answer' => 'Yes.']]],
        ]);
        $this->bonus('Any Casino');

        $this->get('/bonuses/')
            ->assertSee('<title>Custom bonuses title</title>', false)
            ->assertSee('Is it free?');
        $this->get('/de/bonuses/')
            ->assertOk()
            ->assertSee('Top-Casinos')
            ->assertSee('100% Bonus bis 1000 € + 200 Free Spins')
            ->assertSee('Bonus holen');
    }

    public function test_description_blocks_render_with_safe_inline_markup_and_english_fallback(): void
    {
        $b = fn (string $type, array $data = []): array => ['type' => $type, 'data' => $data];
        PageSetting::for('bonuses')->update(['blocks' => ['en' => [
            $b('section', ['title' => 'How we pick']),
            $b('text', ['lead' => true, 'text' => 'See [our guides](/content/guides/) <script>alert(1)</script>']),
            $b('cards', ['columns' => 3, 'style' => 'outline', 'cards' => [['title' => 'Checks', 'text' => '', 'items' => "**Licence:** verified\nPayout speed"]]]),
            $b('section', ['title' => 'How to claim', 'hero_image' => 'page-about/games.webp', 'pills' => "Licensed\nFast payouts"]),
            $b('steps', ['items' => "Pick an offer\n\nRegister"]),
            $b('tip', ['title' => 'Heads up', 'text' => 'Read the terms.']),
            $b('section', ['title' => 'Guides']),
            $b('image', ['image' => 'page-about/promo.webp', 'alt' => 'Promo banner', 'style' => 'promo']),
            $b('feature', ['title' => 'Casino does not pay?', 'url' => '/content/guides/']),
            $b('articles', ['items' => [['title' => 'What is RTP?', 'label' => 'Guide', 'url' => '/content/guides/']]]),
            $b('guides', ['heading' => 'First group', 'columns' => 2, 'cards' => [['title' => 'Guide card', 'url' => '/content/guides/']]]),
            $b('guides', ['heading' => 'Second group', 'cards' => [['title' => 'Another card']]]),
            $b('guides_link', ['label' => 'Read All Guides', 'url' => '/content/guides/']),
            $b('unknown', ['title' => 'Ignored block']),
        ]]]);

        $response = $this->get('/bonuses/')->assertOk()
            ->assertSeeInOrder(['How we pick', 'How to claim', 'Guides', 'First group', 'Second group', 'Read All Guides'])
            ->assertSee('page-about__lead', false)
            ->assertSee('page-about__grid--3', false)
            ->assertSee('page-about__card--outline', false)
            ->assertSee('<strong>Licence:</strong> verified', false)
            ->assertSee('<a class="page-about__link" href="/content/guides/">our guides</a>', false)
            ->assertSee('page-about page-about--games page-about--games-trusted', false)
            ->assertSee('page-about/games.webp', false)
            ->assertSee('page-about__pill', false)
            ->assertSee('page-about__promo-img', false)
            ->assertSee('about-feature', false)
            ->assertSee('about-article__label', false)
            ->assertSee('page-about__guide-grid--2', false)
            ->assertSeeInOrder(['>01<', '>02<'], false)
            ->assertSee('page-about page-about--guides', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('Ignored block');
        $this->assertSame(2, substr_count($response->getContent(), 'page-about__step-num'));

        $this->get('/de/bonuses/')->assertOk()->assertSee('How we pick');
    }

    public function test_schema_org_lists_offers_and_breadcrumbs(): void
    {
        $this->bonus('LeoVegas Casino', ['website_url' => 'https://leovegas.example', 'sort_order' => 1]);
        $this->bonus('Canada Casino', ['countries' => ['CA'], 'sort_order' => 2]);
        PageSetting::for('bonuses')->update(['faq' => ['en' => [['question' => 'Is it free?', 'answer' => 'Yes.']]]]);

        $html = $this->get('/bonuses/?country=CA')->assertOk()->getContent();
        preg_match_all('#<script type="application/ld\+json">(.+?)</script>#s', $html, $matches);
        $schemas = array_map(fn (string $json): array => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $matches[1]);

        $graph = collect($schemas)->firstWhere('@graph')['@graph'];
        $types = array_column($graph, '@type');
        $this->assertSame(['Organization', 'CollectionPage', 'ItemList', 'BreadcrumbList'], $types);

        $offers = $graph[2]['itemListElement'];
        $this->assertCount(2, $offers);
        $this->assertSame('Offer', $offers[0]['item']['@type']);
        $this->assertSame('100% Bonus up to €1000 + 200 Free Spins', $offers[0]['item']['name']);
        $this->assertSame('https://leovegas.example', $offers[0]['item']['offeredBy']['url']);
        $this->assertArrayNotHasKey('eligibleRegion', $offers[0]['item']);
        $this->assertSame('CA', $offers[1]['item']['eligibleRegion'][0]['identifier']);
        $this->assertSame('Bonuses', $graph[3]['itemListElement'][1]['name']);

        $this->assertContains('FAQPage', array_column($schemas, '@type'));
    }

    public function test_empty_list_shows_placeholder(): void
    {
        $this->get('/bonuses/')->assertOk()->assertSee(__('bonus.empty'));
    }
}
