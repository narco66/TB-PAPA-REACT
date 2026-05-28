<?php

namespace Tests\Feature\Rbm;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\Activite;
use App\Models\Tache;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TacheControllerTest extends TestCase
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

    public function test_creation_tache_genere_code_5_niveaux(): void
    {
        $activite = Activite::factory()->create();

        $this->actingAs($this->admin)
            ->post('/rbm/taches', [
                'activite_id' => $activite->id,
                'libelle' => 'Tâche test',
            ])
            ->assertRedirect();

        $tache = Tache::where('libelle', 'Tâche test')->first();
        $this->assertNotNull($tache);
        $this->assertMatchesRegularExpression('/^T\.\d+\.\d+\.\d+\.\d+\.\d+$/', $tache->code);
    }

    public function test_listing_taches(): void
    {
        Tache::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get('/rbm/taches')
            ->assertOk();
    }

    public function test_update_tache(): void
    {
        $tache = Tache::factory()->create(['taux_execution' => 0]);

        $this->actingAs($this->admin)
            ->put("/rbm/taches/{$tache->id}", [
                'activite_id' => $tache->activite_id,
                'libelle' => $tache->libelle,
                'taux_execution' => 75,
                'statut' => 'en_cours',
            ])
            ->assertRedirect();

        $tache->refresh();
        $this->assertEquals(75, $tache->taux_execution);
    }
}
