<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_a_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@ceeac.org',
            'password' => Hash::make('Password@2026'),
            'actif' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'test@ceeac.org',
            'password' => 'Password@2026',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'test@ceeac.org',
            'password' => Hash::make('Password@2026'),
            'actif' => true,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'test@ceeac.org',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactif@ceeac.org',
            'password' => Hash::make('Password@2026'),
            'actif' => false,
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'inactif@ceeac.org',
            'password' => 'Password@2026',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create(['actif' => true]);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_protected_routes_redirect_unauthenticated(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/papa')->assertRedirect('/login');
        $this->get('/rbm/axes')->assertRedirect('/login');
    }
}
