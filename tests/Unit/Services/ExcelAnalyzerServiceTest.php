<?php

namespace Tests\Unit\Services;

use App\Services\Budget\ExcelAnalyzerService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;

class ExcelAnalyzerServiceTest extends TestCase
{
    protected string $tempFile;

    protected function tearDown(): void
    {
        if (isset($this->tempFile) && file_exists($this->tempFile)) {
            @unlink($this->tempFile);
        }
        parent::tearDown();
    }

    public function test_detecte_feuille_budget_par_nom(): void
    {
        $this->tempFile = $this->creerFichier([
            'Budget à importer' => [
                ['Code Axe', 'Département', 'Nature dépense', 'Ligne budgétaire', 'Montant prévu', 'Devise', 'Année', 'Source financement'],
                ['AXE 1', 'DAEC', 'fonctionnement', 'Mission terrain', '1500000', 'XAF', '2026', 'CEEAC-EM'],
            ],
        ]);

        $analyse = (new ExcelAnalyzerService)->analyser($this->tempFile);

        $this->assertSame(1, $analyse['nb_feuilles']);
        $this->assertSame('budget', $analyse['feuilles'][0]['type_detecte']);
        $this->assertSame(1, $analyse['feuilles'][0]['nb_lignes']);
        $this->assertSame(8, $analyse['feuilles'][0]['nb_colonnes']);
    }

    public function test_detecte_feuille_axes_par_nom_et_entetes(): void
    {
        $this->tempFile = $this->creerFichier([
            'Axes stratégiques' => [
                ['Code', 'Libellé', 'Description', 'Poids'],
                ['AXE 1', 'Paix et sécurité', 'Axe stratégique paix', '30'],
            ],
        ]);

        $analyse = (new ExcelAnalyzerService)->analyser($this->tempFile);

        $this->assertSame('axes', $analyse['feuilles'][0]['type_detecte']);
        $mapping = $analyse['feuilles'][0]['mapping_suggere'];
        $this->assertSame('code', $mapping['Code']);
        $this->assertSame('libelle', $mapping['Libellé']);
    }

    public function test_detecte_multi_feuilles(): void
    {
        $this->tempFile = $this->creerFichier([
            'Axes' => [['Code', 'Libellé'], ['AXE 1', 'Test']],
            'Produits' => [['Code', 'Code axe', 'Libellé'], ['P.1.1', 'AXE 1', 'Produit']],
            'Activités' => [['Code', 'Libellé', 'Date début', 'Date fin'], ['A.1', 'Activité 1', '2026-01-01', '2026-12-31']],
        ]);

        $analyse = (new ExcelAnalyzerService)->analyser($this->tempFile);

        $this->assertSame(3, $analyse['nb_feuilles']);
        $types = array_column($analyse['feuilles'], 'type_detecte');
        $this->assertContains('axes', $types);
        $this->assertContains('produits', $types);
        $this->assertContains('activites', $types);
    }

    public function test_colonnes_inconnues_sont_listees(): void
    {
        $this->tempFile = $this->creerFichier([
            'Axes' => [['Code', 'Libellé', 'Champ Inconnu', 'XYZ'], ['AXE 1', 'Test', 'val', 'foo']],
        ]);

        $analyse = (new ExcelAnalyzerService)->analyser($this->tempFile);

        $inconnues = $analyse['feuilles'][0]['colonnes_inconnues'];
        $this->assertContains('Champ Inconnu', $inconnues);
        $this->assertContains('XYZ', $inconnues);
    }

    public function test_champs_manquants_sont_signales(): void
    {
        $this->tempFile = $this->creerFichier([
            'Indicateurs' => [['Code', 'Libellé'], ['IND-001', 'Test']],
        ]);

        $analyse = (new ExcelAnalyzerService)->analyser($this->tempFile);

        $this->assertSame('indicateurs', $analyse['feuilles'][0]['type_detecte']);
        $manquants = $analyse['feuilles'][0]['champs_manquants'];
        $this->assertContains('cible', $manquants);
        $this->assertContains('baseline', $manquants);
    }

    public function test_type_non_detecte_si_aucun_indice(): void
    {
        $this->tempFile = $this->creerFichier([
            'Données diverses' => [['ColA', 'ColB'], ['1', '2']],
        ]);

        $analyse = (new ExcelAnalyzerService)->analyser($this->tempFile);

        $this->assertNull($analyse['feuilles'][0]['type_detecte']);
    }

    /**
     * Crée un fichier .xlsx temporaire avec les feuilles fournies.
     *
     * @param array<string, array<int, array<int, string>>> $feuilles
     */
    protected function creerFichier(array $feuilles): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        $first = true;
        foreach ($feuilles as $nom => $rows) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(mb_substr($nom, 0, 31));
            foreach ($rows as $rowIdx => $row) {
                foreach ($row as $colIdx => $value) {
                    $col = chr(65 + $colIdx);
                    $sheet->setCellValue($col . ($rowIdx + 1), $value);
                }
            }
            if ($first) {
                $spreadsheet->setActiveSheetIndexByName($sheet->getTitle());
                $first = false;
            }
        }

        $temp = tempnam(sys_get_temp_dir(), 'xlsx_') . '.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($temp);

        return $temp;
    }
}
