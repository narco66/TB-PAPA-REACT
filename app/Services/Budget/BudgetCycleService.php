<?php

namespace App\Services\Budget;

use App\Models\Budget\BudgetLigne;
use App\Models\Budget\BudgetMouvement;
use App\Models\User;
use App\Services\Expense\ExpenseNotificationDispatcher;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Moteur du cycle d'exécution budgétaire IPSAS.
 *
 * 4 étapes obligatoires en cascade :
 *   1. Engagement       — réserve le montant sur la ligne, exige disponible suffisant
 *   2. Liquidation      — constate le service fait, montant ≤ engagement
 *   3. Ordonnancement   — titre de paiement émis par l'ordonnateur
 *   4. Paiement         — décaissement effectif par le comptable
 *
 * Contrôles institutionnels :
 *   - Disponible budgétaire vérifié avant chaque engagement (anti-dépassement)
 *   - Séparation ordonnateur ≠ comptable (COSO ERM, IPSAS 24)
 *   - Cohérence des montants entre étapes (liquidation ≤ engagement, etc.)
 *   - Soldes recalculés automatiquement sur la ligne budgétaire
 */
class BudgetCycleService
{
    public function __construct(protected ?ExpenseNotificationDispatcher $notify = null) {}

    /**
     * Engage un montant sur une ligne budgétaire.
     * Vérifie le disponible avant insertion.
     */
    public function engager(
        BudgetLigne $ligne,
        float $montant,
        User $ordonnateur,
        ?string $beneficiaireNom = null,
        ?string $beneficiaireReference = null,
        ?int $partenaireId = null,
        ?string $numeroPiece = null,
        ?string $motif = null,
        ?string $pieceJustificative = null,
    ): BudgetMouvement {
        if ($montant <= 0) {
            throw new InvalidArgumentException('Le montant d\'engagement doit être strictement positif.');
        }

        $disponible = $this->disponiblePour($ligne);
        if ($montant > $disponible) {
            throw new RuntimeException(sprintf(
                'Engagement refusé : montant %s > disponible %s sur la ligne %s.',
                number_format($montant, 2, ',', ' '),
                number_format($disponible, 2, ',', ' '),
                $ligne->libelle,
            ));
        }

        return DB::transaction(function () use ($ligne, $montant, $ordonnateur, $beneficiaireNom, $beneficiaireReference, $partenaireId, $numeroPiece, $motif, $pieceJustificative) {
            $mouvement = BudgetMouvement::create([
                'ligne_id' => $ligne->id,
                'parent_mouvement_id' => null,
                'type' => 'engagement',
                'montant' => $montant,
                'date_mouvement' => now()->toDateString(),
                'numero_piece' => $numeroPiece,
                'beneficiaire_nom' => $beneficiaireNom,
                'beneficiaire_reference' => $beneficiaireReference,
                'partenaire_id' => $partenaireId,
                'ordonnateur_id' => $ordonnateur->id,
                'motif' => $motif,
                'piece_justificative' => $pieceJustificative,
                'statut_mouvement' => 'valide',
                'saisi_par_id' => $ordonnateur->id,
            ]);

            $this->rafraichirSoldes($ligne);

            return $mouvement;
        });
    }

