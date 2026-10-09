<?php

namespace Tests\Feature;

use App\Models\Bonus;
use App\Models\Country;
use App\Models\Post;
use App\Models\Slot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SiteSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Search relies on PostgreSQL JSON/ILIKE operators.');
        }

        Slot::create(['title' => ['en' => 'Zork Riches'], 'slug' => 'zork-riches', 'is_published' => true]);
        Slot::create(['title' => ['en' => 'Zork Hidden'], 'slug' => 'zork-hidden', 'is_published' => false]);
        Post::create(['title' => ['en' => 'Zork weekly news'], 'slug' => 'zork-weekly', 'type' => 'news', 'is_published' => true]);
        Post::create(['title' => ['en' => 'Zork streamer'], 'slug' => 'zork-streamer', 'type' => 'streamer', 'is_published' => true]);
        Post::create(['title' => ['en' => 'Zork draft'], 'slug' => 'zork-draft', 'type' => 'news', 'is_published' => false]);
        Bonus::create([
            'casino_name' => 'ZorkCasino', 'slug' => 'zork-casino', 'cta_url' => 'https://example.com',
            'short_text' => ['en' => '100 free spins'], 'is_published' => true, 'sort_order' => 1, 'countries' => ['ALL'],
        ]);
    }

    public function test_empty_query_shows_recommended_bonuses(): void
    {
        $this->get('/search')
            ->assertOk()
            ->assertSee('Recommended Bonuses')
            ->assertSee('ZorkCasino')
            ->assertDontSee('Zork Riches');
    }

    public function test_recommended_bonuses_follow_visitor_country_list(): void
    {
        $make = fn (string $name, bool $published = true) => Bonus::create([
            'casino_name' => $name, 'cta_url' => 'https://example.com', 'short_text' => ['en' => 'Offer'],
            'is_published' => $published, 'countries' => ['ALL'],
        ]);
        $maple = $make('MapleSpins');
        $north = $make('NorthBet');
        $hidden = $make('HiddenCasino', false);
        $world = $make('WorldWins');

        $canada = Country::where('code', 'CA')->firstOrFail();
        $canada->searchBonusItems()->createMany([
            ['bonus_id' => $north->id, 'sort_order' => 2],
            ['bonus_id' => $maple->id, 'sort_order' => 1],
            ['bonus_id' => $hidden->id, 'sort_order' => 3],
        ]);
        Country::where('code', 'ALL')->firstOrFail()->searchBonusItems()->create(['bonus_id' => $world->id]);

        $this->get('/search', ['CF-IPCountry' => 'CA'])
            ->assertOk()
            ->assertSeeInOrder(['MapleSpins', 'NorthBet'])
            ->assertDontSee('HiddenCasino')
            ->assertDontSee('WorldWins');

        $this->get('/search', ['CF-IPCountry' => 'JP'])
            ->assertOk()
            ->assertSee('WorldWins')
            ->assertDontSee('MapleSpins');

        $canada->update(['is_active' => false]);
        $this->get('/search', ['CF-IPCountry' => 'CA'])
            ->assertOk()
            ->assertSee('WorldWins')
            ->assertDontSee('MapleSpins');
    }

    public function test_country_options_come_from_countries_table(): void
    {
        Country::create(['code' => 'jp', 'name' => 'Japan', 'sort_order' => 99]);

        $options = Bonus::countryOptions();

        $this->assertSame('ALL', array_key_first($options));
        $this->assertSame('Japan', $options['JP']);
        $this->assertSame('Canada', $options['CA']);
    }

    public function test_query_matches_published_slots_posts_and_bonuses(): void
    {
        $this->get('/search?q=zork')
            ->assertOk()
            ->assertSee('Zork Riches')
            ->assertSee('Zork weekly news')
            ->assertSee('Zork streamer')
            ->assertSee('ZorkCasino')
            ->assertDontSee('Zork Hidden')
            ->assertDontSee('Zork draft');
    }

    public function test_type_filter_limits_groups(): void
    {
        $this->get('/search?q=zork&type=publications')
            ->assertOk()
            ->assertSee('Zork weekly news')
            ->assertDontSee('Zork streamer')
            ->assertDontSee('Zork Riches')
            ->assertDontSee('ZorkCasino');
    }

    public function test_like_wildcards_are_escaped(): void
    {
        $this->get('/search?q=%25%25')
            ->assertOk()
            ->assertDontSee('Zork Riches');
    }
}
