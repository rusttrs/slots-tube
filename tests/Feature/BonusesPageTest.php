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

    public function test_empty_list_shows_placeholder(): void
    {
        $this->get('/bonuses/')->assertOk()->assertSee(__('bonus.empty'));
    }
}
