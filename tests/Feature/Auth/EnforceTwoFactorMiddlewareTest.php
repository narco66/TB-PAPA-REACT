<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnforceTwoFactorMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_role_sensible_sans_2fa_est_redirige_vers_setup(): void
    {
        $user = User::factory()->create(['actif' => true]);
        $user->assignRole('president');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('two-factor.setup'));
    }

    public function test_role_sensible_avec_2fa_active_a_acces_normal(): void
    {
        $user = User::factory()->create(['actif' => true]);
        $user->assignRole('president');

        app(TwoFactorService::class)->generate($user);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_role_non_sensible_nest_pas_force_a_activer_la_2fa(): void
    {
        $user = User::factory()->create(['actif' => true]);
        $user->assignRole('point_focal');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_route_setup_2fa_reste_accessible_meme_sans_2fa_active(): void
    {
        $user = User::factory()->create(['actif' => true]);
        $user->assignRole('commissaire');

        $this->actingAs($user)
            ->get('/settings/two-factor')
            ->assertOk();
    }
}