    /**
     * Liquide tout ou partie d'un engagement (constatation du service fait).
     */
    public function liquider(
        BudgetMouvement $engagement,
        float $montant,
        User $valideur,
        ?string $numeroPiece = null,
        ?string $motif = null,
        ?string $pieceJustificative = null,
    ): BudgetMouvement {
        if ($engagement->type !== 'engagement') {
            throw new InvalidArgumentException('Le mouvement parent doit être un engagement.');
        }

        if ($montant <= 0 || $montant > (float) $engagement->montant) {
            throw new InvalidArgumentException(sprintf(
                'Montant liquidation invalide : %s (engagement = %s).',
                $montant,
                $engagement->montant,
            ));
        }

        $dejaLiquide = BudgetMouvement::where('parent_mouvement_id', $engagement->id)
            ->where('type', 'liquidation')
            ->where('statut_mouvement', 'valide')
            ->sum('montant');

        if ((float) $dejaLiquide + $montant > (float) $engagement->montant + 0.01) {
            throw new RuntimeException('La liquidation cumulée dépasserait l\'engagement initial.');
        }

        return DB::transaction(function () use ($engagement, $montant, $valideur, $numeroPiece, $motif, $pieceJustificative) {
            $mouvement = BudgetMouvement::create([
                'ligne_id' => $engagement->ligne_id,
                'parent_mouvement_id' => $engagement->id,
                'type' => 'liquidation',
                'montant' => $montant,
                'date_mouvement' => now()->toDateString(),
                'numero_piece' => $numeroPiece,
                'beneficiaire_nom' => $engagement->beneficiaire_nom,
                'beneficiaire_reference' => $engagement->beneficiaire_reference,
                'partenaire_id' => $engagement->partenaire_id,
                'motif' => $motif,
                'piece_justificative' => $pieceJustificative,
                'statut_mouvement' => 'valide',
                'valide_at' => now(),
                'valide_par_id' => $valideur->id,
                'saisi_par_id' => $valideur->id,
            ]);

            $this->rafraichirSoldes($engagement->ligne);

            return $mouvement;
        });
    }

    /**
     * Ordonnance une liquidation (émission du titre de paiement).
     */
    public function ordonnancer(
        BudgetMouvement $liquidation,
        User $ordonnateur,
        ?string $numeroPiece = null,
        ?string $motif = null,
    ): BudgetMouvement {
        if ($liquidation->type !== 'liquidation') {
            throw new InvalidArgumentException('Le mouvement parent doit être une liquidation.');
        }

        $dejaOrdonnance = BudgetMouvement::where('parent_mouvement_id', $liquidation->id)
            ->where('type', 'ordonnancement')
            ->where('statut_mouvement', 'valide')
            ->exists();

        if ($dejaOrdonnance) {
            throw new RuntimeException('Cette liquidation a déjà été ordonnancée.');
        }

        return DB::transaction(function () use ($liquidation, $ordonnateur, $numeroPiece, $motif) {
            $mouvement = BudgetMouvement::create([
                'ligne_id' => $liquidation->ligne_id,
                'parent_mouvement_id' => $liquidation->id,
                'type' => 'ordonnancement',
                'montant' => $liquidation->montant,
                'date_mouvement' => now()->toDateString(),
                'numero_piece' => $numeroPiece,
                'beneficiaire_nom' => $liquidation->beneficiaire_nom,
                'beneficiaire_reference' => $liquidation->beneficiaire_reference,
                'partenaire_id' => $liquidation->partenaire_id,
                'ordonnateur_id' => $ordonnateur->id,
                'motif' => $motif,
                'statut_mouvement' => 'valide',
                'saisi_par_id' => $ordonnateur->id,
            ]);

            $this->rafraichirSoldes($liquidation->ligne);

            $this->notify?->notifier('ordonnancement.genere', $mouvement, $ordonnateur, $motif);

            return $mouvement;
        });
    }

