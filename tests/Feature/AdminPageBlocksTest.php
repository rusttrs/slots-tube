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
            ['type' => 'cards', 'data' => ['title' => 'How we pick', 'text' => 'Lead', 'columns' => 3, 'style' => 'outline', 'cards' => [
                ['title' => 'Checks', 'text' => '', 'items' => "One\nTwo"],
            ]]],
            ['type' => 'steps', 'data' => ['title' => 'How to claim', 'text' => '', 'items' => "Pick\nRegister", 'tip_title' => 'Heads up', 'tip_text' => 'Read the terms.']],
        ]]]);

        Livewire::test(EditPageSetting::class, ['record' => $page->getRouteKey()])
            ->assertOk()
            ->call('save')
            ->assertHasNoFormErrors();

        $blocks = $page->fresh()->blocksFor('en');
        $this->assertSame(['cards', 'steps'], array_column($blocks, 'type'));
        $this->assertSame(3, $blocks[0]['columns']);
        $this->assertSame('outline', $blocks[0]['style']);
        $this->assertSame(['One', 'Two'], $blocks[0]['cards'][0]['items']);
        $this->assertSame('Heads up', $blocks[1]['tip_title']);
    }

    public function test_pages_without_blocks_ignore_stored_blocks(): void
    {
        $page = PageSetting::for('authors');
        $page->update(['blocks' => ['en' => [['type' => 'text', 'data' => ['title' => 'Hidden']]]]]);

        $this->assertSame([], $page->fresh()->blocksFor('en'));
    }
}
