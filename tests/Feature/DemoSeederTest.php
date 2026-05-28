<?php

namespace Tests\Feature;

use App\Models\Activite;
use App\Models\Audit\AuditMission;
use App\Models\Audit\AuditPlan;
use App\Models\Axe;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetImport;
use App\Models\Budget\BudgetLigne;
use App\Models\Departement;
use App\Models\Direction;
use App\Models\GeneratedReport;
use App\Models\Indicateur;
use App\Models\Papa;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\Tache;
use App\Models\User;
use App\Models\ValeurIndicateur;
use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\TbpapaDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        app(TbpapaDemoSeeder::class)->run(2026, [
            'with_audit' => true,
            'with_imports' => true,
            'with_notifications' => true,
            'with_kpi_history' => true,
        ]);
    }

    public function test_volumes_minimums_atteints(): void
    {
        $this->assertGreaterThanOrEqual(30, User::where('email', 'like', 'demo.%@ceeac.int')->count());
        $this->assertEquals(7, Departement::where('code', 'like', 'DEMO-%')->count());
        $this->assertGreaterThanOrEqual(20, Direction::where('code', 'like', 'DEMO-%')->count());
        $this->assertEquals(10, Axe::where('code', 'like', 'DEMO-%')->count());
        $this->assertGreaterThanOrEqual(30, Produit::where('code', 'like', 'DEMO-%')->count());
        $this->assertGreaterThanOrEqual(60, SousProduit::where('code', 'like', 'DEMO-%')->count());
        $this->assertGreaterThanOrEqual(150, Activite::where('code', 'like', 'DEMO-%')->count());
        $this->assertGreaterThanOrEqual(500, Tache::where('code', 'like', 'DEMO-%')->count());
        $this->assertGreaterThanOrEqual(80, Indicateur::where('code', 'like', 'DEMO-%')->count());
        $this->assertGreaterThanOrEqual(100, BudgetLigne::where('observations', 'like', '%DEMO%')->count());
    }

    public function test_chaine_rbm_gar_coherente(): void
    {
        $papa = Papa::where('version', 'DEMO')->first();
        $this->assertNotNull($papa);

        Axe::where('papa_id', $papa->id)->where('code', 'like', 'DEMO-%')->get()
            ->each(fn ($axe) => $this->assertEquals($papa->id, $axe->papa_id));

        $produitOrphelin = Produit::where('code', 'like', 'DEMO-%')->whereNull('axe_id')->count();
        $this->assertEquals(0, $produitOrphelin, 'Aucun produit DEMO sans axe');

        $spOrphelin = SousProduit::where('code', 'like', 'DEMO-%')->whereNull('produit_id')->count();
        $this->assertEquals(0, $spOrphelin, 'Aucun sous-produit DEMO sans produit');

        $actOrphelin = Activite::where('code', 'like', 'DEMO-%')->whereNull('sous_produit_id')->count();
        $this->assertEquals(0, $actOrphelin, 'Aucune activité DEMO sans sous-produit');

        $tacheOrpheline = Tache::where('code', 'like', 'DEMO-%')->whereNull('activite_id')->count();
        $this->assertEquals(0, $tacheOrpheline, 'Aucune tâche DEMO sans activité');
    }

    public function test_budget_lignes_respectent_total_egal_ceeac_plus_ptf(): void
    {
        $erreurs = BudgetLigne::where('observations', 'like', '%DEMO%')
            ->get()
            ->filter(fn ($l) => (int) $l->montant_total !== (int) ($l->montant_ceeac_em + $l->montant_ptf))
            ->count();

        $this->assertEquals(0, $erreurs, 'Total = CEEAC-EM + PTF pour toutes les lignes DEMO');
    }

    public function test_indicateurs_rattaches_au_niveau_rbm(): void
    {
        $orphelins = Indicateur::where('code', 'like', 'DEMO-%')->whereNull('sous_produit_id')->count();
        $this->assertEquals(0, $orphelins);

        $valeurs = ValeurIndicateur::whereHas('indicateur', fn ($q) => $q->where('code', 'like', 'DEMO-%'))->count();
        $this->assertGreaterThan(0, $valeurs, 'Valeurs trimestrielles présentes pour les indicateurs DEMO');
    }

    public function test_exercices_budgetaires_multi_annees(): void
    {
        $exercices = BudgetExercice::where('libelle', 'like', 'DEMO-%')->pluck('annee')->sort()->values();
        $this->assertGreaterThanOrEqual(3, $exercices->count());
    }

    public function test_audit_interne_chaine_complete(): void
    {
        $plan = AuditPlan::where('libelle', 'like', 'DEMO-%')->first();
        $this->assertNotNull($plan);

        $missions = AuditMission::where('plan_id', $plan->id)->where('code', 'like', 'DEMO-%')->get();
        $this->assertGreaterThanOrEqual(4, $missions->count());

        $missions->each(function ($m) {
            $this->assertNotNull($m->chef_mission_id);
            $this->assertNotNull($m->lettre_mission);
            $this->assertGreaterThan(0, $m->constats()->count(), "Mission {$m->code} doit avoir au moins un constat");
        });
    }

    public function test_imports_simules_avec_erreurs(): void
    {
        $imports = BudgetImport::where('fichier_nom', 'like', 'DEMO-%')->get();
        $this->assertGreaterThanOrEqual(5, $imports->count());

        $statuts = $imports->pluck('statut')->unique()->values()->all();
        $this->assertGreaterThan(1, count($statuts), 'Plusieurs statuts d\'import doivent être représentés');
    }

    public function test_rapports_generes_disponibles(): void
    {
        $rapports = GeneratedReport::where('report_key', 'like', 'demo_%')->get();
        $this->assertGreaterThanOrEqual(5, $rapports->count());
        $rapports->each(fn ($r) => $this->assertNotNull($r->code_verification));
    }

    public function test_rollback_supprime_uniquement_donnees_demo(): void
    {
        $institutionnels = User::where('email', 'not like', 'demo.%@ceeac.int')->count();

        TbpapaDemoSeeder::rollbackDemo();

        $this->assertEquals(0, User::where('email', 'like', 'demo.%@ceeac.int')->count());
        $this->assertEquals(0, Axe::where('code', 'like', 'DEMO-%')->count());
        $this->assertEquals(0, Produit::where('code', 'like', 'DEMO-%')->count());
        $this->assertEquals(0, Papa::where('version', 'DEMO')->count());

        $this->assertEquals($institutionnels, User::where('email', 'not like', 'demo.%@ceeac.int')->count(),
            'Les utilisateurs non-DEMO ne doivent pas être affectés.');
    }

    public function test_commande_artisan_idempotente(): void
    {
        $axesAvant = Axe::where('code', 'like', 'DEMO-%')->count();
        $tachesAvant = Tache::where('code', 'like', 'DEMO-%')->count();

        $this->artisan('tbpapa:seed-demo', ['--append' => true])->assertSuccessful();

        $this->assertEquals($axesAvant, Axe::where('code', 'like', 'DEMO-%')->count(), 'Aucun doublon créé sur ré-exécution');
        $this->assertEquals($tachesAvant, Tache::where('code', 'like', 'DEMO-%')->count());
    }
}
