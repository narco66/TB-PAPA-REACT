<?php

namespace Tests\Feature\Budget;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetImport;
use App\Models\Budget\BudgetImportErreur;
use App\Models\User;
use App\Services\Budget\Import\ImportErrorReportXlsxService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ImportErrorReportXlsxTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected BudgetExercice $exercice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $this->withoutMiddleware(EnforceTwoFactor::class);

        $this->admin = User::factory()->create(['actif' => true]);
        $this->admin->assignRole('admin_technique');

        $this->exercice = BudgetExercice::create([
            'annee' => 2027,
            'libelle' => 'Exercice 2027',
            'statut' => 'valide',
            'date_ouverture' => now(),
        ]);
    }

    public function test_genere_un_xlsx_avec_synthese_et_erreurs(): void
    {
        $import = BudgetImport::create([
            'exercice_id' => $this->exercice->id,
            'fichier_nom' => 'test.xlsx',
            'statut' => 'echec',
            'type_donnees' => 'multi',
            'execute_par_id' => $this->admin->id,
            'execute_at' => now(),
            'nb_erreurs' => 3,
        ]);

        BudgetImportErreur::create([
            'import_id' => $import->id,
            'feuille' => 'Axes',
            'ligne' => 2,
            'colonne' => 'libelle',
            'gravite' => 'erreur',
            'regle' => 'valeur_obligatoire',
            'message' => 'Libellé manquant',
        ]);
        BudgetImportErreur::create([
            'import_id' => $import->id,
            'feuille' => 'Produits',
            'ligne' => 5,
            'colonne' => 'code_axe',
            'gravite' => 'critique',
            'regle' => 'reference_inexistante',
            'message' => 'Axe inconnu',
        ]);
        BudgetImportErreur::create([
            'import_id' => $import->id,
            'feuille' => 'Indicateurs',
            'ligne' => 10,
            'colonne' => 'cible',
            'gravite' => 'avertissement',
            'regle' => 'format_invalide',
            'message' => 'Cible vide',
        ]);

        $chemin = app(ImportErrorReportXlsxService::class)->genererPour($import);

        $this->assertFileExists($chemin);

        $spreadsheet = IOFactory::load($chemin);
        $noms = $spreadsheet->getSheetNames();
        $this->assertContains('Synthèse', $noms);
        $this->assertContains('Erreurs détaillées', $noms);

        $erreursSheet = $spreadsheet->getSheetByName('Erreurs détaillées');
        $this->assertNotNull($erreursSheet);
        $this->assertSame('Feuille', $erreursSheet->getCell('A1')->getValue());

        // 3 erreurs + 1 ligne d'en-tête = 4 lignes
        $highest = $erreursSheet->getHighestDataRow();
        $this->assertSame(4, $highest);

        @unlink($chemin);
    }

    public function test_endpoint_download_xlsx(): void
    {
        $import = BudgetImport::create([
            'exercice_id' => $this->exercice->id,
            'fichier_nom' => 'test.xlsx',
            'statut' => 'echec',
            'type_donnees' => 'multi',
            'execute_par_id' => $this->admin->id,
            'execute_at' => now(),
            'nb_erreurs' => 1,
        ]);
        BudgetImportErreur::create([
            'import_id' => $import->id,
            'feuille' => 'Axes',
            'ligne' => 1,
            'colonne' => 'code',
            'gravite' => 'erreur',
            'regle' => 'valeur_obligatoire',
            'message' => 'Test',
        ]);

        $this->actingAs($this->admin)
            ->get("/budget/imports/{$import->id}/rapport-erreurs.xlsx")
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
