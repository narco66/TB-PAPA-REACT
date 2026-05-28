<?php

namespace App\Services\Budget;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Analyse un fichier Excel pour préparer un import multi-feuilles.
 *
 * Sortie : structure JSON-friendly décrivant chaque feuille du fichier
 *   (nom, type détecté, nombre de lignes, en-têtes, mapping suggéré).
 *
 * Aucune écriture en base — purement lecture. Utilisé par l'étape "analyze"
 * de la procédure d'import progressive (upload → analyze → mapping → import).
 */
class ExcelAnalyzerService
{
    /**
     * Synonymes canoniques par type de données.
     * Reprend la logique existante de BudgetImportService (rétro-compatible).
     */
    public const SYNONYMS = [
        'budget' => [
            'titre_code' => ['titre', 'code titre'],
            'chapitre_code' => ['chap.', 'chap', 'chapitre', 'code chapitre'],
            'article_code' => ['art.', 'art', 'article', 'code article'],
            'paragraphe_code' => ['parag.', 'parag', 'paragraphe', 'code paragraphe'],
            'code_action' => ['code action', 'action', 'code_action', 'code projet'],
            'intitules' => ['intitulés', 'intitules', 'intitulé', 'intitule'],
            'budget_annee_precedente' => ['budget 2025', 'recettes attendues', 'budget annee precedente'],
            'realisation_annee_precedente' => ['recettes réalisées', 'recettes realisees', 'réalisations', 'realisations'],
            'taux_realisation_precedent' => ['taux de réalisation', 'taux de realisation', 'taux réalisation', 'taux realisation'],
            'previsions' => ['prévisions 2026', 'previsions 2026', 'prévision', 'prevision'],
            'variation' => ['variation', 'écart', 'ecart'],
            'code_axe' => ['code axe', 'axe', 'axe code', 'code_axe'],
            'code_produit' => ['code produit', 'produit', 'produit code'],
            'code_sous_produit' => ['code sous-produit', 'sous-produit', 'sous_produit', 'sous produit'],
            'code_activite' => ['code activité', 'activité', 'activite', 'code activite'],
            'code_tache' => ['code tâche', 'tâche', 'tache', 'code tache'],
            'departement' => ['département', 'departement', 'service'],
            'source_financement' => ['source', 'source financement', 'source_financement', 'bailleur'],
            'nature_depense' => ['nature', 'nature dépense', 'nature_depense', 'type budget', 'type_budget'],
            'ligne_budgetaire' => ['libellé', 'libelle', 'description', 'ligne', 'ligne budgétaire'],
            'montant_prevu' => ['montant', 'montant prévu', 'montant_prevu', 'prévision'],
            'montant_ceeac_em' => ['montant ceeac', 'ceeac em', 'ceeac_em', 'montant ceeac em'],
            'montant_ptf' => ['montant ptf', 'ptf', 'partenaires'],
            'devise' => ['devise', 'currency'],
            'annee' => ['année', 'annee', 'exercice', 'year'],
        ],
        'axes' => [
            'code' => ['code', 'code axe'],
            'libelle' => ['libellé', 'libelle', 'intitulé', 'titre'],
            'description' => ['description', 'résumé'],
            'poids' => ['poids', 'pondération', 'ponderation'],
            'departement' => ['département', 'departement'],
            'responsable' => ['responsable', 'pilote'],
        ],
        'produits' => [
            'code' => ['code', 'code produit'],
            'code_axe' => ['axe', 'code axe', 'parent'],
            'libelle' => ['libellé', 'libelle'],
            'description' => ['description'],
            'poids' => ['poids'],
        ],
        'sous_produits' => [
            'code' => ['code'],
            'code_produit' => ['produit', 'code produit', 'parent'],
            'libelle' => ['libellé', 'libelle'],
            'description' => ['description'],
            'poids' => ['poids'],
        ],
        'activites' => [
            'code' => ['code'],
            'code_sous_produit' => ['sous-produit', 'sous_produit', 'parent'],
            'libelle' => ['libellé', 'libelle'],
            'description' => ['description'],
            'date_debut' => ['date début', 'debut', 'date_debut'],
            'date_fin' => ['date fin', 'fin', 'date_fin'],
            'poids' => ['poids'],
            'responsable' => ['responsable'],
            'point_focal' => ['point focal', 'point_focal'],
        ],
        'taches' => [
            'code' => ['code'],
            'code_activite' => ['activité', 'activite', 'parent'],
            'libelle' => ['libellé', 'libelle'],
            'description' => ['description'],
            'poids' => ['poids'],
            'taux_execution' => ['avancement', 'taux', 'taux_execution', '%'],
        ],
        'indicateurs' => [
            'code' => ['code'],
            'code_sous_produit' => ['sous-produit', 'sous_produit', 'rattachement'],
            'libelle' => ['libellé', 'libelle'],
            'definition' => ['définition', 'definition'],
            'type' => ['type'],
            'categorie' => ['catégorie', 'categorie', 'niveau'],
            'unite' => ['unité', 'unite'],
            'baseline' => ['baseline', 'référence', 'reference'],
            'cible' => ['cible', 'target'],
            'frequence_collecte' => ['fréquence', 'frequence', 'frequence_collecte'],
        ],
        'pap' => [
            'code' => ['code'],
            'axe' => ['axe', 'axe objectif strategique', 'objectif strategique'],
            'libelle' => ['programmes sous programmes actions', 'programmes', 'actions', 'libelle'],
            'total_2026' => ['total 2026', 'exercice 2026'],
            'ceeac' => ['ceeac', 'source ceeac'],
            'ptf' => ['ptf', 'partenaires'],
            'total_2025' => ['total 2025', 'total previsions'],
            'taux_realisation' => ['taux de realisation', 'taux réalisation'],
        ],
        'contributions' => [
            'etat_membre' => ['etat membre', 'pays', 'etat'],
            'cle_repartition' => ['clef repartition', 'cle repartition', 'repartition'],
            'contribution_fcfa' => ['contribution attendue', 'en fcfa', 'fcfa'],
            'contribution_usd' => ['en dollar us', 'usd', 'dollar'],
            'ptf_identifie' => ['ptf identifie', 'ptf identifié', 'bailleur'],
        ],
        'synthese' => [
            'libelle' => ['libelle', 'intitule', 'rubrique'],
            'montant' => ['montant', 'total', 'valeur'],
            'taux' => ['taux', 'pourcentage'],
        ],
    ];

