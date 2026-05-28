<?php

use App\Http\Controllers\Activite\ActiviteController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\DepartementController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AlerteController;
use App\Http\Controllers\Audit\AuditInterneController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Budget\BudgetCycleController;
use App\Http\Controllers\Expense\ExpenseRequestController;
use App\Http\Controllers\Expense\ExpenseDashboardController;
use App\Http\Controllers\Expense\ExpenseExportController;
use App\Http\Controllers\Expense\ServiceFaitController;
use App\Http\Controllers\Expense\SupplierController;
use App\Http\Controllers\Budget\BudgetDashboardController;
use App\Http\Controllers\Budget\BudgetExerciceController;
use App\Http\Controllers\Budget\BudgetImportExportController;
use App\Http\Controllers\Budget\BudgetLigneController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Document\DocumentController;
use App\Http\Controllers\Indicateur\IndicateurController;
use App\Http\Controllers\Papa\PapaController;
use App\Http\Controllers\Rbm\AxeController;
use App\Http\Controllers\Rbm\ProduitController;
use App\Http\Controllers\Rbm\SousProduitController;
use App\Http\Controllers\Rbm\TacheController;
use App\Http\Controllers\Reporting\ReportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// === Page d'accueil publique (vitrine institutionnelle) ===
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('welcome');
})->name('welcome');

// === Pages publiques des modules (descriptifs détaillés) ===
Route::prefix('modules')->name('modules.')->group(function () {
    foreach (['papa', 'rbm', 'gantt', 'indicateurs', 'budget', 'audit', 'ged', 'reporting'] as $slug) {
        Route::get($slug, fn () => Inertia::render("modules/{$slug}"))->name($slug);
    }
});

// === Auth ===
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
    Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'store']);
});

