<?php

namespace App\Services\Budget;

use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetLigne;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Service d'export budgétaire (Excel / CSV).
 * Templates supportés :
 *   - 'consolide' : toutes les lignes
 *   - 'par_axe' : groupées par axe RBM
 *   - 'par_source' : groupées par source de financement
 *   - 'par_type' : fonctionnement / investissement / équipement
 *   - 'comparatif' : avec colonnes Budget N-1, Réalisations N-1, Variation
 *   - 'execute' : avec montants engagés / payés
 */
class BudgetExportService
{
    public const FORMATS = ['xlsx', 'csv'];

    public const TEMPLATES = ['consolide', 'par_axe', 'par_source', 'par_type', 'comparatif', 'execute'];

    public function exporter(BudgetExercice $exercice, string $template = 'consolide', string $format = 'xlsx'): string
    {
        if (! in_array($template, self::TEMPLATES, true)) {
            $template = 'consolide';
        }
        if (! in_array($format, self::FORMATS, true)) {
            $format = 'xlsx';
        }

        $spreadsheet = new Spreadsheet;
        $ws = $spreadsheet->getActiveSheet();
        $ws->setTitle("Budget {$exercice->annee}");

        $titre = "BUDGET EXERCICE {$exercice->annee} — CEEAC";
        $ws->setCellValue('A1', $titre);
        $ws->mergeCells('A1:K1');
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = $this->headersPourTemplate($template);
        $ws->fromArray($headers, null, 'A3');
        $derniere = Coordinate::stringFromColumnIndex(count($headers));
        $ws->getStyle("A3:{$derniere}3")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $ws->getStyle("A3:{$derniere}3")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E5CB3');

        $lignes = $this->donneesPourTemplate($exercice, $template);
        $ws->fromArray($lignes, null, 'A4');

        // Auto-fit
        foreach (range(1, count($headers)) as $i) {
            $col = Coordinate::stringFromColumnIndex($i);
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        $dossier = storage_path('app/exports');
        if (! is_dir($dossier)) {
            mkdir($dossier, 0o755, true);
        }
        $nom = "budget-{$exercice->annee}-{$template}-" . date('YmdHis') . ".{$format}";
        $chemin = "{$dossier}/{$nom}";

        $writer = $format === 'csv' ? new Csv($spreadsheet) : new Xlsx($spreadsheet);
        $writer->save($chemin);

        return $chemin;
    }

    protected function headersPourTemplate(string $template): array
    {
        return match ($template) {
            'comparatif' => [
                'Titre', 'Chap.', 'Art.', 'Parag.', 'Code', 'Libellé',
                'Budget 2026 (TOTAL)', 'Budget 2026 (CEEAC-EM)', 'Budget 2026 (PTF)',
                'Budget N-1', 'Réalisation N-1', 'Variation %',
            ],
            'execute' => [
                'Code', 'Libellé', 'Type', 'Budget total', 'Engagé', 'Payé', 'Disponible', 'Taux conso',
            ],
            'par_axe' => [
                'Code Axe', 'Axe', 'Code Produit', 'Produit', 'Code Activité', 'Activité',
                'Libellé budget', 'TOTAL', 'CEEAC-EM', 'PTF',
            ],
            'par_source' => [
                'Source', 'Type', 'Libellé', 'Montant', 'CEEAC-EM', 'PTF',
            ],
            'par_type' => [
                'Type budget', 'Libellé', 'CEEAC-EM', 'PTF', 'TOTAL',
            ],
            default => [
                'Titre', 'Chap.', 'Art.', 'Parag.', 'Code', 'Libellé',
                'Type budget', 'Nature', 'TOTAL', 'CEEAC-EM', 'PTF',
                'Source', 'Axe', 'Observations',
            ],
        };
    }

    protected function donneesPourTemplate(BudgetExercice $exercice, string $template): array
    {
        $query = BudgetLigne::query()
            ->where('exercice_id', $exercice->id)
            ->with(['source:id,code,libelle', 'axe:id,code,libelle', 'produit:id,code', 'activite:id,code'])
            ->orderBy('titre_code')
            ->orderBy('chapitre_code')
            ->orderBy('article_code')
            ->orderBy('code_action');

        return match ($template) {
            'comparatif' => $query->get()->map(fn ($l) => [
                $l->titre_code, $l->chapitre_code, $l->article_code, $l->paragraphe_code,
                $l->code_action, $l->libelle,
                (float) $l->montant_total, (float) $l->montant_ceeac_em, (float) $l->montant_ptf,
                (float) $l->budget_annee_precedente, (float) $l->realisation_annee_precedente, (float) ($l->variation ?? 0),
            ])->toArray(),

            'execute' => $query->get()->map(fn ($l) => [
                $l->code_action, $l->libelle, $l->type_budget,
                (float) $l->montant_total, (float) $l->montant_engage,
                (float) $l->montant_paye, (float) $l->disponible(), $l->tauxConsommation(),
            ])->toArray(),

            'par_axe' => $query->whereNotNull('axe_id')->get()->map(fn ($l) => [
                $l->axe?->code, $l->axe?->libelle,
                $l->produit?->code, $l->produit?->libelle ?? '',
                $l->activite?->code, $l->activite?->libelle ?? '',
                $l->libelle,
                (float) $l->montant_total, (float) $l->montant_ceeac_em, (float) $l->montant_ptf,
            ])->toArray(),

            'par_source' => $query->whereNotNull('source_financement_id')->get()->map(fn ($l) => [
                $l->source?->code, $l->source?->type,
                $l->libelle,
                (float) $l->montant_total, (float) $l->montant_ceeac_em, (float) $l->montant_ptf,
            ])->toArray(),

            'par_type' => $query->get()->groupBy('type_budget')->map(fn ($groupe, $type) => [
                $type,
                $groupe->count() . ' lignes',
                (float) $groupe->sum('montant_ceeac_em'),
                (float) $groupe->sum('montant_ptf'),
                (float) $groupe->sum('montant_total'),
            ])->values()->toArray(),

            default => $query->get()->map(fn ($l) => [
                $l->titre_code, $l->chapitre_code, $l->article_code, $l->paragraphe_code,
                $l->code_action, $l->libelle,
                $l->type_budget, $l->nature,
                (float) $l->montant_total, (float) $l->montant_ceeac_em, (float) $l->montant_ptf,
                $l->source?->code, $l->axe?->code, $l->observations,
            ])->toArray(),
        };
    }
}
