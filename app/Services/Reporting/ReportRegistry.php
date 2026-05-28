<?php

namespace App\Services\Reporting;

use App\Reports\Analytique\TableauBordExecutifReport;
use App\Reports\Audit\HistoriqueRapportsReport;
use App\Reports\Audit\JournalAuditReport;
use App\Reports\Budget\BudgetConsolideReport;
use App\Reports\Budget\BudgetLignesReport;
use App\Reports\Expense\AvisPaiementReport;
use App\Reports\Expense\BonEngagementReport;
use App\Reports\Expense\BordereauOrdonnancementReport;
use App\Reports\Expense\CertificatServiceFaitReport;
use App\Reports\Expense\DecomptePaiementReport;
use App\Reports\Expense\DemandeVisaFinancierReport;
use App\Reports\Expense\FicheExpressionBesoinReport;
use App\Reports\Expense\FicheLiquidationReport;
use App\Reports\Expense\NotificationRejetReport;
use App\Reports\Expense\NotificationRetourReport;
use App\Reports\Expense\OrdonnancePaiementReport;
use App\Reports\Expense\PvReceptionReport;
use App\Reports\Expense\RecuPaiementReport;
use App\Reports\Expense\TableauBordExpenseReport;
use App\Reports\Expense\VisaFinancierReport;
use App\Reports\Gouvernance\FicheDepartementReport;
use App\Reports\Gouvernance\MatriceRaciReport;
use App\Reports\Listings\ListeActivitesReport;
use App\Reports\Listings\ListeAlertesReport;
use App\Reports\Listings\ListeAuditMissionsReport;
use App\Reports\Listings\ListeAuditPlansReport;
use App\Reports\Listings\ListeAuditRecommandationsReport;
use App\Reports\Listings\ListeAxesReport;
use App\Reports\Listings\ListeBudgetExercicesReport;
use App\Reports\Listings\ListeDepartementsReport;
use App\Reports\Listings\ListeExpenseRequestsReport;
use App\Reports\Listings\ListeIndicateursReport;
use App\Reports\Listings\ListeLiquidationsReport;
use App\Reports\Listings\ListeOrdonnancementsReport;
use App\Reports\Listings\ListePaiementsReport;
use App\Reports\Listings\ListePapasReport;
use App\Reports\Listings\ListeProduitsReport;
use App\Reports\Listings\ListeSousProduitsReport;
use App\Reports\Listings\ListeSuppliersReport;
use App\Reports\Listings\ListeTachesReport;
use App\Reports\Listings\ListeUsersReport;
use App\Reports\Performance\FicheActiviteReport;
use App\Reports\Performance\FichePerformanceAxeReport;
use App\Reports\Performance\FicheProduitReport;
use App\Reports\Performance\FicheSousProduitReport;
use App\Reports\Performance\FicheTacheReport;
use App\Reports\Rbm\MatriceIndicateursReport;
use App\Reports\Rbm\MatriceRbmReport;
use App\Reports\Report;
use App\Reports\Strategique\PapaStrategiqueReport;
use App\Reports\Strategique\SyntheseExecutiveReport;
use InvalidArgumentException;

/**
 * Registre central des rapports disponibles dans TB-PAPA-CEEAC.
 *
 * Pour ajouter un nouveau rapport :
 *   1. Créer une classe héritant de App\Reports\Report
 *   2. L'enregistrer ci-dessous dans la propriété $reports
 */
class ReportRegistry
{
    /** @var array<string, class-string<Report>> */
    protected array $reports = [
        // Stratégiques
        PapaStrategiqueReport::class,
        SyntheseExecutiveReport::class,
        // Budgétaires
        BudgetConsolideReport::class,
        BudgetLignesReport::class,
        // Chaîne de la dépense (Phases 1+2+3+6)
        FicheExpressionBesoinReport::class,
        DemandeVisaFinancierReport::class,
        VisaFinancierReport::class,
        BonEngagementReport::class,
        NotificationRetourReport::class,
        NotificationRejetReport::class,
        CertificatServiceFaitReport::class,
        PvReceptionReport::class,
        FicheLiquidationReport::class,
        DecomptePaiementReport::class,
        OrdonnancePaiementReport::class,
        BordereauOrdonnancementReport::class,
        AvisPaiementReport::class,
        RecuPaiementReport::class,
        TableauBordExpenseReport::class,
        // Performance (fiches par niveau RBM)
        FichePerformanceAxeReport::class,
        FicheProduitReport::class,
        FicheSousProduitReport::class,
        FicheActiviteReport::class,
        FicheTacheReport::class,
        // RBM/GAR
        MatriceRbmReport::class,
        MatriceIndicateursReport::class,
        // Gouvernance
        MatriceRaciReport::class,
        FicheDepartementReport::class,
        // Audit
        JournalAuditReport::class,
        HistoriqueRapportsReport::class,
        // Analytique
        TableauBordExecutifReport::class,
        // Listes d'index (export PDF des pages liste de l'application)
        ListePapasReport::class,
        ListeAxesReport::class,
        ListeProduitsReport::class,
        ListeSousProduitsReport::class,
        ListeActivitesReport::class,
        ListeTachesReport::class,
        ListeIndicateursReport::class,
        ListeAlertesReport::class,
        ListeUsersReport::class,
        ListeDepartementsReport::class,
        ListeBudgetExercicesReport::class,
        ListeAuditPlansReport::class,
        ListeAuditMissionsReport::class,
        ListeAuditRecommandationsReport::class,
        ListeExpenseRequestsReport::class,
        ListeSuppliersReport::class,
        ListePaiementsReport::class,
        ListeLiquidationsReport::class,
        ListeOrdonnancementsReport::class,
    ];

    /** @return Report[] */
    public function tous(): array
    {
        return array_map(fn (string $class) => app($class), $this->reports);
    }

    public function trouver(string $key): Report
    {
        foreach ($this->tous() as $report) {
            if ($report->key() === $key) {
                return $report;
            }
        }

        throw new InvalidArgumentException("Rapport introuvable : {$key}");
    }

    /** @return array<string, Report[]> */
    public function parCategorie(): array
    {
        $groupes = [];
        foreach ($this->tous() as $report) {
            $groupes[$report->categorie()][] = $report;
        }

        return $groupes;
    }
}
