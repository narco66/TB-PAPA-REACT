<?php

namespace App\Services\Expense;

use App\Models\Budget\BudgetMouvement;
use App\Models\LiquidationDetail;
use App\Models\Reception;
use App\Models\ServiceDoneCertificate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Service de gestion des étapes 6 et 7 de la chaîne de la dépense :
 *   - Service fait (constatation)
 *   - Réception (procès-verbal)
 *   - Liquidation détaillée (retenues, pénalités, avances)
 */
class ServiceFaitReceptionService
{
    public function __construct(protected ExpenseNotificationDispatcher $notify) {}

    /**
     * Crée un certificat de service fait pour un engagement.
     */
    public function creerCertificatServiceFait(BudgetMouvement $engagement, array $donnees, User $constateur): ServiceDoneCertificate
    {
        if ($engagement->type !== 'engagement') {
            throw new InvalidArgumentException("Un certificat de service fait ne peut être créé que pour un engagement (type actuel : {$engagement->type}).");
        }

        $montantConstate = (float) ($donnees['montant_constate'] ?? 0);
        if ($montantConstate <= 0 || $montantConstate > (float) $engagement->montant) {
            throw new InvalidArgumentException("Le montant constaté doit être > 0 et ≤ au montant engagé ({$engagement->montant}).");
        }

        $cert = DB::transaction(function () use ($engagement, $donnees, $constateur, $montantConstate) {
            $annee = now()->year;

            return ServiceDoneCertificate::create([
                ...$donnees,
                'budget_mouvement_id' => $engagement->id,
                'reference' => ServiceDoneCertificate::genererReference($annee),
                'montant_constate' => $montantConstate,
                'constate_par_id' => $constateur->id,
                'statut' => 'projet',
            ]);
        });

        $this->notify->notifier('service_fait.attendu', $cert, $constateur);

        return $cert;
    }

    /**
     * Valide un certificat de service fait.
     */
    public function validerCertificat(ServiceDoneCertificate $cert, User $valideur): ServiceDoneCertificate
    {
        if ($cert->statut !== 'projet') {
            throw new RuntimeException("Seul un certificat en projet peut être validé (statut actuel : {$cert->statut}).");
        }

        $cert->update([
            'statut' => 'valide',
            'valide_par_id' => $valideur->id,
            'valide_at' => now(),
        ]);

        return $cert->refresh();
    }

    /**
     * Crée un procès-verbal de réception.
     */
    public function creerReception(BudgetMouvement $engagement, array $donnees, User $auteur): Reception
    {
        if ($engagement->type !== 'engagement') {
            throw new InvalidArgumentException('Un PV de réception ne peut être créé que pour un engagement.');
        }

        return DB::transaction(function () use ($engagement, $donnees, $auteur) {
            $annee = now()->year;

            return Reception::create([
                ...$donnees,
                'budget_mouvement_id' => $engagement->id,
                'reference' => Reception::genererReference($annee),
                'saisi_par_id' => $auteur->id,
                'statut' => 'projet',
            ]);
        });
    }

    /**
     * Valide une réception.
     */
    public function validerReception(Reception $reception, User $valideur): Reception
    {
        if ($reception->statut !== 'projet') {
            throw new RuntimeException('Seule une réception en projet peut être validée.');
        }

        $reception->update([
            'statut' => 'valide',
            'valide_par_id' => $valideur->id,
            'valide_at' => now(),
        ]);

        return $reception->refresh();
    }

    /**
     * Crée un détail de liquidation (calcul net = brut - retenues - pénalités).
     */
    public function creerDetailLiquidation(BudgetMouvement $liquidation, array $donnees, User $liquideur): LiquidationDetail
    {
        $this->notify->notifier('liquidation.validee', $liquidation, $liquideur);

        if ($liquidation->type !== 'liquidation') {
            throw new InvalidArgumentException("Un détail de liquidation ne peut être créé que pour une liquidation (type actuel : {$liquidation->type}).");
        }

        $detail = new LiquidationDetail([
            ...$donnees,
            'budget_mouvement_id' => $liquidation->id,
            'liquide_par_id' => $liquideur->id,
        ]);

        // Calcul du net
        $detail->montant_net_a_payer = $detail->calculerMontantNet();

        if ((float) $detail->montant_net_a_payer < 0) {
            throw new InvalidArgumentException('Le montant net à payer ne peut pas être négatif.');
        }

        $detail->save();

        return $detail;
    }
}