    /**
     * Indices contextuels pour deviner le type d'une feuille à partir de son nom.
     */
    public const HINTS_NOM_FEUILLE = [
        'budget' => ['budget', 'lignes', 'dépenses', 'depenses', 'recettes'],
        'pap' => ['pap', 'résumépap', 'resumepap', 'plan annuel de performance'],
        'contributions' => ['contribution', 'contributions'],
        'synthese' => ['synthèse', 'synthese', 'synthèse des données', 'synthese des donnees'],
        'axes' => ['axe', 'axes', 'pilier', 'priorité'],
        'produits' => ['produit', 'produits', 'résultats', 'resultats'],
        'sous_produits' => ['sous-produit', 'sous_produit', 'sous produits'],
        'activites' => ['activité', 'activite', 'activités', 'activites'],
        'taches' => ['tâche', 'tache', 'tâches', 'taches'],
        'indicateurs' => ['indicateur', 'indicateurs', 'kpi', 'cmr'],
    ];

    /**
     * @return array{
     *   fichier: string,
     *   nb_feuilles: int,
     *   feuilles: array<int, array{
     *     nom: string,
     *     index: int,
     *     type_detecte: string|null,
     *     nb_lignes: int,
     *     nb_colonnes: int,
     *     en_tetes: array<int, string>,
     *     mapping_suggere: array<string, string|null>,
     *     colonnes_inconnues: array<int, string>,
     *     champs_manquants: array<int, string>
     *   }>
     * }
     */
    public function analyser(UploadedFile|string $fichier): array
    {
        $cheminTemp = $fichier instanceof UploadedFile
            ? $fichier->getRealPath()
            : $fichier;

        $spreadsheet = IOFactory::load($cheminTemp);

        $resultat = [
            'fichier' => $fichier instanceof UploadedFile
                ? $fichier->getClientOriginalName()
                : basename($cheminTemp),
            'nb_feuilles' => $spreadsheet->getSheetCount(),
            'feuilles' => [],
        ];

        foreach ($spreadsheet->getAllSheets() as $index => $feuille) {
            $resultat['feuilles'][] = $this->analyserFeuille($spreadsheet, $feuille, $index);
        }

        return $resultat;
    }

