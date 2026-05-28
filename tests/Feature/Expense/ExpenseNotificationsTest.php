<?php

namespace Tests\Feature\Expense;

use App\Http\Middleware\EnforceTwoFactor;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetLigne;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\ExpenseEventNotification;
use App\Services\Expense\ExpenseRequestService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ExpenseNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $initiateur;

    protected User $ordonnateur;

    protected User $directeur;

    protected BudgetExercice $exercice;

    protected BudgetLigne $ligne;

    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->withoutMiddleware(EnforceTwoFactor::class);

        $this->initiateur = User::factory()->create(['actif' => true]);
        $this->initiateur->assignRole('point_focal');

        $this->ordonnateur = User::factory()->create(['actif' => true]);
        $this->ordonnateur->assignRole('directeur_technique');

        $this->directeur = User::factory()->create(['actif' => true]);
        $this->directeur->assignRole('directeur_technique');

        $this->exercice = BudgetExercice::create(['annee' => 2027, 'libelle' => 'X', 'statut' => 'valide']);
        $this->ligne = BudgetLigne::create([
            'exercice_id' => $this->exercice->id, 'libelle' => 'F', 'nature' => 'depense',
            'type_budget' => 'fonctionnement', 'montant_total' => 10_000_000,
        ]);
        $this->supplier = Supplier::create(['code' => 'F', 'libelle' => 'F', 'type' => 'personne_morale', 'statut' => 'actif']);
    }

    public function test_notification_soumission_aux_directeurs(): void
    {
        Notification::fake();
        $svc = app(ExpenseRequestService::class);

        $r = $svc->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'X', 'justification' => 'X',
            'montant_estime' => 100_000,
        ], $this->initiateur);

        $svc->soumettre($r, $this->initiateur);

        Notification::assertSentTo($this->directeur, ExpenseEventNotification::class,
            fn ($n) => $n->toArray($this->directeur)['event_type'] === 'expression.soumise');
        Notification::assertNotSentTo($this->initiateur, ExpenseEventNotification::class);
    }

    public function test_notification_retour_correction_au_demandeur_uniquement(): void
    {
        Notification::fake();
        $svc = app(ExpenseRequestService::class);

        $r = $svc->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'X', 'justification' => 'X',
            'montant_estime' => 100_000,
        ], $this->initiateur);
        $svc->soumettre($r, $this->initiateur);
        $svc->retourner($r->fresh(), $this->ordonnateur, 'Manque doc');

        Notification::assertSentTo($this->initiateur, ExpenseEventNotification::class,
            fn ($n) => $n->toArray($this->initiateur)['event_type'] === 'expression.retour_correction');
        // L'ordonnateur (auteur du retour) ne doit pas s'auto-notifier
        Notification::assertNotSentTo($this->ordonnateur, ExpenseEventNotification::class,
            fn ($n) => $n->toArray($this->ordonnateur)['event_type'] === 'expression.retour_correction');
    }

    public function test_notification_rejet_avec_motif_au_demandeur(): void
    {
        Notification::fake();
        $svc = app(ExpenseRequestService::class);

        $r = $svc->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'X', 'justification' => 'X',
            'montant_estime' => 100_000,
        ], $this->initiateur);
        $svc->soumettre($r, $this->initiateur);
        $svc->rejeter($r->fresh(), $this->ordonnateur, 'Non éligible');

        Notification::assertSentTo($this->initiateur, ExpenseEventNotification::class,
            fn ($n) => $n->toArray($this->initiateur)['event_type'] === 'expression.rejet'
                && $n->toArray($this->initiateur)['commentaire'] === 'Non éligible');
    }

    public function test_notification_visa_accorde_au_demandeur(): void
    {
        Notification::fake();
        $svc = app(ExpenseRequestService::class);

        $r = $svc->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'X', 'justification' => 'X',
            'montant_estime' => 100_000,
        ], $this->initiateur);
        $svc->soumettre($r, $this->initiateur);
        $svc->valider($r->fresh(), $this->ordonnateur, 'OK');

        Notification::assertSentTo($this->initiateur, ExpenseEventNotification::class,
            fn ($n) => $n->toArray($this->initiateur)['event_type'] === 'visa.accorde');
    }

    public function test_notification_engagement_au_demandeur_et_controle_financier(): void
    {
        $cf = User::factory()->create(['actif' => true]);
        $cf->assignRole('controle_financier');

        Notification::fake();
        $svc = app(ExpenseRequestService::class);

        $r = $svc->creer([
            'exercice_id' => $this->exercice->id,
            'type_engagement' => 'achat_biens',
            'objet' => 'X', 'justification' => 'X',
            'montant_estime' => 100_000,
            'supplier_pressenti_id' => $this->supplier->id,
        ], $this->initiateur);
        $svc->soumettre($r, $this->initiateur);
        $svc->valider($r->fresh(), $this->ordonnateur);
        $svc->engager($r->fresh(), $this->ordonnateur, [
            ['budget_ligne_id' => $this->ligne->id, 'libelle' => 'X', 'montant' => 100_000],
        ]);

        Notification::assertSentTo($this->initiateur, ExpenseEventNotification::class,
            fn ($n) => $n->toArray($this->initiateur)['event_type'] === 'expression.engagee');
        Notification::assertSentTo($cf, ExpenseEventNotification::class,
            fn ($n) => $n->toArray($cf)['event_type'] === 'expression.engagee');
    }

    public function test_event_labels_complets(): void
    {
        $labels = ExpenseEventNotification::EVENT_LABELS;
        $this->assertArrayHasKey('expression.soumise', $labels);
        $this->assertArrayHasKey('expression.engagee', $labels);
        $this->assertArrayHasKey('paiement.effectue', $labels);
        $this->assertArrayHasKey('echeance.depassee', $labels);
        $this->assertGreaterThanOrEqual(11, count($labels));
    }

    public function test_commande_detection_echeances_executable(): void
    {
        $this->artisan('expense:detecter-echeances', ['--dry-run' => true])
            ->assertSuccessful();
    }
}