    /**
     * Paie un ordonnancement (décaissement effectif).
     * Applique la séparation des tâches : comptable ≠ ordonnateur (COSO).
     */
    public function payer(
        BudgetMouvement $ordonnancement,
        User $comptable,
        string $modePaiement,
        ?string $numeroPiece = null,
        ?string $compteBancaire = null,
        ?\DateTimeInterface $dateValeur = null,
        ?string $motif = null,
    ): BudgetMouvement {
        if ($ordonnancement->type !== 'ordonnancement') {
            throw new InvalidArgumentException('Le mouvement parent doit être un ordonnancement.');
        }

        if (! in_array($modePaiement, BudgetMouvement::MODES_PAIEMENT, true)) {
            throw new InvalidArgumentException("Mode de paiement invalide : {$modePaiement}.");
        }

        if ($ordonnancement->ordonnateur_id === $comptable->id) {
            throw new RuntimeException(
                'Séparation des tâches violée : le comptable ne peut pas être l\'ordonnateur (règle COSO ERM / IPSAS).',
            );
        }

        $dejaPaye = BudgetMouvement::where('parent_mouvement_id', $ordonnancement->id)
            ->where('type', 'paiement')
            ->where('statut_mouvement', 'valide')
            ->exists();

        if ($dejaPaye) {
            throw new RuntimeException('Cet ordonnancement a déjà été payé.');
        }

        return DB::transaction(function () use ($ordonnancement, $comptable, $modePaiement, $numeroPiece, $compteBancaire, $dateValeur, $motif) {
            $mouvement = BudgetMouvement::create([
                'ligne_id' => $ordonnancement->ligne_id,
                'parent_mouvement_id' => $ordonnancement->id,
                'type' => 'paiement',
                'montant' => $ordonnancement->montant,
                'date_mouvement' => now()->toDateString(),
                'numero_piece' => $numeroPiece,
                'beneficiaire_nom' => $ordonnancement->beneficiaire_nom,
                'beneficiaire_reference' => $ordonnancement->beneficiaire_reference,
                'partenaire_id' => $ordonnancement->partenaire_id,
                'comptable_id' => $comptable->id,
                'mode_paiement' => $modePaiement,
                'compte_bancaire' => $compteBancaire,
                'date_valeur' => $dateValeur,
                'motif' => $motif,
                'statut_mouvement' => 'valide',
                'saisi_par_id' => $comptable->id,
            ]);

            $this->rafraichirSoldes($ordonnancement->ligne);

            $this->notify?->notifier('paiement.effectue', $mouvement, $comptable, $motif);

            return $mouvement;
        });
    }

    /**
     * Recalcule les soldes (engagé, liquidé, ordonnancé, payé, disponible, taux)
     * sur une ligne budgétaire à partir des mouvements validés.
     */
    public function rafraichirSoldes(BudgetLigne $ligne): void
    {
        $sommes = BudgetMouvement::where('ligne_id', $ligne->id)
            ->where('statut_mouvement', 'valide')
            ->selectRaw('
                SUM(CASE WHEN type = ? THEN montant ELSE 0 END) AS engage,
                SUM(CASE WHEN type = ? THEN montant ELSE 0 END) AS liquide,
                SUM(CASE WHEN type = ? THEN montant ELSE 0 END) AS ordonnance,
                SUM(CASE WHEN type = ? THEN montant ELSE 0 END) AS paye
            ', ['engagement', 'liquidation', 'ordonnancement', 'paiement'])
            ->first();

        $engage = (float) ($sommes->engage ?? 0);
        $liquide = (float) ($sommes->liquide ?? 0);
        $ordonnance = (float) ($sommes->ordonnance ?? 0);
        $paye = (float) ($sommes->paye ?? 0);
        $total = (float) $ligne->montant_total;
        $disponible = max(0.0, $total - $engage);
        $taux = $total > 0 ? round(($paye / $total) * 100, 2) : 0;

        $ligne->update([
            'montant_engage' => $engage,
            'montant_liquide' => $liquide,
            'montant_ordonnance' => $ordonnance,
            'montant_paye' => $paye,
            'montant_disponible' => $disponible,
            'taux_consommation' => $taux,
        ]);
    }

    /** Montant disponible avant tout nouvel engagement. */
    public function disponiblePour(BudgetLigne $ligne): float
    {
        $engage = (float) BudgetMouvement::where('ligne_id', $ligne->id)
            ->where('type', 'engagement')
            ->where('statut_mouvement', 'valide')
            ->sum('montant');

        return max(0.0, (float) $ligne->montant_total - $engage);
    }
}