    protected function analyserFeuille(Spreadsheet $spreadsheet, Worksheet $feuille, int $index): array
    {
        $nom = $feuille->getTitle();
        $data = $this->tableauFeuille($feuille);
        $zone = $this->detecterZoneTabulaire($nom, $data);

        $enTetes = $this->entetesDepuisZone($data, $zone);
        $nbLignes = $this->compterLignesDonnees($data, $zone);

        $typeDetecte = $this->devinerType($nom, $enTetes);
        $mapping = $this->mappingSuggere($enTetes, $typeDetecte);

        $colonnesInconnues = array_values(array_filter(
            $enTetes,
            fn ($h) => ! in_array($h, array_filter($mapping), true) && ! $this->matchSynonyme($h, $typeDetecte),
        ));

        $champsAttendus = $this->champsAttendus($typeDetecte, $mapping);
        $champsManquants = $typeDetecte
            ? array_values(array_diff($champsAttendus, array_values(array_filter($mapping))))
            : [];

        return [
            'nom' => $nom,
            'index' => $index,
            'type_detecte' => $typeDetecte,
            'nb_lignes' => $nbLignes,
            'nb_colonnes' => count($enTetes),
            'ligne_titre' => $zone['ligne_titre'],
            'ligne_entete' => $zone['ligne_entete'],
            'ligne_debut_donnees' => $zone['ligne_debut_donnees'],
            'lignes_decoratives_ignorees' => $zone['lignes_decoratives_ignorees'],
            'zones_detectees' => [$zone],
            'cellules_fusionnees' => count($feuille->getMergeCells()),
            'en_tetes' => $enTetes,
            'mapping_suggere' => $mapping,
            'colonnes_inconnues' => $colonnesInconnues,
            'champs_manquants' => $champsManquants,
        ];
    }

    /**
     * Déduit le type de données d'une feuille à partir de son nom et de ses en-têtes.
     */
    protected function devinerType(string $nomFeuille, array $enTetes): ?string
    {
        $nomNormalise = $this->normaliser($nomFeuille);

        foreach (self::HINTS_NOM_FEUILLE as $type => $hints) {
            foreach ($hints as $hint) {
                if (str_contains($nomNormalise, $this->normaliser($hint))) {
                    return $type;
                }
            }
        }

        // Fallback : essayer de deviner à partir des en-têtes
        $scores = [];
        foreach (self::SYNONYMS as $type => $champs) {
            $score = 0;
            foreach ($enTetes as $en) {
                foreach ($champs as $synonymes) {
                    foreach ($synonymes as $syn) {
                        if ($this->normaliser($en) === $this->normaliser($syn)) {
                            $score++;
                        }
                    }
                }
            }
            $scores[$type] = $score;
        }

        arsort($scores);
        $top = array_key_first($scores);

        return ($top && $scores[$top] >= 2) ? $top : null;
    }

    /**
     * Calcule la correspondance suggérée entre en-têtes du fichier et champs canoniques.
     *
     * @return array<string, string|null> en-tête → champ canonique (ou null si pas de match)
     */
    protected function mappingSuggere(array $enTetes, ?string $type): array
    {
        $mapping = [];
        if (! $type) {
            foreach ($enTetes as $h) {
                $mapping[$h] = null;
            }

            return $mapping;
        }

        $champs = self::SYNONYMS[$type] ?? [];

        foreach ($enTetes as $h) {
            $hNorm = $this->normaliser($h);
            $champTrouve = $this->mappingBudgetMalabo($hNorm);
            $bestScore = 0.0;
            if (! $champTrouve) {
                foreach ($champs as $champ => $synonymes) {
                    foreach ($synonymes as $syn) {
                        $synNorm = $this->normaliser($syn);
                        if ($synNorm === $hNorm || str_contains($hNorm, $synNorm) || str_contains($synNorm, $hNorm)) {
                            $champTrouve = $champ;
                            break 2;
                        }
                        similar_text($hNorm, $synNorm, $pct);
                        if ($pct > $bestScore && $pct >= 82) {
                            $bestScore = $pct;
                            $champTrouve = $champ;
                        }
                    }
                }
            }
            $mapping[$h] = $champTrouve;
        }

        return $mapping;
    }

    protected function mappingBudgetMalabo(string $header): ?string
    {
        return match (true) {
            $header === 'titre' => 'titre_code',
            in_array($header, ['chap', 'chapitre'], true) => 'chapitre_code',
            in_array($header, ['art', 'article'], true) => 'article_code',
            in_array($header, ['parag', 'paragraphe'], true) => 'paragraphe_code',
            $header === 'code_action' => 'code_action',
            $header === 'intitules' => 'intitules',
            str_contains($header, 'budget_2025') && str_contains($header, 'realise') => 'realisation_annee_precedente',
            str_contains($header, 'budget_2025') => 'budget_annee_precedente',
            str_contains($header, 'taux_de_realisation') => 'taux_realisation_precedent',
            str_contains($header, 'previsions_2026') && str_contains($header, 'variation') => 'variation',
            str_contains($header, 'previsions_2026') => 'previsions',
            default => null,
        };
    }

