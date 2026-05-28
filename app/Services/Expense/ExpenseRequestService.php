<?php

namespace App\Services\Expense;

use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetLigne;
use App\Models\Budget\BudgetMouvement;
use App\Models\CommitmentLine;
use App\Models\ExpenseRequest;
use App\Models\User;
use App\Services\Budget\BudgetCycleService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Service métier de la chaîne de la dépense — Phase 1.
 *
 * Orchestration du cycle :
 *   Expression du besoin (brouillon)
 *     → soumis
 *     → en_validation_hierarchique
 *     → valide → engagement créé (BudgetMouvement type=engagement via BudgetCycleService)
 *   ou → retourne_correction / rejete / annule
 *
 * Conformité :
 *   - RGCP (chaîne de la dépense publique)
 *   - COSO ERM (séparation des fonctions)
 *   - Activity log + traçabilité par champ
 */
class ExpenseRequestService
{
    public function __construct(
        protected BudgetCycleService $budgetCycle,
        protected ExpenseNotificationDispatcher $notify,
    ) {}

    /**
     * Crée une expression du besoin en brouillon.
     */
    public function creer(array $donnees, User $demandeur): ExpenseRequest
    {
        $this->validerCoherenceMontants($donnees);

        return DB::transaction(function () use ($donnees, $demandeur) {
            $exerciceAnnee = $donnees['exercice_annee']
                ?? BudgetExercice::find($donnees['exercice_id'])->annee;

            return ExpenseRequest::create([
                ...$donnees,
                'numero' => ExpenseRequest::genererNumero($exerciceAnnee),
                'demandeur_id' => $demandeur->id,
                'statut' => 'brouillon',
                'devise' => $donnees['devise'] ?? 'XAF',
            ]);
        });
    }

    /**
     * Soumet une expression du besoin pour validation hiérarchique.
     */
    public function soumettre(ExpenseRequest $request, User $auteur): ExpenseRequest
    {
        if ($request->statut !== 'brouillon' && $request->statut !== 'retourne_correction') {
            throw new RuntimeException("Seule une expression en brouillon ou retournée peut être soumise (statut actuel : {$request->statut}).");
        }

        $request->update([
            'statut' => 'soumis',
        ]);

        activity()
            ->performedOn($request)
            ->causedBy($auteur)
            ->withProperties(['ancien_statut' => 'brouillon', 'nouveau_statut' => 'soumis'])
            ->log('Expression du besoin soumise');

        $this->notify->notifier('expression.soumise', $request->fresh(), $auteur);

        return $request->refresh();
    }

    /**
     * Validation hiérarchique de l'expression du besoin.
     */
    public function valider(ExpenseRequest $request, User $valideur, ?string $commentaire = null): ExpenseRequest
    {
        if (! in_array($request->statut, ['soumis', 'en_validation_hierarchique'], true)) {
            throw new RuntimeException("L'expression du besoin doit être en cours de validation pour être validée.");
        }

        $request->update([
            'statut' => 'valide',
            'valideur_hierarchique_id' => $valideur->id,
            'valide_at' => now(),
            'motif_decision' => $commentaire,
        ]);

        $this->notify->notifier('visa.accorde', $request->fresh(), $valideur, $commentaire);

        return $request->refresh();
    }

    /**
     * Retour pour correction.
     */
    public function retourner(ExpenseRequest $request, User $valideur, string $motif): ExpenseRequest
    {
        if (! in_array($request->statut, ['soumis', 'en_validation_hierarchique'], true)) {
            throw new RuntimeException('Impossible de retourner une expression non en cours de validation.');
        }

        $request->update([
            'statut' => 'retourne_correction',
            'valideur_hierarchique_id' => $valideur->id,
            'motif_decision' => $motif,
        ]);

        $this->notify->notifier('expression.retour_correction', $request->fresh(), $valideur, $motif);

        return $request->refresh();
    }

    /**
     * Rejet définitif.
     */
    public function rejeter(ExpenseRequest $request, User $valideur, string $motif): ExpenseRequest
    {
        if (in_array($request->statut, ['engage', 'annule'], true)) {
            throw new RuntimeException('Impossible de rejeter une expression déjà engagée ou annulée.');
        }

        $request->update([
            'statut' => 'rejete',
            'valideur_hierarchique_id' => $valideur->id,
            'motif_decision' => $motif,
            'valide_at' => now(),
        ]);

        $this->notify->notifier('expression.rejet', $request->fresh(), $valideur, $motif);

        return $request->refresh();
    }

