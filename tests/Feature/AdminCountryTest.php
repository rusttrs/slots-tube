<?php

namespace Tests\Feature;

use App\Filament\Resources\Countries\Pages\CreateCountry;
use App\Filament\Resources\Countries\Pages\EditCountry;
use App\Models\Bonus;
use App\Models\Country;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCountryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Migrations rely on PostgreSQL.');
        }

        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function bonus(string $name): Bonus
    {
        return Bonus::create(['casino_name' => $name, 'cta_url' => 'https://example.com', 'is_published' => true, 'countries' => ['ALL']]);
    }

    public function test_country_is_created_with_ordered_search_bonuses(): void
    {
        $first = $this->bonus('First');
        $second = $this->bonus('Second');

        Livewire::test(CreateCountry::class)
            ->fillForm([
                'code' => 'JP',
                'name' => 'Japan',
                'searchBonusItems' => [['bonus_id' => $second->id], ['bonus_id' => $first->id]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $japan = Country::where('code', 'JP')->firstOrFail();
        $this->assertSame([$second->id, $first->id], $japan->searchBonuses->pluck('id')->all());
    }

    public function test_code_must_be_unique_and_valid(): void
    {
        Livewire::test(CreateCountry::class)
            ->fillForm(['code' => 'CA', 'name' => 'Canada again'])
            ->call('create')
            ->assertHasFormErrors(['code' => 'unique']);

        Livewire::test(CreateCountry::class)
            ->fillForm(['code' => 'Canada', 'name' => 'Canada'])
            ->call('create')
            ->assertHasFormErrors(['code']);
    }

    public function test_search_bonuses_are_limited_to_three(): void
    {
        $canada = Country::where('code', 'CA')->firstOrFail();
        $items = collect(['A', 'B', 'C', 'D'])->map(fn (string $name) => ['bonus_id' => $this->bonus($name)->id])->all();

        Livewire::test(EditCountry::class, ['record' => $canada->getRouteKey()])
            ->fillForm(['searchBonusItems' => $items])
            ->call('save')
            ->assertHasFormErrors(['searchBonusItems']);

        Livewire::test(EditCountry::class, ['record' => $canada->getRouteKey()])
            ->fillForm(['searchBonusItems' => array_slice($items, 0, 3)])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(3, $canada->searchBonuses()->count());
    }
}
