<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Migrations rely on PostgreSQL.');
        }
    }

    public function test_site_user_is_not_let_into_admin(): void
    {
        $this->actingAs(User::factory()->create(['password' => null]))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_opens_admin_and_deactivated_admin_does_not(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/admin')->assertOk();

        $this->assertFalse(User::factory()->admin()->create(['deactivated_at' => now()])->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_site_user_with_password_cannot_log_in_to_admin(): void
    {
        $user = User::factory()->create(['email' => 'visitor@example.com']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(Login::class)
            ->fillForm(['email' => 'visitor@example.com', 'password' => 'password'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_is_admin_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->create();
        $user->update(['is_admin' => true]);

        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_admin_grants_access_in_user_form_but_cannot_revoke_own(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['is_admin' => true])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertTrue($user->fresh()->is_admin);

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->fillForm(['is_admin' => false])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertTrue($admin->fresh()->is_admin);
    }

    public function test_command_grants_and_revokes_access(): void
    {
        $user = User::factory()->create(['email' => 'editor@example.com']);

        $this->artisan('user:admin', ['email' => 'Editor@Example.com'])->assertSuccessful();
        $this->assertTrue($user->fresh()->is_admin);

        $this->artisan('user:admin', ['email' => 'editor@example.com', '--revoke' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->is_admin);

        $this->artisan('user:admin', ['email' => 'nobody@example.com'])->assertFailed();
    }
}