    protected function champsAttendus(?string $type, array $mapping): array
    {
        if ($type !== 'budget') {
            return array_keys(self::SYNONYMS[$type] ?? []);
        }

        $values = array_values(array_filter($mapping));
        $malabo = ['titre_code', 'chapitre_code', 'article_code', 'paragraphe_code', 'code_action', 'intitules', 'budget_annee_precedente', 'realisation_annee_precedente', 'taux_realisation_precedent', 'previsions', 'variation'];
        if (count(array_intersect($values, $malabo)) >= 6) {
            return $malabo;
        }

        return ['code_axe', 'departement', 'nature_depense', 'ligne_budgetaire', 'montant_prevu', 'devise', 'annee', 'source_financement'];
    }

    protected function matchSynonyme(string $entete, ?string $type): bool
    {
        if (! $type) {
            return false;
        }

        $hNorm = $this->normaliser($entete);
        foreach (self::SYNONYMS[$type] ?? [] as $synonymes) {
            foreach ($synonymes as $syn) {
                if ($this->normaliser($syn) === $hNorm) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function normaliser(string $s): string
    {
        return Str::of($s)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
    }

    protected function tableauFeuille(Worksheet $feuille): array
    {
        $highestRow = min($feuille->getHighestRow(), 2000);
        $highestColumn = Coordinate::columnIndexFromString($feuille->getHighestColumn());
        $highestColumn = min($highestColumn, 80);
        $mergedValues = $this->indexCellulesFusionnees($feuille);
        $values = [];

        for ($row = 1; $row <= $highestRow; $row++) {
            $values[$row] = [];
            for ($col = 1; $col <= $highestColumn; $col++) {
                $cell = Coordinate::stringFromColumnIndex($col) . $row;
                $value = $feuille->getCell($cell)->getFormattedValue();
                if (($value === null || $value === '') && isset($mergedValues[$cell])) {
                    $value = $mergedValues[$cell];
                }
                $values[$row][$col] = trim(preg_replace('/\s+/u', ' ', (string) $value));
            }
        }

        return $values;
    }

    protected function indexCellulesFusionnees(Worksheet $feuille): array
    {
        $index = [];
        foreach ($feuille->getMergeCells() as $range) {
            [$start, $end] = Coordinate::rangeBoundaries($range);
            $startCell = Coordinate::stringFromColumnIndex($start[0]) . $start[1];
            $value = (string) $feuille->getCell($startCell)->getFormattedValue();

            for ($row = $start[1]; $row <= $end[1]; $row++) {
                for ($col = $start[0]; $col <= $end[0]; $col++) {
                    $index[Coordinate::stringFromColumnIndex($col) . $row] = $value;
                }
            }
        }

        return $index;
    }

    protected function detecterZoneTabulaire(string $nomFeuille, array $data): array
    {
        $bestRow = 1;
        $bestScore = -1;

        foreach (array_slice($data, 0, 60, true) as $rowNumber => $row) {
            $combined = $this->normaliser(implode(' ', array_filter($row)));
            $score = 0;
            foreach (['titre', 'chap', 'art', 'parag', 'code_action', 'intitules', 'budget', 'previsions', 'variation', 'libelle', 'code'] as $needle) {
                if (str_contains($combined, $needle)) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRow = (int) $rowNumber;
            }
        }

        $isBudgetMalabo = str_contains($this->normaliser($nomFeuille), 'budget') && $bestScore >= 5;
        $headerRows = $isBudgetMalabo ? [$bestRow, $bestRow + 1] : [$bestRow];

        return [
            'ligne_titre' => $bestRow > 1 ? 1 : null,
            'ligne_entete' => $bestRow,
            'lignes_entete' => $headerRows,
            'ligne_debut_donnees' => max($headerRows) + 1,
            'lignes_decoratives_ignorees' => max(0, $bestRow - 1),
            'type_zone' => $isBudgetMalabo ? 'budget_malabo_2026' : 'table_standard',
        ];
    }

    protected function entetesDepuisZone(array $data, array $zone): array
    {
        $headers = [];
        $rows = $zone['lignes_entete'] ?? [$zone['ligne_entete']];
        $maxCol = max(array_map(fn ($row) => count($row), $data ?: [[]]));

        for ($col = 1; $col <= $maxCol; $col++) {
            $parts = [];
            foreach ($rows as $rowNumber) {
                $value = trim((string) ($data[$rowNumber][$col] ?? ''));
                if ($value !== '' && ! in_array($value, $parts, true)) {
                    $parts[] = $value;
                }
            }
            $label = trim(implode(' ', $parts));
            if ($label !== '') {
                $headers[] = $label;
            }
        }

        return array_values($headers);
    }

    protected function compterLignesDonnees(array $data, array $zone): int
    {
        $count = 0;
        foreach ($data as $rowNumber => $row) {
            if ($rowNumber < $zone['ligne_debut_donnees']) {
                continue;
            }
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) > 0) {
                $count++;
            }
        }

        return $count;
    }
}