Route::middleware(['auth', 'enforce.2fa'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // === Réglages 2FA ===
    Route::prefix('settings/two-factor')->name('two-factor.')->group(function () {
        Route::get('/', [TwoFactorController::class, 'index'])->name('setup');
        Route::post('enable', [TwoFactorController::class, 'enable'])->name('enable');
        Route::post('confirm', [TwoFactorController::class, 'confirm'])->name('confirm');
        Route::delete('/', [TwoFactorController::class, 'disable'])->name('disable');
        Route::post('recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('recovery.regenerate');
    });

    Route::get('home', fn () => redirect()->route('dashboard'))->name('home');
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // === PAPA (référentiel annuel racine) + Workflow institutionnel ===
    Route::resource('papa', PapaController::class)->parameters(['papa' => 'papa']);
    Route::post('papa/{papa}/workflow/{action}', [PapaController::class, 'transition'])
        ->where('action', 'submit|resubmit|approve|reject|revise|close|archive')
        ->name('papa.workflow');

    // === Chaîne RBM/GAR CEEAC : Axe → Produit → SousProduit → Activité → Tâche ===
    Route::prefix('rbm')->name('rbm.')->group(function () {
        Route::resource('axes', AxeController::class);
        Route::resource('produits', ProduitController::class);
        Route::resource('sous-produits', SousProduitController::class)->parameters(['sous-produits' => 'sousProduit']);
        Route::resource('taches', TacheController::class)->parameters(['taches' => 'tache']);
    });

    // === Activités + Gantt (niveau 4 de la chaîne RBM) ===
    Route::get('activites/gantt', [ActiviteController::class, 'gantt'])->name('activites.gantt');
    Route::resource('activites', ActiviteController::class)
        ->parameters(['activites' => 'activite'])
        ->except(['edit', 'update', 'destroy']);
    Route::post('activites/{activite}/avancement', [ActiviteController::class, 'updateAvancement'])
        ->name('activites.avancement');

    // === Indicateurs (KPI rattachés au niveau Sous-Produit) ===
    Route::resource('indicateurs', IndicateurController::class)
        ->parameters(['indicateurs' => 'indicateur'])
        ->except(['edit', 'update', 'destroy']);
    Route::post('indicateurs/{indicateur}/valeurs', [IndicateurController::class, 'storeValeur'])
        ->name('indicateurs.valeurs.store');

    // === GED Documents ===
    Route::resource('documents', DocumentController::class)
        ->parameters(['documents' => 'document'])
        ->only(['index', 'create', 'store', 'destroy']);
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::post('documents/{document}/valider', [DocumentController::class, 'valider'])->name('documents.valider');

    // === Alertes ===
    Route::get('alertes', [AlerteController::class, 'index'])->name('alertes.index');
    Route::post('alertes/{alerte}/resolve', [AlerteController::class, 'resolve'])->name('alertes.resolve');
    Route::post('alertes/detecter', [AlerteController::class, 'detecter'])->name('alertes.detecter');

    // === Budget institutionnel CEEAC ===
    Route::prefix('budget')->name('budget.')->group(function () {
        Route::get('/', BudgetDashboardController::class)->name('dashboard');
        Route::resource('exercices', BudgetExerciceController::class)
            ->parameters(['exercices' => 'exercice']);
        Route::post('exercices/{exercice}/valider', [BudgetExerciceController::class, 'valider'])->name('exercices.valider');
        Route::post('exercices/{exercice}/cloturer', [BudgetExerciceController::class, 'cloturer'])->name('exercices.cloturer');

        Route::resource('lignes', BudgetLigneController::class)
            ->parameters(['lignes' => 'ligne']);

        // === Cycle IPSAS : Engagement → Liquidation → Ordonnancement → Paiement ===
        Route::post('lignes/{ligne}/engager', [BudgetCycleController::class, 'engager'])->name('cycle.engager');
        Route::post('mouvements/{mouvement}/liquider', [BudgetCycleController::class, 'liquider'])->name('cycle.liquider');
        Route::post('mouvements/{mouvement}/ordonnancer', [BudgetCycleController::class, 'ordonnancer'])->name('cycle.ordonnancer');
        Route::post('mouvements/{mouvement}/payer', [BudgetCycleController::class, 'payer'])->name('cycle.payer');

        Route::get('imports', [BudgetImportExportController::class, 'indexImport'])->name('imports.index');
        Route::get('imports/modele', [BudgetImportExportController::class, 'modele'])->name('imports.modele');
        Route::post('imports/analyser', [BudgetImportExportController::class, 'analyser'])->name('imports.analyser');
        Route::post('imports/multi-feuilles', [BudgetImportExportController::class, 'executerMultiFeuilles'])->name('imports.multi-feuilles');
        Route::post('imports/mappings', [BudgetImportExportController::class, 'saveMapping'])->name('imports.mappings.store');
        Route::delete('imports/mappings/{mapping}', [BudgetImportExportController::class, 'deleteMapping'])->name('imports.mappings.delete');
        Route::get('imports/{import}/rapport-erreurs.xlsx', [BudgetImportExportController::class, 'rapportErreursXlsx'])->name('imports.rapport-erreurs.xlsx');
        Route::get('imports/{import}/rapport-erreurs', [BudgetImportExportController::class, 'rapportErreurs'])->name('imports.rapport-erreurs');
        Route::post('imports/{import}/rollback', [BudgetImportExportController::class, 'rollback'])->name('imports.rollback');
        Route::get('imports/{import}/statut', [BudgetImportExportController::class, 'statut'])->name('imports.statut');
        Route::post('imports', [BudgetImportExportController::class, 'executerImport'])->name('imports.execute');
        Route::get('exports', [BudgetImportExportController::class, 'exporter'])->name('exports.execute');
    });

    // === Audit interne IGS (IIA/IFACI/ISO 19011/COSO) ===
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/', [AuditInterneController::class, 'dashboard'])->name('dashboard');

        Route::get('plans', [AuditInterneController::class, 'plansIndex'])->name('plans.index');
        Route::get('plans/create', [AuditInterneController::class, 'plansCreate'])->name('plans.create');
        Route::post('plans', [AuditInterneController::class, 'plansStore'])->name('plans.store');
        Route::get('plans/{plan}', [AuditInterneController::class, 'plansShow'])->name('plans.show');
        Route::post('plans/{plan}/valider', [AuditInterneController::class, 'plansValider'])->name('plans.valider');

        Route::get('missions', [AuditInterneController::class, 'missionsIndex'])->name('missions.index');
        Route::get('missions/create', [AuditInterneController::class, 'missionsCreate'])->name('missions.create');
        Route::post('missions', [AuditInterneController::class, 'missionsStore'])->name('missions.store');
        Route::get('missions/{mission}', [AuditInterneController::class, 'missionsShow'])->name('missions.show');

        Route::post('constats', [AuditInterneController::class, 'constatsStore'])->name('constats.store');
        Route::get('constats/{constat}', [AuditInterneController::class, 'constatsShow'])->name('constats.show');

        Route::get('recommandations', [AuditInterneController::class, 'recommandationsIndex'])->name('recommandations.index');
        Route::post('recommandations', [AuditInterneController::class, 'recommandationsStore'])->name('recommandations.store');
        Route::get('recommandations/{recommandation}', [AuditInterneController::class, 'recommandationsShow'])->name('recommandations.show');

        Route::post('suivis', [AuditInterneController::class, 'suivisStore'])->name('suivis.store');
    });

    // === Chaîne de la dépense — Phases 1+2+3 ===
    Route::prefix('expense')->name('expense.')->group(function () {
        // Phase 3 — Tableau de bord chaîne dépense
        Route::get('/', ExpenseDashboardController::class)->name('dashboard');

        Route::get('requests', [ExpenseRequestController::class, 'index'])->name('requests.index');
        Route::get('requests/create', [ExpenseRequestController::class, 'create'])->name('requests.create');
        Route::post('requests', [ExpenseRequestController::class, 'store'])->name('requests.store');
        Route::get('requests/{request}', [ExpenseRequestController::class, 'show'])->name('requests.show');
        Route::post('requests/{request}/submit', [ExpenseRequestController::class, 'submit'])->name('requests.submit');
        Route::post('requests/{request}/validate', [ExpenseRequestController::class, 'validateHierarchique'])->name('requests.validate');
        Route::post('requests/{request}/reject', [ExpenseRequestController::class, 'reject'])->name('requests.reject');
        Route::post('requests/{request}/return', [ExpenseRequestController::class, 'return'])->name('requests.return');
        Route::post('requests/{request}/engage', [ExpenseRequestController::class, 'engage'])->name('requests.engage');
        Route::post('requests/{request}/cancel', [ExpenseRequestController::class, 'cancel'])->name('requests.cancel');

        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');

        // Phase 5 — Exports Excel/CSV des journaux
        Route::get('exports/{journal}.{format}', [ExpenseExportController::class, 'download'])
            ->where(['journal' => 'expressions|engagements|liquidations|ordonnancements|paiements|suppliers', 'format' => 'xlsx|csv'])
            ->name('exports.download');

        // Phase 2 — Service fait + Réception
        Route::get('service-fait', [ServiceFaitController::class, 'index'])->name('service_fait.index');
        Route::post('service-fait/certificats', [ServiceFaitController::class, 'storeCertificat'])->name('service_fait.certificats.store');
        Route::post('service-fait/certificats/{certificat}/validate', [ServiceFaitController::class, 'validateCertificat'])->name('service_fait.certificats.validate');
        Route::post('service-fait/receptions', [ServiceFaitController::class, 'storeReception'])->name('service_fait.receptions.store');
        Route::post('service-fait/receptions/{reception}/validate', [ServiceFaitController::class, 'validateReception'])->name('service_fait.receptions.validate');
    });

    // === Rapports & Reporting PDF institutionnel ===
    Route::prefix('rapports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('historique', [ReportController::class, 'historique'])->name('historique');
        Route::get('verifier/{code}', [ReportController::class, 'verifier'])->name('verifier');
        Route::get('{key}/generer', [ReportController::class, 'show'])->name('show');
        Route::post('{key}/generer', [ReportController::class, 'generer'])->name('generer');
        Route::get('{key}/quick', [ReportController::class, 'quick'])
            ->where('key', '[a-z_]+')
            ->name('quick');
        Route::get('{rapport}/telecharger', [ReportController::class, 'telecharger'])->name('telecharger');
        Route::get('{rapport}/preview', [ReportController::class, 'preview'])->name('preview');
    });

    // === Administration ===
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->parameters(['users' => 'user']);
        Route::resource('departements', DepartementController::class)
            ->parameters(['departements' => 'departement']);
        Route::get('audit', [AuditController::class, 'index'])->name('audit');
    });
});
