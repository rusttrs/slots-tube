<?php

namespace Tests\Feature;

use App\Filament\Resources\PageSettings\Pages\EditPageSetting;
use App\Models\PageSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPageBlocksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Migrations rely on PostgreSQL.');
        }

        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_description_blocks_survive_a_save_from_the_admin(): void
    {
        $page = PageSetting::for('bonuses');
        $page->update(['blocks' => ['en' => [
            ['type' => 'section', 'data' => ['title' => 'How we pick', 'pills' => "Licensed\nFast"]],
            ['type' => 'cards', 'data' => ['columns' => 3, 'style' => 'outline', 'cards' => [
                ['title' => 'Checks', 'text' => '', 'items' => "One\nTwo"],
            ]]],
            ['type' => 'steps', 'data' => ['items' => "Pick\nRegister"]],
            ['type' => 'tip', 'data' => ['title' => 'Heads up', 'text' => 'Read the terms.']],
            ['type' => 'guides', 'data' => ['heading' => 'Guides', 'columns' => 2, 'cards' => [['title' => 'Card', 'url' => '/content/guides/']]]],
        ]]]);

        Livewire::test(EditPageSetting::class, ['record' => $page->getRouteKey()])
            ->assertOk()
            ->call('save')
            ->assertHasNoFormErrors();

        $sections = $page->fresh()->blocksFor('en');
        $this->assertCount(1, $sections);
        $this->assertSame('How we pick', $sections[0]['title']);
        $this->assertSame(['Licensed', 'Fast'], $sections[0]['pills']);
        $elements = $sections[0]['elements'];
        $this->assertSame(['cards', 'steps', 'tip', 'guides'], array_column($elements, 'type'));
        $this->assertSame(3, $elements[0]['columns']);
        $this->assertSame('outline', $elements[0]['style']);
        $this->assertSame(['One', 'Two'], $elements[0]['cards'][0]['items']);
        $this->assertSame('Heads up', $elements[2]['title']);
        $this->assertSame(2, $elements[3]['columns']);
    }

    public function test_pages_without_blocks_ignore_stored_blocks(): void
    {
        $page = PageSetting::for('authors');
        $page->update(['blocks' => ['en' => [['type' => 'section', 'data' => ['title' => 'Hidden']]]]]);

        $this->assertSame([], $page->fresh()->blocksFor('en'));
    }
}