    /**
     * Convertit l'expression du besoin validée en engagement budgétaire.
     * Crée un BudgetMouvement type=engagement + N CommitmentLine.
     *
     * @param array<int, array{budget_ligne_id:int, libelle:string, montant:float, montant_ceeac?:float, montant_ptf?:float, source_financement_id?:?int, imputation_analytique?:?string}> $imputations
     */
    public function engager(
        ExpenseRequest $request,
        User $ordonnateur,
        array $imputations,
        ?string $numeroPiece = null,
        ?string $motif = null,
    ): BudgetMouvement {
        if ($request->statut !== 'valide') {
            throw new RuntimeException('Seule une expression validée peut être engagée (statut actuel : ' . $request->statut . ').');
        }

        if (empty($imputations)) {
            throw new InvalidArgumentException('Au moins une imputation budgétaire est requise.');
        }

        return DB::transaction(function () use ($request, $ordonnateur, $imputations, $numeroPiece, $motif) {
            $premiereImputation = $imputations[0];
            $ligne = BudgetLigne::findOrFail($premiereImputation['budget_ligne_id']);
            $montantTotal = (float) array_sum(array_column($imputations, 'montant'));

            // Création du mouvement engagement via le service IPSAS existant
            $mouvement = $this->budgetCycle->engager(
                ligne: $ligne,
                montant: $montantTotal,
                ordonnateur: $ordonnateur,
                beneficiaireNom: $request->supplierPressenti?->libelle,
                beneficiaireReference: $request->supplierPressenti?->code,
                numeroPiece: $numeroPiece ?? $request->numero,
                motif: $motif ?? "Engagement issu de {$request->numero} — {$request->objet}",
            );

            // Champs enrichis (lien expense_request + type d'engagement + supplier)
            $mouvement->update([
                'expense_request_id' => $request->id,
                'type_engagement' => $request->type_engagement,
                'supplier_id' => $request->supplier_pressenti_id,
            ]);

            // Création des N lignes d'engagement (multi-imputation)
            foreach ($imputations as $imp) {
                CommitmentLine::create([
                    'budget_mouvement_id' => $mouvement->id,
                    'budget_ligne_id' => $imp['budget_ligne_id'],
                    'libelle' => $imp['libelle'] ?? $request->objet,
                    'montant' => $imp['montant'],
                    'montant_ceeac' => $imp['montant_ceeac'] ?? 0,
                    'montant_ptf' => $imp['montant_ptf'] ?? 0,
                    'source_financement_id' => $imp['source_financement_id'] ?? $request->source_financement_id,
                    'imputation_analytique' => $imp['imputation_analytique'] ?? null,
                    'observations' => $imp['observations'] ?? null,
                ]);
            }

            // Mise à jour expense_request
            $request->update([
                'statut' => 'engage',
                'budget_mouvement_engagement_id' => $mouvement->id,
            ]);

            $this->notify->notifier(
                'expression.engagee',
                $request->fresh(),
                $ordonnateur,
                $motif,
                ['mouvement_id' => $mouvement->id, 'montant' => $montantTotal],
            );

            return $mouvement->refresh();
        });
    }

    /**
     * Annulation d'une expression du besoin (uniquement avant engagement).
     */
    public function annuler(ExpenseRequest $request, User $auteur, string $motif): ExpenseRequest
    {
        if ($request->statut === 'engage') {
            throw new RuntimeException("Une expression engagée ne peut pas être annulée. Procédure d'annulation budgétaire requise.");
        }

        $request->update([
            'statut' => 'annule',
            'motif_decision' => $motif,
        ]);

        return $request->refresh();
    }

    /**
     * Vérifie la règle Total = CEEAC + PTF.
     */
    protected function validerCoherenceMontants(array $donnees): void
    {
        $total = (float) ($donnees['montant_estime'] ?? 0);
        $ceeac = (float) ($donnees['montant_estime_ceeac'] ?? 0);
        $ptf = (float) ($donnees['montant_estime_ptf'] ?? 0);

        if ($ceeac > 0 || $ptf > 0) {
            $somme = $ceeac + $ptf;
            if (abs($somme - $total) > 0.01) {
                throw new InvalidArgumentException(sprintf(
                    'Incohérence des montants : Total %.2f ≠ CEEAC %.2f + PTF %.2f.',
                    $total, $ceeac, $ptf,
                ));
            }
        }
    }
}
