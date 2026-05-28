<?php

namespace Tests\Feature\Papa;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\Papa;
use App\Models\User;
use App\Models\Validation;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PapaWorkflowTest extends TestCase
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

    public function test_submit_cree_une_entree_validation_avec_commentaire(): void
    {
        $papa = Papa::factory()->create();

        $this->actingAs($this->admin)
            ->post("/papa/{$papa->id}/workflow/submit", ['commentaire' => 'Pour examen'])
            ->assertRedirect();

        $this->assertDatabaseHas('validations', [
            'validable_id' => $papa->id,
            'validable_type' => Papa::class,
            'etape' => 'soumission',
            'commentaire' => 'Pour examen',
        ]);
        $papa->refresh();
        $this->assertSame(Papa::STATUT_EN_VALIDATION, $papa->statut);
    }

    public function test_reject_renvoie_au_statut_brouillon_et_enregistre_decision_rejete(): void
    {
        $papa = Papa::factory()->create();
        $this->actingAs($this->admin)->post("/papa/{$papa->id}/workflow/submit");

        $this->actingAs($this->admin)
            ->post("/papa/{$papa->id}/workflow/reject", ['commentaire' => 'Manque la section 3'])
            ->assertRedirect();

        $papa->refresh();
        $this->assertSame(Papa::STATUT_BROUILLON, $papa->statut);

        $derniereValidation = Validation::latest('id')->first();
        $this->assertSame('rejete', $derniereValidation->decision);
        $this->assertSame('Manque la section 3', $derniereValidation->commentaire);
    }

    public function test_transition_interdite_depuis_letat_courant_est_refusee(): void
    {
        $papa = Papa::factory()->create();

        $this->actingAs($this->admin)
            ->from("/papa/{$papa->id}")
            ->post("/papa/{$papa->id}/workflow/approve")
            ->assertSessionHasErrors('workflow');

        $papa->refresh();
        $this->assertSame(Papa::STATUT_BROUILLON, $papa->statut);
    }

    public function test_cycle_complet_brouillon_validation_revise_resubmit_valide(): void
    {
        $papa = Papa::factory()->create();

        $this->actingAs($this->admin)->post("/papa/{$papa->id}/workflow/submit");
        $this->actingAs($this->admin)->post("/papa/{$papa->id}/workflow/approve");
        $this->actingAs($this->admin)->post("/papa/{$papa->id}/workflow/revise");

        $papa->refresh();
        $this->assertSame(Papa::STATUT_REVISE, $papa->statut);

        $this->actingAs($this->admin)->post("/papa/{$papa->id}/workflow/resubmit");
        $this->actingAs($this->admin)->post("/papa/{$papa->id}/workflow/approve");

        $papa->refresh();
        $this->assertSame(Papa::STATUT_VALIDE, $papa->statut);
        $this->assertSame(5, Validation::where('validable_id', $papa->id)->count());
    }

    public function test_archive_apres_cloture_verrouille_definitivement(): void
    {
        $papa = Papa::factory()->create();

        $this->actingAs($this->admin)->post("/papa/{$papa->id}/workflow/submit");
        $this->actingAs($this->admin)->post("/papa/{$papa->id}/workflow/approve");
        $this->actingAs($this->admin)->post("/papa/{$papa->id}/workflow/close");
        $this->actingAs($this->admin)->post("/papa/{$papa->id}/workflow/archive");

        $papa->refresh();
        $this->assertSame(Papa::STATUT_ARCHIVE, $papa->statut);
        $this->assertTrue($papa->verrouille);
    }

    public function test_show_inclut_les_transitions_disponibles_et_lhistorique(): void
    {
        $papa = Papa::factory()->create();
        $this->actingAs($this->admin)->post("/papa/{$papa->id}/workflow/submit", ['commentaire' => 'Test']);

        $this->actingAs($this->admin)
            ->get("/papa/{$papa->id}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('papa/show')
                ->has('transitions')
                ->has('historique', 1)
                ->where('historique.0.commentaire', 'Test'),
            );
    }

    public function test_utilisateur_sans_permission_ne_peut_pas_transitionner(): void
    {
        $papa = Papa::factory()->create();
        $pointFocal = User::factory()->create(['actif' => true]);
        $pointFocal->assignRole('point_focal');

        $this->actingAs($pointFocal)
            ->from("/papa/{$papa->id}")
            ->post("/papa/{$papa->id}/workflow/submit")
            ->assertSessionHasErrors('workflow');

        $papa->refresh();
        $this->assertSame(Papa::STATUT_BROUILLON, $papa->statut);
    }
}
