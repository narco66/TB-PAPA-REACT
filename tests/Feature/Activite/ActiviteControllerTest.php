<?php

namespace Tests\Feature\Activite;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\Activite;
use App\Models\SousProduit;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiviteControllerTest extends TestCase
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

    public function test_listing_active(): void
    {
        Activite::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get('/activites')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('activites/index'));
    }

    public function test_creation_avec_donnees_valides(): void
    {
        $sp = SousProduit::factory()->create();

        $this->actingAs($this->admin)
            ->post('/activites', [
                'sous_produit_id' => $sp->id,
                'libelle' => 'Activité de test',
                'date_debut' => '2026-01-01',
                'date_fin' => '2026-06-30',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activites', [
            'libelle' => 'Activité de test',
            'statut' => 'planifiee',
        ]);
    }

    public function test_creation_refuse_sans_dates(): void
    {
        $sp = SousProduit::factory()->create();

        $this->actingAs($this->admin)
            ->from('/activites/create')
            ->post('/activites', [
                'sous_produit_id' => $sp->id,
                'libelle' => 'Activité',
            ])
            ->assertSessionHasErrors(['date_debut', 'date_fin']);
    }

    public function test_update_avancement(): void
    {
        $activite = Activite::factory()->create([
            'statut' => 'planifiee',
            'taux_execution' => 0,
        ]);

        $this->actingAs($this->admin)
            ->post("/activites/{$activite->id}/avancement", [
                'taux_execution' => 50,
                'statut' => 'en_cours',
                'commentaire' => 'Mi-parcours.',
            ])
            ->assertRedirect();

        $activite->refresh();
        $this->assertEquals(50, $activite->taux_execution);
        $this->assertSame('en_cours', $activite->statut);
        $this->assertNotNull($activite->date_debut_reelle);
    }

    public function test_update_avancement_refuse_taux_hors_borne(): void
    {
        $activite = Activite::factory()->create();

        $this->actingAs($this->admin)
            ->from('/activites/' . $activite->id)
            ->post("/activites/{$activite->id}/avancement", [
                'taux_execution' => 150,
                'statut' => 'en_cours',
            ])
            ->assertSessionHasErrors('taux_execution');
    }
}
