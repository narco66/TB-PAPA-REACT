<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $this->withoutMiddleware(EnforceTwoFactor::class);

        $this->admin = User::factory()->create(['actif' => true]);
        $this->admin->assignRole('admin_technique');
    }

    public function test_admin_peut_lister_les_utilisateurs(): void
    {
        User::factory()->count(3)->create(['actif' => true]);

        $this->actingAs($this->admin)
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_creation_user_avec_mot_de_passe_fort(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/users', [
                'name' => 'Jane Doe',
                'email' => 'jane.doe@ceeac.org',
                'password' => 'Strong@Password2026',
                'password_confirmation' => 'Strong@Password2026',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'jane.doe@ceeac.org']);
    }

    public function test_creation_refuse_mot_de_passe_faible(): void
    {
        $this->actingAs($this->admin)
            ->from('/admin/users/create')
            ->post('/admin/users', [
                'name' => 'Test',
                'email' => 'test@ceeac.org',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_creation_refuse_email_duplique(): void
    {
        User::factory()->create(['email' => 'duplique@ceeac.org']);

        $this->actingAs($this->admin)
            ->from('/admin/users/create')
            ->post('/admin/users', [
                'name' => 'Test',
                'email' => 'duplique@ceeac.org',
                'password' => 'Strong@Password2026',
                'password_confirmation' => 'Strong@Password2026',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_destroy_desactive_lutilisateur(): void
    {
        $cible = User::factory()->create(['actif' => true]);

        $this->actingAs($this->admin)
            ->delete("/admin/users/{$cible->id}");

        $cible->refresh();
        $this->assertFalse($cible->actif);
    }
}
