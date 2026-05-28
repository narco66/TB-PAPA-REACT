<?php

namespace App\Services\Expense;

use App\Models\Budget\BudgetMouvement;
use App\Models\ExpenseRequest;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Service d'export des journaux opérationnels de la chaîne de la dépense.
 *
 * Journaux supportés (clés URL) :
 *   - 'expressions'      → Journal des expressions du besoin
 *   - 'engagements'      → Journal des engagements
 *   - 'liquidations'     → Journal des liquidations
 *   - 'ordonnancements'  → Journal des ordonnancements
 *   - 'paiements'        → Journal des paiements
 *   - 'suppliers'        → Référentiel fournisseurs
 *
 * Formats : xlsx, csv
 */
class ExpenseJournalExportService
{
    public const FORMATS = ['xlsx', 'csv'];

    public const JOURNAUX = ['expressions', 'engagements', 'liquidations', 'ordonnancements', 'paiements', 'suppliers'];

    /**
     * Génère le fichier en mémoire et retourne le chemin temporaire.
     */
    public function exporter(string $journal, string $format = 'xlsx', array $filtres = []): string
    {
        if (! in_array($journal, self::JOURNAUX, true)) {
            throw new \InvalidArgumentException("Journal inconnu : {$journal}");
        }
        if (! in_array($format, self::FORMATS, true)) {
            $format = 'xlsx';
        }

        [$titre, $headers, $rows] = match ($journal) {
            'expressions' => $this->journalExpressions($filtres),
            'engagements' => $this->journalMouvements('engagement', $filtres),
            'liquidations' => $this->journalMouvements('liquidation', $filtres),
            'ordonnancements' => $this->journalMouvements('ordonnancement', $filtres),
            'paiements' => $this->journalMouvements('paiement', $filtres),
            'suppliers' => $this->journalSuppliers($filtres),
        };

        return $this->buildFile($journal, $titre, $headers, $rows, $format);
    }

    /**
     * @return array{0:string, 1:array, 2:Collection<int, array>}
     */
    protected function journalExpressions(array $filtres): array
    {
        $headers = [
            'N°', 'Date création', 'Demandeur', 'Département', 'Type engagement',
            'Objet', 'Montant estimé', 'CEEAC', 'PTF', 'Devise',
            'Source financement', 'Fournisseur pressenti', 'Statut',
            'Valideur', 'Date validation', 'Motif décision', 'Engagement',
        ];

        $q = ExpenseRequest::with([
            'demandeur:id,name', 'departement:id,code', 'sourceFinancement:id,code',
            'supplierPressenti:id,libelle', 'valideurHierarchique:id,name', 'engagement:id,reference',
        ]);

        if ($s = $filtres['statut'] ?? null) {
            $q->where('statut', $s);
        }
        if ($t = $filtres['type'] ?? null) {
            $q->where('type_engagement', $t);
        }
        if ($e = $filtres['exercice_id'] ?? null) {
            $q->where('exercice_id', $e);
        }

        $rows = $q->latest()->limit(5000)->get()->map(fn ($r) => [
            $r->numero,
            $r->created_at?->format('d/m/Y'),
            $r->demandeur?->name,
            $r->departement?->code,
            ExpenseRequest::TYPES_ENGAGEMENT[$r->type_engagement] ?? $r->type_engagement,
            $r->objet,
            (float) $r->montant_estime,
            (float) $r->montant_estime_ceeac,
            (float) $r->montant_estime_ptf,
            $r->devise,
            $r->sourceFinancement?->code,
            $r->supplierPressenti?->libelle,
            $r->statut,
            $r->valideurHierarchique?->name,
            $r->valide_at?->format('d/m/Y H:i'),
            $r->motif_decision,
            $r->engagement?->reference,
        ]);

        return ['Journal des expressions du besoin', $headers, $rows];
    }

