<?php

namespace App\Services\Budget;

use App\Models\Activite;
use App\Models\Axe;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetSourceFinancement;
use App\Models\Departement;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\Tache;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BudgetImportTemplateService
{
    public const HEADERS = [
        'Code Axe',
        'Libellé Axe',
        'Code Produit',
        'Libellé Produit',
        'Code Sous-Produit',
        'Libellé Sous-Produit',
        'Code Activité',
        'Libellé Activité',
        'Code Tâche',
        'Libellé Tâche',
        'Département',
        'Nature dépense',
        'Ligne budgétaire',
        'Montant prévu',
        'Devise',
        'Année',
        'Source financement',
        'Observations',
    ];

    public function generer(): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('TB-PAPA-CEEAC')
            ->setTitle('Modèle officiel import budget')
            ->setSubject('Import budget RBM/GAR CEEAC');

        $instructions = $spreadsheet->getActiveSheet();
        $instructions->setTitle('Instructions');
        $instructions->fromArray([
            ['Modèle officiel d’importation budgétaire TB-PAPA-CEEAC'],
            ['Renseigner une ligne par poste budgétaire à importer.'],
            ['Les colonnes obligatoires sont : Code Axe, Département, Nature dépense, Ligne budgétaire, Montant prévu, Devise, Année, Source financement.'],
            ['La chaîne RBM/GAR attendue est : Axe → Produit → Sous-Produit → Activité → Tâche.'],
            ['Les codes doivent correspondre aux référentiels existants lorsque renseignés.'],
            ['Utiliser l’onglet "Référentiels" pour copier les valeurs autorisées.'],
        ]);
        $instructions->getColumnDimension('A')->setWidth(120);
        $instructions->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $refs = $spreadsheet->createSheet();
        $refs->setTitle('Référentiels');
        $refs->fromArray([
            ['Départements', 'Devises', 'Natures', 'Sources financement', 'Axes'],
        ]);

        $departements = Departement::where('actif', true)->orderBy('ordre')->get(['code', 'libelle'])
            ->map(fn ($d) => "{$d->code} - {$d->libelle}")->values()->all();
        $sources = BudgetSourceFinancement::where('actif', true)->orderBy('libelle')->get(['code', 'libelle'])
            ->map(fn ($s) => "{$s->code} - {$s->libelle}")->values()->all();
        $axes = Axe::orderBy('papa_id')->orderBy('ordre')->get(['code', 'libelle'])
            ->map(fn ($a) => "{$a->code} - {$a->libelle}")->values()->all();
        $devises = ['XAF', 'USD', 'EUR'];
        $natures = ['fonctionnement', 'investissement', 'equipement', 'dotation', 'dette', 'transfert', 'recette_interne', 'recette_externe', 'autre'];

        $max = max(count($departements), count($sources), count($axes), count($devises), count($natures), 1);
        for ($i = 0; $i < $max; $i++) {
            $refs->fromArray([[
                $departements[$i] ?? null,
                $devises[$i] ?? null,
                $natures[$i] ?? null,
                $sources[$i] ?? null,
                $axes[$i] ?? null,
            ]], null, 'A' . ($i + 2));
        }
        foreach (range('A', 'E') as $col) {
            $refs->getColumnDimension($col)->setWidth(36);
        }
        $refs->getStyle('A1:E1')->getFont()->setBold(true);
        $refs->getStyle('A1:E1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EAF2FF');

        $budget = $spreadsheet->createSheet();
        $budget->setTitle('Budget à importer');
        $budget->fromArray(self::HEADERS, null, 'A1');
        $budget->fromArray([$this->exampleRow($departements, $sources)], null, 'A2');

        $lastColumn = $budget->getHighestColumn();
        $budget->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $budget->getStyle("A1:{$lastColumn}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F4C81');
        $budget->getStyle("A1:{$lastColumn}1")->getFont()->getColor()->setRGB('FFFFFF');
        $budget->getStyle("A1:{$lastColumn}200")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $budget->freezePane('A2');
        foreach (range('A', $lastColumn) as $col) {
            $budget->getColumnDimension($col)->setWidth(in_array($col, ['B', 'D', 'F', 'H', 'J', 'M', 'R'], true) ? 32 : 18);
        }

        $this->applyListValidation($budget, 'K2:K500', 'Référentiels!$A$2:$A$500');
        $this->applyListValidation($budget, 'L2:L500', 'Référentiels!$C$2:$C$20');
        $this->applyListValidation($budget, 'O2:O500', 'Référentiels!$B$2:$B$10');
        $this->applyListValidation($budget, 'Q2:Q500', 'Référentiels!$D$2:$D$500');

        $moneyValidation = $budget->getCell('N2')->getDataValidation();
        $moneyValidation->setType(DataValidation::TYPE_DECIMAL)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(false)
            ->setShowErrorMessage(true)
            ->setErrorTitle('Montant invalide')
            ->setError('Le montant prévu doit être un nombre positif ou nul.')
            ->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL)
            ->setFormula1('0');
        $budget->setDataValidation('N2:N500', $moneyValidation);

        $yearValidation = $budget->getCell('P2')->getDataValidation();
        $yearValidation->setType(DataValidation::TYPE_WHOLE)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(false)
            ->setShowErrorMessage(true)
            ->setErrorTitle('Année invalide')
            ->setError('L’année doit être comprise entre 2020 et 2100.')
            ->setOperator(DataValidation::OPERATOR_BETWEEN)
            ->setFormula1('2020')
            ->setFormula2('2100');
        $budget->setDataValidation('P2:P500', $yearValidation);

        $this->addSimpleTemplateSheet($spreadsheet, 'Budget', [
            'Titre', 'Chap.', 'Art.', 'Parag.', 'Code Action', 'Intitulés',
            'Budget 2025', 'Recettes réalisées', 'Taux de réalisation', 'Prévisions 2026', 'Variation',
        ], [
            ['Titre 2', '', '', '', '', 'RECETTES EXTERNES', 11846247097, 2840427566, '24%', 14030081000, '18.4%'],
            ['', '', '741', '', '', 'Dons des institutions internationales', 11846247097, 2840427566, '24%', 14030081000, '18.4%'],
        ]);

        $this->addSimpleTemplateSheet($spreadsheet, 'PAP', [
            'Code', 'Pilier', 'Axe / Objectif stratégique', 'Programmes / Sous-programmes / Actions',
            'Total 2026', 'CEEAC', 'PTF', 'Total 2025', 'Réalisations', 'Taux de réalisation (%)',
        ], [
            ['ACT.1.1.1.1', 'Pilier 5', 'Santé et protection sociale', 'Réalisation d’une revue situationnelle', 126000, 0, 126000, 0, 0, '0%'],
        ]);

        $this->addSimpleTemplateSheet($spreadsheet, 'Contributions', [
            'Etat membre', 'Clef répartition (%)', 'Contribution FCFA', 'Contribution USD', 'PTF identifié', 'Contribution PTF FCFA',
        ], [
            ['République du Cameroun', 11, 1000000000, 1785714, 'Union Européenne', 755000000],
        ]);

        $this->addSimpleTemplateSheet($spreadsheet, 'Indicateurs', [
            'Code', 'Code Sous-Produit', 'Libellé', 'Définition', 'Type', 'Catégorie', 'Unité', 'Baseline', 'Cible', 'Fréquence',
        ], [
            ['IND-001', 'SP.1.1.1', 'Taux de réalisation des activités', 'Mesure du progrès annuel', 'quantitatif', 'effet', '%', 0, 80, 'trimestrielle'],
        ]);

        $this->addSimpleTemplateSheet($spreadsheet, 'Responsables', [
            'Code entité', 'Type entité', 'Responsable', 'Email', 'Département', 'Rôle',
        ], [
            ['ACT.1.1.1.1', 'activite', 'Nom Responsable', 'responsable@example.org', $departements[0] ?? 'DEP - Département', 'Point focal'],
        ]);

        $spreadsheet->setActiveSheetIndexByName('Budget à importer');

        $path = storage_path('app/budget-imports/modele-import-budget-tb-papa-ceeac.xlsx');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    protected function applyListValidation($sheet, string $range, string $formula): void
    {
        $validation = $sheet->getCell(explode(':', $range)[0])->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setShowDropDown(true)
            ->setShowErrorMessage(true)
            ->setFormula1($formula);
        $sheet->setDataValidation($range, $validation);
    }

    protected function addSimpleTemplateSheet(Spreadsheet $spreadsheet, string $title, array $headers, array $examples): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($title);
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($examples, null, 'A2');

        $lastColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F4C81');
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->freezePane('A2');

        foreach (range('A', $lastColumn) as $col) {
            $sheet->getColumnDimension($col)->setWidth(24);
        }
    }

    protected function exampleRow(array $departements, array $sources): array
    {
        $axe = null;
        $produit = null;
        $sousProduit = null;
        $activite = null;
        $tache = Tache::with(['activite.sousProduit.produit.axe'])
            ->whereHas('activite.sousProduit.produit.axe')
            ->orderBy('id')
            ->first();

        if ($tache) {
            $activite = $tache->activite;
            $sousProduit = $activite?->sousProduit;
            $produit = $sousProduit?->produit;
            $axe = $produit?->axe;
        } else {
            $activite = Activite::with(['sousProduit.produit.axe'])
                ->whereHas('sousProduit.produit.axe')
                ->orderBy('id')
                ->first();
            $sousProduit = $activite?->sousProduit;
            $produit = $sousProduit?->produit;
            $axe = $produit?->axe;
        }

        if (! $axe) {
            $sousProduit = SousProduit::with(['produit.axe'])
                ->whereHas('produit.axe')
                ->orderBy('id')
                ->first();
            $produit = $sousProduit?->produit;
            $axe = $produit?->axe;
        }

        if (! $axe) {
            $produit = Produit::with('axe')
                ->whereHas('axe')
                ->orderBy('id')
                ->first();
            $axe = $produit?->axe;
        }

        $axe ??= Axe::orderBy('papa_id')->orderBy('ordre')->first();

        return [
            $axe?->code ?? 'AXE 1',
            $axe?->libelle ?? 'Renforcement institutionnel',
            $produit?->code ?? '',
            $produit?->libelle ?? '',
            $sousProduit?->code ?? '',
            $sousProduit?->libelle ?? '',
            $activite?->code ?? '',
            $activite?->libelle ?? '',
            $tache?->code ?? '',
            $tache?->libelle ?? '',
            $departements[0] ?? 'DEP - Département',
            'fonctionnement',
            'Formation et accompagnement des utilisateurs',
            25000000,
            'XAF',
            BudgetExercice::orderByDesc('annee')->value('annee') ?? now()->year,
            $sources[0] ?? 'CEEAC - Ressources propres',
            'Exemple conforme',
        ];
    }
}
