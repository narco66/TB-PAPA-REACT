<?php

namespace Tests\Feature\Papa;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\Papa;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PapaControllerTest extends TestCase
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

    public function test_listing_papas(): void
    {
        Papa::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get('/papa')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('papa/index')->has('papas.data', 3));
    }

    public function test_creation_papa_valide(): void
    {
        $this->actingAs($this->admin)
            ->post('/papa', [
                'annee' => 2027,
                'version' => '1.0',
                'libelle' => 'PAPA 2027 de test',
                'date_debut' => '2027-01-01',
                'date_fin' => '2027-12-31',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('papas', [
            'annee' => 2027,
            'libelle' => 'PAPA 2027 de test',
            'statut' => Papa::STATUT_BROUILLON,
        ]);
    }

    public function test_creation_refuse_annee_invalide(): void
    {
        $this->actingAs($this->admin)
            ->from('/papa/create')
            ->post('/papa', [
                'annee' => 1999,
                'version' => '1.0',
                'libelle' => 'PAPA',
            ])
            ->assertSessionHasErrors('annee');
    }

    public function test_workflow_soumission_validation_cloture(): void
    {
        $papa = Papa::factory()->create();
        $this->assertSame(Papa::STATUT_BROUILLON, $papa->statut);

        $this->actingAs($this->admin)->post("/papa/{$papa->id}/workflow/submit");
        $papa->refresh();
        $this->assertSame(Papa::STATUT_EN_VALIDATION, $papa->statut);

        $this->actingAs($this->admin)->post("/papa/{$papa->id}/workflow/approve");
        $papa->refresh();
        $this->assertSame(Papa::STATUT_VALIDE, $papa->statut);
        $this->assertNotNull($papa->date_validation);

        $this->actingAs($this->admin)->post("/papa/{$papa->id}/workflow/close");
        $papa->refresh();
        $this->assertSame(Papa::STATUT_CLOTURE, $papa->statut);
        $this->assertTrue($papa->verrouille);
        $this->assertNotNull($papa->cloture_le);
    }

    public function test_show_inclut_les_axes_et_taux_execution(): void
    {
        $papa = Papa::factory()->valide()->create();

        $this->actingAs($this->admin)
            ->get("/papa/{$papa->id}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('papa/show')
                ->has('papa.taux_execution_physique')
                ->has('papa.taux_execution_financier'),
            );
    }
}