    protected function journalMouvements(string $type, array $filtres): array
    {
        $headers = match ($type) {
            'engagement' => ['Référence', 'Date', 'N° pièce', 'Ligne budgétaire', 'Bénéficiaire', 'Type engagement', 'Ordonnateur', 'Montant', 'Statut', 'Motif'],
            'liquidation' => ['Référence', 'Date', 'Engagement source', 'Bénéficiaire', 'Valideur', 'Montant', 'Statut', 'Motif'],
            'ordonnancement' => ['Référence', 'Date', 'N° pièce', 'Liquidation source', 'Bénéficiaire', 'Ordonnateur', 'Montant', 'Statut'],
            'paiement' => ['Référence', 'Date', 'N° pièce', 'Bénéficiaire', 'Mode', 'Compte', 'Date valeur', 'Comptable', 'Montant', 'Statut'],
        };

        $q = BudgetMouvement::where('type', $type)
            ->with(['ligne:id,budget_ligne_code,libelle', 'supplier:id,libelle', 'parent:id,reference',
                'ordonnateur:id,name', 'comptable:id,name', 'validePar:id,name']);

        if ($s = $filtres['statut'] ?? null) {
            $q->where('statut_mouvement', $s);
        }

        $rows = $q->latest('date_mouvement')->limit(5000)->get()->map(fn ($m) => match ($type) {
            'engagement' => [
                $m->reference, $m->date_mouvement?->format('d/m/Y'), $m->numero_piece,
                $m->ligne?->budget_ligne_code . ' — ' . $m->ligne?->libelle,
                $m->supplier?->libelle ?? $m->beneficiaire_nom,
                $m->type_engagement,
                $m->ordonnateur?->name,
                (float) $m->montant, $m->statut_mouvement, $m->motif,
            ],
            'liquidation' => [
                $m->reference, $m->date_mouvement?->format('d/m/Y'),
                $m->parent?->reference,
                $m->supplier?->libelle ?? $m->beneficiaire_nom,
                $m->validePar?->name,
                (float) $m->montant, $m->statut_mouvement, $m->motif,
            ],
            'ordonnancement' => [
                $m->reference, $m->date_mouvement?->format('d/m/Y'), $m->numero_piece,
                $m->parent?->reference,
                $m->supplier?->libelle ?? $m->beneficiaire_nom,
                $m->ordonnateur?->name,
                (float) $m->montant, $m->statut_mouvement,
            ],
            'paiement' => [
                $m->reference, $m->date_mouvement?->format('d/m/Y'), $m->numero_piece,
                $m->supplier?->libelle ?? $m->beneficiaire_nom,
                $m->mode_paiement, $m->compte_bancaire,
                $m->date_valeur?->format('d/m/Y'),
                $m->comptable?->name,
                (float) $m->montant, $m->statut_mouvement,
            ],
        });

        return ['Journal des ' . $type . 's', $headers, $rows];
    }

    protected function journalSuppliers(array $filtres): array
    {
        $headers = ['Code', 'Libellé', 'Type', 'NIF', 'RCCM', 'Contact', 'Email', 'Téléphone', 'Pays', 'Compte bancaire', 'Banque', 'Statut'];

        $q = Supplier::query();
        if ($s = $filtres['statut'] ?? null) {
            $q->where('statut', $s);
        }

        $rows = $q->orderBy('libelle')->limit(5000)->get()->map(fn ($s) => [
            $s->code, $s->libelle, $s->type, $s->nif, $s->rccm,
            $s->contact_principal, $s->email, $s->telephone, $s->pays,
            $s->compte_bancaire, $s->banque, $s->statut,
        ]);

        return ['Référentiel fournisseurs', $headers, $rows];
    }

    /**
     * Construit le fichier xlsx ou csv et retourne le chemin tmp.
     *
     * @param Collection<int, array> $rows
     */
    protected function buildFile(string $journal, string $titre, array $headers, Collection $rows, string $format): string
    {
        $sheet = new Spreadsheet;
        $ws = $sheet->getActiveSheet();
        $ws->setTitle(substr($titre, 0, 30));

        // Titre
        $ws->setCellValue('A1', strtoupper($titre) . ' — TB-PAPA-CEEAC');
        $derniere = Coordinate::stringFromColumnIndex(count($headers));
        $ws->mergeCells("A1:{$derniere}1");
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('1E5CB3');
        $ws->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Émis le
        $ws->setCellValue('A2', 'Émis le ' . now()->format('d/m/Y H:i') . ' · ' . $rows->count() . ' ligne(s)');
        $ws->mergeCells("A2:{$derniere}2");
        $ws->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $ws->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('6B7280');

        // Headers
        $ws->fromArray($headers, null, 'A4');
        $ws->getStyle("A4:{$derniere}4")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $ws->getStyle("A4:{$derniere}4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E5CB3');
        $ws->getStyle("A4:{$derniere}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Data
        if ($rows->isNotEmpty()) {
            $ws->fromArray($rows->toArray(), null, 'A5');
        }

        // Auto width
        foreach (range(1, count($headers)) as $i) {
            $col = Coordinate::stringFromColumnIndex($i);
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        // Freeze
        $ws->freezePane('A5');

        // Write
        $ext = $format === 'csv' ? 'csv' : 'xlsx';
        $tmp = tempnam(sys_get_temp_dir(), "expense_export_{$journal}_") . '.' . $ext;
        $writer = $format === 'csv' ? new Csv($sheet) : new Xlsx($sheet);
        if ($format === 'csv') {
            $writer->setDelimiter(';')->setEnclosure('"')->setUseBOM(true);
        }
        $writer->save($tmp);

        return $tmp;
    }
}
