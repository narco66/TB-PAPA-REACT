<?php

namespace Tests\Feature\Audit;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\Audit\AuditConstat;
use App\Models\Audit\AuditMission;
use App\Models\Audit\AuditPlan;
use App\Models\Audit\AuditRecommandation;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditInterneTest extends TestCase
{
    use RefreshDatabase;

    protected User $auditeur;

    protected User $autre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $this->withoutMiddleware(EnforceTwoFactor::class);

        $this->auditeur = User::factory()->create(['actif' => true]);
        $this->auditeur->assignRole('audit_interne');

        $this->autre = User::factory()->create(['actif' => true]);
        $this->autre->assignRole('partenaire');
    }

    public function test_dashboard_accessible_aux_auditeurs(): void
    {
        $this->actingAs($this->auditeur)->get('/audit')->assertOk();
    }

    public function test_dashboard_refuse_aux_non_auditeurs(): void
    {
        $this->actingAs($this->autre)->get('/audit')->assertForbidden();
    }

    public function test_creation_plan_annuel(): void
    {
        $response = $this->actingAs($this->auditeur)->post('/audit/plans', [
            'annee' => 2027,
            'libelle' => "Plan d'audit 2027",
            'description' => 'Programmation des missions annuelles',
            'orientation_strategique' => 'Audits de conformité prioritaires',
            'date_debut' => '2027-01-01',
            'date_fin' => '2027-12-31',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('audit_plans', [
            'annee' => 2027,
            'statut' => 'projet',
            'created_by' => $this->auditeur->id,
        ]);
    }

    public function test_annee_plan_unique(): void
    {
        AuditPlan::create([
            'annee' => 2027,
            'libelle' => 'Existant',
            'statut' => 'projet',
            'created_by' => $this->auditeur->id,
        ]);

        $this->actingAs($this->auditeur)->post('/audit/plans', [
            'annee' => 2027,
            'libelle' => 'Doublon',
        ])->assertSessionHasErrors('annee');
    }

    public function test_creation_mission_avec_equipe(): void
    {
        $plan = AuditPlan::create([
            'annee' => 2027, 'libelle' => 'P 2027', 'statut' => 'valide',
            'created_by' => $this->auditeur->id,
        ]);

        $membre = User::factory()->create(['actif' => true]);

        $response = $this->actingAs($this->auditeur)->post('/audit/missions', [
            'plan_id' => $plan->id,
            'code' => 'M-2027-001',
            'titre' => 'Audit conformité commande publique',
            'type' => 'conformite',
            'priorite' => 'haute',
            'chef_mission_id' => $this->auditeur->id,
            'objectifs' => 'Vérifier la conformité des procédures',
            'date_debut_prevue' => '2027-03-01',
            'date_fin_prevue' => '2027-04-30',
            'equipe' => [
                ['user_id' => $membre->id, 'role_mission' => 'auditeur_senior'],
            ],
        ]);

        $response->assertRedirect();
        $mission = AuditMission::where('code', 'M-2027-001')->first();
        $this->assertNotNull($mission);
        $this->assertCount(1, $mission->equipe);
        $this->assertEquals('auditeur_senior', $mission->equipe->first()->pivot->role_mission);
    }

    public function test_creation_constat_dans_mission(): void
    {
        $mission = $this->createMission();

        $response = $this->actingAs($this->auditeur)->post('/audit/constats', [
            'mission_id' => $mission->id,
            'code' => 'C-001',
            'libelle' => 'Absence de contrôle hiérarchique',
            'description' => 'Les bons de commande ne sont pas systématiquement validés.',
            'gravite' => 'majeur',
            'nature' => 'controle_insuffisant',
            'cause_racine' => 'Délégation de signature non formalisée',
            'impact' => 'Risque financier et de fraude',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('audit_constats', [
            'mission_id' => $mission->id, 'code' => 'C-001', 'gravite' => 'majeur',
        ]);
    }

    public function test_code_constat_unique_par_mission(): void
    {
        $mission = $this->createMission();
        AuditConstat::create([
            'mission_id' => $mission->id, 'code' => 'C-001',
            'libelle' => 'Premier', 'description' => 'X',
            'gravite' => 'moyen', 'nature' => 'non_conformite',
            'saisi_par_id' => $this->auditeur->id,
        ]);

        $this->actingAs($this->auditeur)->post('/audit/constats', [
            'mission_id' => $mission->id,
            'code' => 'C-001',
            'libelle' => 'Doublon',
            'description' => 'Y',
            'gravite' => 'moyen',
            'nature' => 'non_conformite',
        ])->assertSessionHasErrors('code');
    }

    public function test_creation_recommandation_et_suivi(): void
    {
        $mission = $this->createMission();
        $constat = AuditConstat::create([
            'mission_id' => $mission->id, 'code' => 'C-001',
            'libelle' => 'Test', 'description' => 'X',
            'gravite' => 'majeur', 'nature' => 'controle_insuffisant',
            'saisi_par_id' => $this->auditeur->id,
        ]);

        $this->actingAs($this->auditeur)->post('/audit/recommandations', [
            'constat_id' => $constat->id,
            'code' => 'R-001',
            'libelle' => 'Formaliser les délégations',
            'action_proposee' => 'Mettre en place un registre des délégations de signature',
            'priorite' => 'haute',
            'date_echeance' => '2027-06-30',
        ])->assertRedirect();

        $reco = AuditRecommandation::where('code', 'R-001')->first();
        $this->assertNotNull($reco);

        $this->actingAs($this->auditeur)->post('/audit/suivis', [
            'recommandation_id' => $reco->id,
            'date_suivi' => '2027-05-15',
            'etat_avancement' => 'en_cours',
            'pourcentage' => 40,
            'actions_realisees' => 'Projet de note rédigé',
            'actions_restantes' => 'Validation Direction',
        ])->assertRedirect();

        $reco->refresh();
        $this->assertEquals(40, $reco->pourcentage_avancement);
        $this->assertEquals('en_cours', $reco->statut);
        $this->assertDatabaseCount('audit_suivis_recommandations', 1);
    }

    public function test_suivi_realise_passe_recommandation_en_mise_en_oeuvre(): void
    {
        $reco = $this->createRecommandation();

        $this->actingAs($this->auditeur)->post('/audit/suivis', [
            'recommandation_id' => $reco->id,
            'date_suivi' => '2027-08-01',
            'etat_avancement' => 'realise',
            'pourcentage' => 100,
        ])->assertRedirect();

        $reco->refresh();
        $this->assertEquals('mise_en_oeuvre', $reco->statut);
        $this->assertEquals(100, $reco->pourcentage_avancement);
    }

    public function test_validation_plan_reserve_au_role_validateur(): void
    {
        $plan = AuditPlan::create([
            'annee' => 2027, 'libelle' => 'P', 'statut' => 'soumis',
            'created_by' => $this->auditeur->id,
        ]);

        $this->actingAs($this->autre)->post("/audit/plans/{$plan->id}/valider")
            ->assertForbidden();
    }

    public function test_listes_publiques_aux_utilisateurs_avec_droit_view(): void
    {
        $this->actingAs($this->auditeur)->get('/audit/plans')->assertOk();
        $this->actingAs($this->auditeur)->get('/audit/missions')->assertOk();
        $this->actingAs($this->auditeur)->get('/audit/recommandations')->assertOk();
    }

    public function test_est_en_retard_recommandation(): void
    {
        $reco = AuditRecommandation::create([
            'constat_id' => $this->createConstat()->id,
            'code' => 'R-LATE',
            'libelle' => 'Retard',
            'action_proposee' => 'X',
            'priorite' => 'haute',
            'date_echeance' => now()->subDays(10),
            'statut' => 'en_cours',
            'saisi_par_id' => $this->auditeur->id,
        ]);

        $this->assertTrue($reco->estEnRetard());
    }

    // === Helpers ===

    protected function createMission(): AuditMission
    {
        $plan = AuditPlan::create([
            'annee' => 2027, 'libelle' => 'P', 'statut' => 'valide',
            'created_by' => $this->auditeur->id,
        ]);

        return AuditMission::create([
            'plan_id' => $plan->id,
            'code' => 'M-2027-T' . uniqid(),
            'titre' => 'Mission test',
            'type' => 'conformite',
            'priorite' => 'moyenne',
            'statut' => 'planifiee',
            'created_by' => $this->auditeur->id,
        ]);
    }

    protected function createConstat(): AuditConstat
    {
        return AuditConstat::create([
            'mission_id' => $this->createMission()->id,
            'code' => 'C-T' . uniqid(),
            'libelle' => 'Test',
            'description' => 'X',
            'gravite' => 'moyen',
            'nature' => 'non_conformite',
            'saisi_par_id' => $this->auditeur->id,
        ]);
    }

    protected function createRecommandation(): AuditRecommandation
    {
        return AuditRecommandation::create([
            'constat_id' => $this->createConstat()->id,
            'code' => 'R-T' . uniqid(),
            'libelle' => 'Test',
            'action_proposee' => 'X',
            'priorite' => 'moyenne',
            'statut' => 'ouverte',
            'saisi_par_id' => $this->auditeur->id,
        ]);
    }
}
