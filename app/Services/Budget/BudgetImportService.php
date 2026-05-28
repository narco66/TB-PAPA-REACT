<?php

namespace App\Services\Budget;

use App\Models\Activite;
use App\Models\Axe;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetImportErreur;
use App\Models\Budget\BudgetImport;
use App\Models\Budget\BudgetLigne;
use App\Models\Budget\BudgetSourceFinancement;
use App\Models\Departement;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\Tache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BudgetImportService
{
    public function __construct(protected BudgetNomenclatureService $nomenclature) {}

    protected array $erreurs = [];

    protected array $avertissements = [];

    protected array $stats = [
        'lues' => 0,
        'valides' => 0,
        'creees' => 0,
        'mises_a_jour' => 0,
        'erreurs' => 0,
        'doublons' => 0,
        'montant_total' => 0.0,
    ];

    protected array $mapping = [];

    protected array $repartitionAxes = [];

    protected array $repartitionDepartements = [];

    protected const REQUIRED = [
        'code_axe',
        'departement',
        'nature_depense',
        'ligne_budgetaire',
        'montant_prevu',
        'devise',
        'annee',
        'source_financement',
    ];

    protected const SYNONYMS = [
        'code_axe' => ['code axe', 'axe code', 'axe_code', 'axe', 'codeaxe'],
        'libelle_axe' => ['libellé axe', 'libelle axe', 'axe libellé', 'axe libelle'],
        'code_produit' => ['code produit', 'produit code', 'produit_code', 'produit'],
        'libelle_produit' => ['libellé produit', 'libelle produit'],
        'code_sous_produit' => ['code sous-produit', 'code sous produit', 'sous_produit_code', 'sous produit code', 'sous-produit'],
        'libelle_sous_produit' => ['libellé sous-produit', 'libelle sous produit', 'libellé sous produit'],
        'code_activite' => ['code activité', 'code activite', 'activite_code', 'activité', 'activite'],
        'libelle_activite' => ['libellé activité', 'libelle activite'],
        'code_tache' => ['code tâche', 'code tache', 'tache_code', 'tâche', 'tache'],
        'libelle_tache' => ['libellé tâche', 'libelle tache'],
        'departement' => ['département', 'departement', 'department', 'direction', 'departement code'],
        'nature_depense' => ['nature dépense', 'nature depense', 'type_budget', 'type budget', 'nature', 'type de dépense', 'type depense'],
        'ligne_budgetaire' => ['ligne budgétaire', 'ligne budgetaire', 'libellé', 'libelle', 'description ligne', 'poste budgétaire', 'poste budgetaire'],
        'montant_prevu' => ['montant prévu', 'montant prevu', 'montant_total', 'montant total', 'budget', 'total', 'montant'],
        'devise' => ['devise', 'currency', 'monnaie'],
        'annee' => ['année', 'annee', 'exercice', 'year'],
        'source_financement' => ['source financement', 'source_financement', 'source de financement', 'bailleur', 'financement'],
        'observations' => ['observations', 'observation', 'commentaire', 'commentaires', 'notes'],
    ];

    public function importer(string $cheminFichier, BudgetExercice $exercice, ?int $userId = null, bool $dryRun = false, ?string $feuilleSource = null): array
    {
        $this->reset();

        if (! file_exists($cheminFichier)) {
            throw new \RuntimeException("Fichier introuvable : {$cheminFichier}");
        }

        $spreadsheet = IOFactory::load($cheminFichier);
        $ws = $feuilleSource
            ? $spreadsheet->getSheetByName($feuilleSource)
            : ($spreadsheet->getSheetByName('Budget à importer') ?? $spreadsheet->getSheetByName('Budget') ?? $spreadsheet->getActiveSheet());

        if (! $ws) {
            throw new \RuntimeException("Feuille introuvable : {$feuilleSource}");
        }

        if ($this->estFeuilleBudgetMalabo($ws)) {
            return $this->importerBudgetMalabo($cheminFichier, $exercice, $userId, $dryRun, $ws);
        }

        $rows = $ws->toArray(null, true, true, true);

        if (count($rows) < 2) {
            $this->erreurs[] = ['ligne' => null, 'champ' => 'fichier', 'message' => 'Fichier vide ou sans données exploitables.'];

            return $this->reponse(false, $exercice);
        }

        $headers = array_shift($rows);
        $this->mapping = $this->detecterMapping($headers);
        $this->validerColonnesObligatoires();

        if ($this->erreurs) {
            return $this->reponse(false, $exercice);
        }

        $refs = $this->referentiels();
        $seen = [];
        $lignesAImporter = [];

        foreach ($rows as $idxRow => $row) {
            if (empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))) {
                continue;
            }

            $this->stats['lues']++;
            $ligneNumero = $idxRow + 1;
            $assoc = $this->associerLigne($row);
            $erreursLigne = [];

            $codeAxe = $this->code($assoc['code_axe'] ?? null);
            $codeProduit = $this->code($assoc['code_produit'] ?? null);
            $codeSousProduit = $this->code($assoc['code_sous_produit'] ?? null);
            $codeActivite = $this->code($assoc['code_activite'] ?? null);
            $codeTache = $this->code($assoc['code_tache'] ?? null);
            $departementKey = $this->key($assoc['departement'] ?? null);
            $sourceKey = $this->key($assoc['source_financement'] ?? null);
            $libelle = trim((string) ($assoc['ligne_budgetaire'] ?? ''));
            $annee = (int) ($assoc['annee'] ?? 0);
            $montant = $this->parseMontant($assoc['montant_prevu'] ?? null);
            $natureDepense = $this->normaliserNatureDepense($assoc['nature_depense'] ?? null);
            $devise = strtoupper(trim((string) ($assoc['devise'] ?? '')));

            if ($codeAxe === '') {
                $erreursLigne[] = ['champ' => 'Code Axe', 'message' => 'Code axe obligatoire.'];
            } elseif (! isset($refs['axes'][$codeAxe])) {
                $erreursLigne[] = ['champ' => 'Code Axe', 'message' => "Axe inexistant : {$codeAxe}."];
            }

            if ($codeProduit !== '' && ! isset($refs['produits'][$codeProduit])) {
                $erreursLigne[] = ['champ' => 'Code Produit', 'message' => "Produit inexistant : {$codeProduit}."];
            }
            if ($codeProduit !== '' && isset($refs['produits'][$codeProduit], $refs['axes'][$codeAxe]) && (int) $refs['produits'][$codeProduit]->axe_id !== (int) $refs['axes'][$codeAxe]->id) {
                $erreursLigne[] = ['champ' => 'Code Produit', 'message' => "Le produit {$codeProduit} n'appartient pas à l'axe {$codeAxe}."];
            }

            if ($codeSousProduit !== '' && ! isset($refs['sous_produits'][$codeSousProduit])) {
                $erreursLigne[] = ['champ' => 'Code Sous-Produit', 'message' => "Sous-produit inexistant : {$codeSousProduit}."];
            }
            if ($codeSousProduit !== '' && $codeProduit !== '' && isset($refs['sous_produits'][$codeSousProduit], $refs['produits'][$codeProduit]) && (int) $refs['sous_produits'][$codeSousProduit]->produit_id !== (int) $refs['produits'][$codeProduit]->id) {
                $erreursLigne[] = ['champ' => 'Code Sous-Produit', 'message' => "Le sous-produit {$codeSousProduit} n'appartient pas au produit {$codeProduit}."];
            }

            if ($codeActivite !== '' && ! isset($refs['activites'][$codeActivite])) {
                $erreursLigne[] = ['champ' => 'Code Activité', 'message' => "Activité inexistante : {$codeActivite}."];
            }
            if ($codeActivite !== '' && $codeSousProduit !== '' && isset($refs['activites'][$codeActivite], $refs['sous_produits'][$codeSousProduit]) && (int) $refs['activites'][$codeActivite]->sous_produit_id !== (int) $refs['sous_produits'][$codeSousProduit]->id) {
                $erreursLigne[] = ['champ' => 'Code Activité', 'message' => "L'activité {$codeActivite} n'appartient pas au sous-produit {$codeSousProduit}."];
            }

            if ($codeTache !== '' && ! isset($refs['taches'][$codeTache])) {
                $erreursLigne[] = ['champ' => 'Code Tâche', 'message' => "Tâche inexistante : {$codeTache}."];
            }
            if ($codeTache !== '' && $codeActivite !== '' && isset($refs['taches'][$codeTache], $refs['activites'][$codeActivite]) && (int) $refs['taches'][$codeTache]->activite_id !== (int) $refs['activites'][$codeActivite]->id) {
                $erreursLigne[] = ['champ' => 'Code Tâche', 'message' => "La tâche {$codeTache} n'appartient pas à l'activité {$codeActivite}."];
            }

            if ($departementKey === '' || ! isset($refs['departements'][$departementKey])) {
                $erreursLigne[] = ['champ' => 'Département', 'message' => 'Département inexistant ou manquant.'];
            }
            if ($sourceKey === '' || ! isset($refs['sources'][$sourceKey])) {
                $erreursLigne[] = ['champ' => 'Source financement', 'message' => 'Source de financement inexistante ou manquante.'];
            }
            if ($libelle === '') {
                $erreursLigne[] = ['champ' => 'Ligne budgétaire', 'message' => 'Libellé de ligne obligatoire.'];
            }
            if ($montant === null) {
                $erreursLigne[] = ['champ' => 'Montant prévu', 'message' => 'Montant invalide.'];
            } elseif ($montant < 0) {
                $erreursLigne[] = ['champ' => 'Montant prévu', 'message' => 'Montant négatif non autorisé sans justification.'];
            }
            if ($devise === '' || ! in_array($devise, ['XAF', 'USD', 'EUR'], true)) {
                $erreursLigne[] = ['champ' => 'Devise', 'message' => 'Devise invalide. Valeurs acceptées : XAF, USD, EUR.'];
            }
            if ($annee !== (int) $exercice->annee) {
                $erreursLigne[] = ['champ' => 'Année', 'message' => "Année {$annee} différente de l'exercice cible {$exercice->annee}."];
            }

            $signature = implode('|', [$exercice->id, $codeAxe, $codeProduit, $codeSousProduit, $codeActivite, $codeTache, Str::lower($libelle), $montant]);
            if (isset($seen[$signature])) {
                $this->stats['doublons']++;
                $erreursLigne[] = ['champ' => 'Doublon', 'message' => "Doublon détecté avec la ligne {$seen[$signature]}."];
            }
            $seen[$signature] = $ligneNumero;

            if ($erreursLigne) {
                $this->stats['erreurs']++;
                foreach ($erreursLigne as $err) {
                    $this->erreurs[] = ['ligne' => $ligneNumero, ...$err];
                }
                continue;
            }

            $source = $refs['sources'][$sourceKey];
            $montantCeeac = $source->estInterne() ? $montant : 0;
            $montantPtf = $source->estInterne() ? 0 : $montant;
            $axe = $refs['axes'][$codeAxe];
            $departement = $refs['departements'][$departementKey];

            $data = [
                'exercice_id' => $exercice->id,
                'parent_id' => null,
                'niveau' => 0,
                'code_action' => $codeTache ?: $codeActivite ?: $codeSousProduit ?: $codeProduit ?: $codeAxe,
                'libelle' => $libelle,
                'description' => trim((string) ($assoc['observations'] ?? '')) ?: null,
                'nature' => str_starts_with($natureDepense, 'recette') ? 'recette' : 'depense',
                'type_budget' => in_array($natureDepense, BudgetLigne::TYPES_BUDGET, true) ? $natureDepense : 'autre',
                'montant_total' => $montant,
                'montant_ceeac_em' => $montantCeeac,
                'montant_ptf' => $montantPtf,
                'source_financement_id' => $source->id,
                'axe_id' => $axe->id,
                'produit_id' => $codeProduit ? $refs['produits'][$codeProduit]?->id : null,
                'sous_produit_id' => $codeSousProduit ? $refs['sous_produits'][$codeSousProduit]?->id : null,
                'activite_id' => $codeActivite ? $refs['activites'][$codeActivite]?->id : null,
                'tache_id' => $codeTache ? $refs['taches'][$codeTache]?->id : null,
                'departement_id' => $departement->id,
                'statut' => 'importe',
                'observations' => $assoc['observations'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ];

            try {
                $data = $this->nomenclature->normaliserLigne($data, $exercice, $userId, false);
            } catch (\InvalidArgumentException $e) {
                $this->stats['erreurs']++;
                $this->erreurs[] = ['ligne' => $ligneNumero, 'champ' => 'Nomenclature', 'message' => $e->getMessage()];
                continue;
            }

            $this->stats['valides']++;
            $this->stats['montant_total'] += $montant;
            $this->repartitionAxes[$codeAxe] = ($this->repartitionAxes[$codeAxe] ?? 0) + $montant;
            $this->repartitionDepartements[$departement->code] = ($this->repartitionDepartements[$departement->code] ?? 0) + $montant;
            $lignesAImporter[] = $data;
        }

        $succes = empty($this->erreurs);
        $statut = $dryRun ? ($succes ? 'prevu' : 'echec') : ($succes ? 'en_cours' : 'echec');
        $import = BudgetImport::create([
            'exercice_id' => $exercice->id,
            'fichier_nom' => basename($cheminFichier),
            'chemin_stockage' => $cheminFichier,
            'taille_octets' => filesize($cheminFichier),
            'hash_sha256' => hash_file('sha256', $cheminFichier),
            'feuille_source' => $ws->getTitle(),
            'type_donnees' => 'budget',
            'statut' => $statut,
            'nb_lignes_lues' => $this->stats['lues'],
            'nb_erreurs' => $this->stats['erreurs'],
            'journal' => $this->journal(),
            'execute_par_id' => $userId,
            'execute_at' => now(),
        ]);

        if (! $succes || $dryRun) {
            return $this->reponse($succes, $exercice, $import);
        }

        DB::transaction(function () use ($lignesAImporter, $exercice, $userId) {
            foreach ($lignesAImporter as $data) {
                $data = $this->nomenclature->normaliserLigne($data, $exercice, $userId, true);
                BudgetLigne::create($data);
                $this->stats['creees']++;
            }
            $exercice->recalculerTotaux();
        });

        $import->update([
            'statut' => 'reussi',
            'nb_lignes_creees' => $this->stats['creees'],
            'nb_lignes_mises_a_jour' => $this->stats['mises_a_jour'],
            'nb_erreurs' => $this->stats['erreurs'],
            'journal' => $this->journal(),
        ]);

        return $this->reponse(true, $exercice, $import);
    }

    protected function estFeuilleBudgetMalabo($ws): bool
    {
        if ($ws->getTitle() !== 'Budget') {
            return false;
        }

        $text = '';
        for ($row = 1; $row <= min(8, $ws->getHighestRow()); $row++) {
            for ($col = 1; $col <= 12; $col++) {
                $text .= ' ' . $ws->getCell([$col, $row])->getFormattedValue();
            }
        }
        $normalise = $this->normalize($text);

        return str_contains($normalise, 'titre')
            && str_contains($normalise, 'chap')
            && str_contains($normalise, 'code action')
            && str_contains($normalise, 'previsions 2026');
    }

    protected function importerBudgetMalabo(string $cheminFichier, BudgetExercice $exercice, ?int $userId, bool $dryRun, $ws): array
    {
        $this->reset();
        $lignesAImporter = [];
        $hierarchie = [
            'titre_code' => null,
            'chapitre_code' => null,
            'article_code' => null,
            'paragraphe_code' => null,
        ];

        for ($row = 5; $row <= $ws->getHighestRow(); $row++) {
            $brut = [
                'titre_code' => $this->cell($ws, 1, $row),
                'chapitre_code' => $this->cell($ws, 2, $row),
                'article_code' => $this->cell($ws, 3, $row),
                'paragraphe_code' => $this->cell($ws, 4, $row),
                'code_action' => $this->cell($ws, 5, $row),
                'libelle' => $this->cell($ws, 6, $row),
                'budget_annee_precedente' => $this->cell($ws, 7, $row),
                'realisation_annee_precedente' => $this->cell($ws, 8, $row),
                'taux_realisation_precedent' => $this->cell($ws, 9, $row),
                'montant_prevu' => $this->cell($ws, 10, $row),
                'variation' => $this->cell($ws, 11, $row),
            ];

            if ($this->ligneVide($brut)) {
                continue;
            }

            $this->stats['lues']++;

            foreach (['titre_code', 'chapitre_code', 'article_code', 'paragraphe_code'] as $key) {
                if ($brut[$key] !== '') {
                    $hierarchie[$key] = $brut[$key];
                }
            }

            $libelle = $brut['libelle'];
            if ($libelle === '') {
                $this->avertissements[] = [
                    'ligne' => $row,
                    'champ' => 'Intitulés',
                    'message' => 'Ligne ignorée : aucun libellé exploitable.',
                ];
                continue;
            }

            $budgetPrecedent = $this->parseMontant($brut['budget_annee_precedente']);
            $realisePrecedent = $this->parseMontant($brut['realisation_annee_precedente']);
            $prevision = $this->parseMontant($brut['montant_prevu']);
            $taux = $this->parsePourcentage($brut['taux_realisation_precedent']);
            $variation = $this->parsePourcentage($brut['variation']);

            if ($this->estLignePresentation($libelle, $brut, $prevision, $budgetPrecedent, $realisePrecedent)) {
                $this->avertissements[] = [
                    'ligne' => $row,
                    'champ' => 'Intitulés',
                    'message' => 'Ligne de présentation, regroupement ou total ignorée.',
                ];
                continue;
            }

            if ($prevision === null && $budgetPrecedent === null && $realisePrecedent === null) {
                $this->avertissements[] = [
                    'ligne' => $row,
                    'champ' => 'Montants',
                    'message' => 'Ligne ignorée : aucun montant exploitable après normalisation.',
                ];
                continue;
            }

            if ($prevision !== null && $prevision < 0) {
                $this->ajouterErreurLigne($row, 'Prévisions 2026', 'Montant négatif non autorisé sans justification.', 'depassement_borne', null);
                continue;
            }

            if ($taux !== null && $realisePrecedent !== null && $budgetPrecedent !== null && $budgetPrecedent > 0) {
                $attendu = round(($realisePrecedent / $budgetPrecedent) * 100, 2);
                if (abs($attendu - $taux) > 1) {
                    $this->avertissements[] = [
                        'ligne' => $row,
                        'champ' => 'Taux de réalisation',
                        'message' => "Taux incohérent : {$taux}% lu, {$attendu}% calculé.",
                    ];
                }
            }

            $codeAction = $this->code($brut['code_action']);
            $rbm = $this->rattachementRbmDepuisCodeAction($codeAction);
            if ($codeAction !== '' && ! $rbm['trouve']) {
                $this->avertissements[] = [
                    'ligne' => $row,
                    'champ' => 'Code Action',
                    'message' => "Aucun rattachement RBM/GAR trouvé pour le code action {$codeAction}. La ligne est conservée sans lien activité/tâche.",
                ];
            }

            $signature = [
                'exercice_id' => $exercice->id,
                'titre_code' => $hierarchie['titre_code'],
                'chapitre_code' => $hierarchie['chapitre_code'],
                'article_code' => $hierarchie['article_code'],
                'paragraphe_code' => $hierarchie['paragraphe_code'],
                'code_action' => $codeAction ?: null,
                'libelle' => $libelle,
            ];

            if ($this->budgetExisteDeja($signature)) {
                $this->stats['doublons']++;
                $this->ajouterErreurLigne($row, 'Doublon', 'Une ligne budgétaire équivalente existe déjà pour cet exercice.', 'doublon', 'Confirmer explicitement une mise à jour avant remplacement.');
                continue;
            }

            $montantTotal = $prevision ?? $budgetPrecedent ?? $realisePrecedent ?? 0.0;
            $data = [
                ...$signature,
                'parent_id' => null,
                'niveau' => $this->niveauBudget($hierarchie, $codeAction),
                'code_projet' => null,
                'description' => null,
                'nature' => $this->natureBudget($hierarchie, $libelle),
                'type_budget' => $this->typeBudget($hierarchie, $libelle),
                'montant_total' => $montantTotal,
                'montant_ceeac_em' => $this->estRecetteExterne($hierarchie, $libelle) ? 0 : $montantTotal,
                'montant_ptf' => $this->estRecetteExterne($hierarchie, $libelle) ? $montantTotal : 0,
                'devise' => 'XAF',
                'budget_annee_precedente' => $budgetPrecedent ?? 0,
                'realisation_annee_precedente' => $realisePrecedent ?? 0,
                'taux_realisation_precedent' => $taux,
                'variation' => $variation,
                'axe_id' => $rbm['axe_id'],
                'produit_id' => $rbm['produit_id'],
                'sous_produit_id' => $rbm['sous_produit_id'],
                'activite_id' => $rbm['activite_id'],
                'tache_id' => $rbm['tache_id'],
                'statut' => 'importe',
                'ordre' => $row,
                'observations' => 'Import feuille Budget Malabo 2026.',
                'created_by' => $userId,
                'updated_by' => $userId,
            ];

            try {
                $data = $this->nomenclature->normaliserLigne($data, $exercice, $userId, false);
            } catch (\InvalidArgumentException $e) {
                $this->ajouterErreurLigne($row, 'Nomenclature', $e->getMessage(), 'incoherence_hierarchique', 'Corriger le code budgétaire ou ses parents.');
                continue;
            }

            $this->stats['valides']++;
            $this->stats['montant_total'] += $montantTotal;
            $this->repartitionAxes[$rbm['axe_code'] ?? 'Sans rattachement RBM'] = ($this->repartitionAxes[$rbm['axe_code'] ?? 'Sans rattachement RBM'] ?? 0) + $montantTotal;
            $lignesAImporter[] = $data;
        }

        $succes = empty($this->erreurs);
        $import = BudgetImport::create([
            'exercice_id' => $exercice->id,
            'fichier_nom' => basename($cheminFichier),
            'chemin_stockage' => $cheminFichier,
            'taille_octets' => filesize($cheminFichier),
            'hash_sha256' => hash_file('sha256', $cheminFichier),
            'feuille_source' => $ws->getTitle(),
            'type_donnees' => 'budget',
            'statut' => $dryRun ? ($succes ? 'prevu' : 'echec') : ($succes ? 'en_cours' : 'echec'),
            'nb_lignes_lues' => $this->stats['lues'],
            'nb_erreurs' => $this->stats['erreurs'],
            'journal' => $this->journal() + [
                'format_detecte' => 'budget_malabo_2026',
                'feuilles_traitees' => [$ws->getTitle()],
            ],
            'execute_par_id' => $userId,
            'execute_at' => now(),
        ]);
        $this->persisterErreursStructurees($import, $ws->getTitle());

        if (! $succes || $dryRun) {
            return $this->reponse($succes, $exercice, $import);
        }

        DB::transaction(function () use ($lignesAImporter, $exercice, $import) {
            foreach ($lignesAImporter as $data) {
                $data = $this->nomenclature->normaliserLigne($data, $exercice, $import->execute_par_id, true);
                $ligne = BudgetLigne::create($data);
                if (Schema::hasColumn('budget_lignes', 'originated_from_import_id')) {
                    $ligne->forceFill(['originated_from_import_id' => $import->id])->saveQuietly();
                }
                $this->stats['creees']++;
            }
            $exercice->recalculerTotaux();
        });

        $import->update([
            'statut' => 'reussi',
            'nb_lignes_creees' => $this->stats['creees'],
            'nb_lignes_mises_a_jour' => $this->stats['mises_a_jour'],
            'nb_erreurs' => $this->stats['erreurs'],
            'journal' => $this->journal() + [
                'format_detecte' => 'budget_malabo_2026',
                'feuilles_traitees' => [$ws->getTitle()],
            ],
        ]);

        return $this->reponse(true, $exercice, $import);
    }

    protected function cell($ws, int $col, int $row): string
    {
        $value = $ws->getCell([$col, $row])->getFormattedValue();

        return trim(preg_replace('/\s+/u', ' ', (string) $value));
    }

    protected function ligneVide(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function ajouterErreurLigne(?int $ligne, string $champ, string $message, string $regle = 'autre', ?string $correction = null): void
    {
        $this->stats['erreurs']++;
        $this->erreurs[] = [
            'ligne' => $ligne,
            'champ' => $champ,
            'message' => $message,
            'regle' => $regle,
            'correction_suggeree' => $correction,
        ];
    }

    protected function parsePourcentage(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '' || trim((string) $value) === '-') {
            return null;
        }

        $raw = trim((string) $value);
        $isPercent = str_contains($raw, '%');
        $number = $this->parseMontant(str_replace('%', '', $raw));
        if ($number === null) {
            return null;
        }

        return $isPercent ? $number : ($number <= 1 ? $number * 100 : $number);
    }

    protected function estLignePresentation(string $libelle, array $brut, ?float $prevision, ?float $budgetPrecedent, ?float $realisePrecedent): bool
    {
        $normalise = $this->normalize($libelle);
        $hasAction = $this->code($brut['code_action'] ?? '') !== '';
        $hasBudgetCode = collect(['titre_code', 'chapitre_code', 'article_code', 'paragraphe_code'])
            ->contains(fn ($key) => trim((string) ($brut[$key] ?? '')) !== '');

        if (str_starts_with($normalise, 'total') || str_contains($normalise, 'total des')) {
            return true;
        }

        if (! $hasAction && ! $hasBudgetCode && ($prevision !== null || $budgetPrecedent !== null || $realisePrecedent !== null)) {
            return true;
        }

        return ! $hasAction && preg_match('/^[A-Z0-9\s\'\-]+$/u', $libelle) === 1 && mb_strlen($libelle) > 8;
    }

    protected function rattachementRbmDepuisCodeAction(string $codeAction): array
    {
        $empty = [
            'trouve' => false,
            'axe_id' => null,
            'produit_id' => null,
            'sous_produit_id' => null,
            'activite_id' => null,
            'tache_id' => null,
            'axe_code' => null,
        ];

        if ($codeAction === '') {
            return $empty;
        }

        $tache = Tache::with('activite.sousProduit.produit.axe')->where('code', $codeAction)->first();
        if ($tache) {
            $activite = $tache->activite;
            $sousProduit = $activite?->sousProduit;
            $produit = $sousProduit?->produit;
            $axe = $produit?->axe;

            return [
                'trouve' => true,
                'axe_id' => $axe?->id,
                'produit_id' => $produit?->id,
                'sous_produit_id' => $sousProduit?->id,
                'activite_id' => $activite?->id,
                'tache_id' => $tache->id,
                'axe_code' => $axe?->code,
            ];
        }

        $activite = Activite::with('sousProduit.produit.axe')->where('code', $codeAction)->first();
        if ($activite) {
            $sousProduit = $activite->sousProduit;
            $produit = $sousProduit?->produit;
            $axe = $produit?->axe;

            return [
                'trouve' => true,
                'axe_id' => $axe?->id,
                'produit_id' => $produit?->id,
                'sous_produit_id' => $sousProduit?->id,
                'activite_id' => $activite->id,
                'tache_id' => null,
                'axe_code' => $axe?->code,
            ];
        }

        return $empty;
    }

    protected function budgetExisteDeja(array $signature): bool
    {
        return BudgetLigne::query()
            ->where('exercice_id', $signature['exercice_id'])
            ->where('libelle', $signature['libelle'])
            ->where(function ($query) use ($signature) {
                foreach (['titre_code', 'chapitre_code', 'article_code', 'paragraphe_code', 'code_action'] as $field) {
                    $value = $signature[$field] ?? null;
                    $value === null
                        ? $query->whereNull($field)
                        : $query->where($field, $value);
                }
            })
            ->exists();
    }

    protected function niveauBudget(array $hierarchie, string $codeAction): int
    {
        if ($codeAction !== '') {
            return 5;
        }
        foreach (['paragraphe_code' => 4, 'article_code' => 3, 'chapitre_code' => 2, 'titre_code' => 1] as $field => $niveau) {
            if (! empty($hierarchie[$field])) {
                return $niveau;
            }
        }

        return 0;
    }

    protected function natureBudget(array $hierarchie, string $libelle): string
    {
        return str_contains($this->normalize(($hierarchie['titre_code'] ?? '') . ' ' . $libelle), 'recette') ? 'recette' : 'depense';
    }

    protected function typeBudget(array $hierarchie, string $libelle): string
    {
        if ($this->natureBudget($hierarchie, $libelle) === 'recette') {
            return $this->estRecetteExterne($hierarchie, $libelle) ? 'recette_externe' : 'recette_interne';
        }

        return 'fonctionnement';
    }

    protected function estRecetteExterne(array $hierarchie, string $libelle): bool
    {
        $text = $this->normalize(($hierarchie['titre_code'] ?? '') . ' ' . $libelle);

        return str_contains($text, 'recettes externes') || str_contains($text, 'dons') || str_contains($text, 'ptf');
    }

    protected function persisterErreursStructurees(BudgetImport $import, ?string $feuille): void
    {
        if (! class_exists(BudgetImportErreur::class)) {
            return;
        }

        foreach ($this->erreurs as $erreur) {
            BudgetImportErreur::create([
                'import_id' => $import->id,
                'feuille' => $feuille,
                'ligne' => $erreur['ligne'] ?? null,
                'colonne' => $erreur['champ'] ?? null,
                'valeur_fautive' => null,
                'gravite' => 'erreur',
                'regle' => $erreur['regle'] ?? 'autre',
                'message' => $erreur['message'] ?? 'Erreur import.',
                'correction_suggeree' => $erreur['correction_suggeree'] ?? null,
            ]);
        }
    }

    protected function reset(): void
    {
        $this->erreurs = [];
        $this->avertissements = [];
        $this->mapping = [];
        $this->repartitionAxes = [];
        $this->repartitionDepartements = [];
        $this->stats = ['lues' => 0, 'valides' => 0, 'creees' => 0, 'mises_a_jour' => 0, 'erreurs' => 0, 'doublons' => 0, 'montant_total' => 0.0];
    }

    protected function detecterMapping(array $headers): array
    {
        $mapping = [];
        foreach ($headers as $col => $header) {
            $normalized = $this->normalize((string) $header);
            if ($normalized === '') {
                continue;
            }
            foreach (self::SYNONYMS as $canonical => $synonyms) {
                if (in_array($normalized, array_map(fn ($s) => $this->normalize($s), $synonyms), true)) {
                    $mapping[$canonical] = $col;
                    break;
                }
            }
        }

        return $mapping;
    }

    protected function validerColonnesObligatoires(): void
    {
        foreach (self::REQUIRED as $field) {
            if (! isset($this->mapping[$field])) {
                $this->erreurs[] = ['ligne' => null, 'champ' => $field, 'message' => 'Colonne obligatoire manquante.'];
            }
        }
    }

    protected function associerLigne(array $row): array
    {
        $assoc = [];
        foreach ($this->mapping as $field => $col) {
            $val = $row[$col] ?? null;
            $assoc[$field] = is_string($val) ? trim($val) : $val;
        }

        return $assoc;
    }

    protected function referentiels(): array
    {
        return [
            'axes' => Axe::get(['id', 'code'])->keyBy(fn ($a) => $this->code($a->code)),
            'produits' => Produit::get(['id', 'code', 'axe_id'])->keyBy(fn ($p) => $this->code($p->code)),
            'sous_produits' => SousProduit::get(['id', 'code', 'produit_id'])->keyBy(fn ($sp) => $this->code($sp->code)),
            'activites' => Activite::get(['id', 'code', 'sous_produit_id'])->keyBy(fn ($a) => $this->code($a->code)),
            'taches' => Tache::get(['id', 'code', 'activite_id'])->keyBy(fn ($t) => $this->code($t->code)),
            'departements' => Departement::get(['id', 'code', 'libelle'])->flatMap(fn ($d) => [
                $this->key($d->code) => $d,
                $this->key($d->libelle) => $d,
                $this->key("{$d->code} - {$d->libelle}") => $d,
            ]),
            'sources' => BudgetSourceFinancement::where('actif', true)->get(['id', 'code', 'libelle', 'type'])->flatMap(fn ($s) => [
                $this->key($s->code) => $s,
                $this->key($s->libelle) => $s,
                $this->key("{$s->code} - {$s->libelle}") => $s,
            ]),
        ];
    }

    protected function parseMontant(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        $raw = trim((string) $value);
        if ($raw === '' || $raw === '-' || str_starts_with($raw, '#')) {
            return null;
        }

        $raw = str_replace(["\xc2\xa0", ' '], '', $raw);
        if (preg_match('/^-?\d{1,3}(,\d{3})+(\.\d+)?$/', $raw)) {
            $clean = str_replace(',', '', $raw);
        } elseif (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $raw)) {
            $clean = str_replace('.', '', $raw);
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '.', $raw);
        }

        return is_numeric($clean) ? (float) $clean : null;
    }

    protected function normaliserNatureDepense(mixed $value): string
    {
        $normalized = $this->normalize((string) $value);
        $map = [
            'fonctionnement' => 'fonctionnement',
            'investissement' => 'investissement',
            'equipement' => 'equipement',
            'dotation' => 'dotation',
            'dette' => 'dette',
            'transfert' => 'transfert',
            'recette interne' => 'recette_interne',
            'recette externe' => 'recette_externe',
            'recette' => 'recette_interne',
        ];

        return $map[$normalized] ?? 'autre';
    }

    protected function normalize(string $value): string
    {
        return Str::of($value)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->toString();
    }

    protected function code(mixed $value): string
    {
        return Str::upper(trim((string) $value));
    }

    protected function key(mixed $value): string
    {
        return $this->normalize((string) $value);
    }

    protected function journal(): array
    {
        return [
            'mapping' => $this->mapping,
            'erreurs' => $this->erreurs,
            'avertissements' => $this->avertissements,
            'stats' => $this->stats,
            'repartition_axes' => $this->repartitionAxes,
            'repartition_departements' => $this->repartitionDepartements,
        ];
    }

    protected function reponse(bool $succes, BudgetExercice $exercice, ?BudgetImport $import = null): array
    {
        return [
            'succes' => $succes,
            'erreurs' => $this->erreurs,
            'avertissements' => $this->avertissements,
            'stats' => $this->stats,
            'mapping' => $this->mapping,
            'repartition_axes' => $this->repartitionAxes,
            'repartition_departements' => $this->repartitionDepartements,
            'import_id' => $import?->id,
            'exercice' => [
                'id' => $exercice->id,
                'annee' => $exercice->annee,
                'libelle' => $exercice->libelle,
            ],
        ];
    }
}
