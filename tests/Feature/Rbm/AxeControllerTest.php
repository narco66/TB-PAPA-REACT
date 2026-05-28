<?php

namespace Tests\Feature\Rbm;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\Axe;
use App\Models\Papa;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AxeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pointFocal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $this->withoutMiddleware(EnforceTwoFactor::class);

        $this->admin = User::factory()->create(['actif' => true]);
        $this->admin->assignRole('admin_technique');

        $this->pointFocal = User::factory()->create(['actif' => true]);
        $this->pointFocal->assignRole('point_focal');
    }

    public function test_guest_est_redirige_vers_login(): void
    {
        $this->get('/rbm/axes')->assertRedirect('/login');
    }

    public function test_utilisateur_authentifie_peut_lister_les_axes(): void
    {
        Papa::factory()->valide()->create();
        Axe::factory()->count(3)->create();

        $this->actingAs($this->pointFocal)
            ->get('/rbm/axes')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('rbm/axes/index')->has('axes.data', 3));
    }

    public function test_admin_peut_creer_un_axe(): void
    {
        $papa = Papa::factory()->valide()->create();

        $this->actingAs($this->admin)
            ->post('/rbm/axes', [
                'papa_id' => $papa->id,
                'libelle' => 'Axe stratégique de test',
                'poids' => 100,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('axes', [
            'libelle' => 'Axe stratégique de test',
            'code' => 'AXE 1',
        ]);
    }

    public function test_point_focal_ne_peut_pas_creer_un_axe(): void
    {
        $papa = Papa::factory()->valide()->create();

        $this->actingAs($this->pointFocal)
            ->post('/rbm/axes', [
                'papa_id' => $papa->id,
                'libelle' => 'Test',
            ])
            ->assertForbidden();
    }

    public function test_validation_refuse_libelle_vide(): void
    {
        $papa = Papa::factory()->valide()->create();

        $this->actingAs($this->admin)
            ->from('/rbm/axes/create')
            ->post('/rbm/axes', ['papa_id' => $papa->id])
            ->assertSessionHasErrors('libelle');
    }

    public function test_validation_refuse_date_fin_avant_debut(): void
    {
        $papa = Papa::factory()->valide()->create();

        $this->actingAs($this->admin)
            ->from('/rbm/axes/create')
            ->post('/rbm/axes', [
                'papa_id' => $papa->id,
                'libelle' => 'Axe',
                'date_debut' => '2026-12-31',
                'date_fin' => '2026-01-01',
            ])
            ->assertSessionHasErrors('date_fin');
    }

    public function test_admin_peut_modifier_un_axe(): void
    {
        $axe = Axe::factory()->create();

        $this->actingAs($this->admin)
            ->put("/rbm/axes/{$axe->id}", [
                'papa_id' => $axe->papa_id,
                'libelle' => 'Libellé modifié',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('axes', ['id' => $axe->id, 'libelle' => 'Libellé modifié']);
    }

    public function test_admin_peut_supprimer_un_axe(): void
    {
        $axe = Axe::factory()->create();

        $this->actingAs($this->admin)
            ->delete("/rbm/axes/{$axe->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('axes', ['id' => $axe->id]);
    }
}
