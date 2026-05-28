<?php

namespace App\Services\Budget\Import;

use App\Models\Budget\BudgetImport;
use App\Models\Budget\BudgetImportErreur;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Génère un rapport d'erreurs XLSX riche pour un import multi-feuilles.
 *
 * Structure :
 *   - Feuille "Synthèse" : totaux par feuille × type × gravité
 *   - Feuille "Erreurs détaillées" : tableau coloré par gravité
 *
 * Couleurs gravité :
 *   - critique   = rouge intense (#dc2626)
 *   - erreur     = rouge clair (#fee2e2)
 *   - avertissement = orange clair (#fed7aa)
 *   - info       = bleu clair (#dbeafe)
 */
class ImportErrorReportXlsxService
{
    public const COULEURS_GRAVITE = [
        'critique' => 'FFDC2626',
        'erreur' => 'FFFECACA',
        'avertissement' => 'FFFED7AA',
        'info' => 'FFDBEAFE',
    ];

    public function genererPour(BudgetImport $import): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        // Récupérer toutes les erreurs (parent + enfants)
        $importIds = collect([$import->id])->merge($import->enfants()->pluck('id'))->all();
        $erreurs = BudgetImportErreur::whereIn('import_id', $importIds)
            ->orderBy('feuille')
            ->orderBy('ligne')
            ->get();

        $this->ajouterFeuilleSynthese($spreadsheet, $import, $erreurs);
        $this->ajouterFeuilleErreurs($spreadsheet, $erreurs);

        $spreadsheet->setActiveSheetIndex(0);

        $temp = tempnam(sys_get_temp_dir(), 'rapport-erreurs-') . '.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($temp);

        return $temp;
    }

    protected function ajouterFeuilleSynthese(Spreadsheet $ss, BudgetImport $import, $erreurs): void
    {
        $sheet = $ss->createSheet();
        $sheet->setTitle('Synthèse');

        // Header
        $sheet->setCellValue('A1', 'Rapport d\'erreurs — TB-PAPA-CEEAC');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E5CB3']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->setCellValue('A3', 'Fichier :');
        $sheet->setCellValue('B3', $import->fichier_nom);
        $sheet->setCellValue('A4', 'Import #:');
        $sheet->setCellValue('B4', $import->id);
        $sheet->setCellValue('A5', 'Date :');
        $sheet->setCellValue('B5', $import->execute_at?->format('d/m/Y H:i') ?? '—');
        $sheet->setCellValue('A6', 'Statut :');
        $sheet->setCellValue('B6', $import->statut);
        $sheet->setCellValue('A7', 'Total erreurs :');
        $sheet->setCellValue('B7', $erreurs->count());

        $sheet->getStyle('A3:A7')->getFont()->setBold(true);

        // Tableau de synthèse par feuille × gravité
        $row = 10;
        $sheet->setCellValue("A{$row}", 'Feuille');
        $sheet->setCellValue("B{$row}", 'Critique');
        $sheet->setCellValue("C{$row}", 'Erreur');
        $sheet->setCellValue("D{$row}", 'Avertissement');
        $sheet->setCellValue("E{$row}", 'Info');
        $sheet->setCellValue("F{$row}", 'Total');

        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF374151']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;

        $parFeuille = $erreurs->groupBy('feuille');
        foreach ($parFeuille as $feuille => $errs) {
            $sheet->setCellValue("A{$row}", $feuille ?: '(global)');
            $sheet->setCellValue("B{$row}", $errs->where('gravite', 'critique')->count());
            $sheet->setCellValue("C{$row}", $errs->where('gravite', 'erreur')->count());
            $sheet->setCellValue("D{$row}", $errs->where('gravite', 'avertissement')->count());
            $sheet->setCellValue("E{$row}", $errs->where('gravite', 'info')->count());
            $sheet->setCellValue("F{$row}", $errs->count());
            $sheet->getStyle("F{$row}")->getFont()->setBold(true);
            $row++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    protected function ajouterFeuilleErreurs(Spreadsheet $ss, $erreurs): void
    {
        $sheet = $ss->createSheet();
        $sheet->setTitle('Erreurs détaillées');

        // En-têtes
        $headers = ['Feuille', 'Ligne', 'Colonne', 'Valeur fautive', 'Gravité', 'Règle', 'Message', 'Correction suggérée', 'Statut'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}1", $h);
        }
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E5CB3']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // Lignes
        $row = 2;
        foreach ($erreurs as $err) {
            $sheet->setCellValue("A{$row}", $err->feuille ?? '—');
            $sheet->setCellValue("B{$row}", $err->ligne ?? '');
            $sheet->setCellValue("C{$row}", $err->colonne ?? '—');
            $sheet->setCellValue("D{$row}", $err->valeur_fautive ?? '—');
            $sheet->setCellValue("E{$row}", $err->gravite);
            $sheet->setCellValue("F{$row}", $err->regle);
            $sheet->setCellValue("G{$row}", $err->message);
            $sheet->setCellValue("H{$row}", $err->correction_suggeree ?? '');
            $sheet->setCellValue("I{$row}", $err->statut_traitement);

            // Coloration par gravité (sur toute la ligne)
            $couleur = self::COULEURS_GRAVITE[$err->gravite] ?? 'FFFFFFFF';
            $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $couleur]],
                'borders' => [
                    'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE5E7EB']],
                ],
            ]);

            // Gravité en gras + couleur texte pour critique
            $sheet->getStyle("E{$row}")->getFont()->setBold(true);
            if ($err->gravite === 'critique') {
                $sheet->getStyle("E{$row}")->getFont()->getColor()->setARGB('FFFFFFFF');
            }

            $row++;
        }

        // Largeurs colonnes
        $widths = ['A' => 18, 'B' => 8, 'C' => 18, 'D' => 25, 'E' => 12, 'F' => 22, 'G' => 50, 'H' => 40, 'I' => 12];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // Wrap text pour Message
        $sheet->getStyle('G1:G' . ($row - 1))->getAlignment()->setWrapText(true);

        // Freeze panes (1re ligne)
        $sheet->freezePane('A2');
    }
}
