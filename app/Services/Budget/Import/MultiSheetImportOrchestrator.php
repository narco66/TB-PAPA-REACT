<?php

namespace App\Services\Budget\Import;

use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetImport;
use App\Models\Papa;

/**
 * Orchestrateur d'import multi-feuilles.
 *
 * Reçoit un fichier + la liste des feuilles à importer (avec leur type + mapping optionnel),
 * et délègue à chaque ImportService spécialisé.
 *
 * Ordonne automatiquement les feuilles dans l'ordre de la chaîne RBM :
 *   Axes → Produits → Sous-Produits → Activités → Tâches → Indicateurs → Budget
 *
 * Crée un BudgetImport "parent" qui chapeaute les enfants (1 enfant par feuille traitée).
 */
class MultiSheetImportOrchestrator
{
    /** Ordre canonique d'exécution (cascade RBM). */
    public const ORDRE_EXECUTION = [
        'axes', 'produits', 'sous_produits', 'activites', 'taches', 'indicateurs', 'budget',
    ];

    /** Map type → service. */
    public const SERVICES = [
        'axes' => AxeImportService::class,
        'produits' => ProduitImportService::class,
        'sous_produits' => SousProduitImportService::class,
        'activites' => ActiviteImportService::class,
        'taches' => TacheImportService::class,
        'indicateurs' => IndicateurImportService::class,
        // 'budget' : géré par le BudgetImportService existant, en complément
    ];

    /**
     * @param array<int, array{nom: string, type: string, mapping?: array<string, string>}> $feuilles feuilles sélectionnées
     */
    public function executer(
        BudgetExercice $exercice,
        string $cheminFichier,
        string $fichierNom,
        int $tailleOctets,
        string $hashSha256,
        int $userId,
        array $feuilles,
        ?int $papaId = null,
        bool $dryRun = false,
    ): BudgetImport {
        // Trier les feuilles selon l'ordre de cascade RBM
        usort($feuilles, fn ($a, $b) => array_search($a['type'], self::ORDRE_EXECUTION, true)
            <=> array_search($b['type'], self::ORDRE_EXECUTION, true));

        // Trouver le PAPA actif si non fourni
        $papaId = $papaId ?? Papa::actif()->orderByDesc('annee')->value('id');

        $parent = BudgetImport::create([
            'exercice_id' => $exercice->id,
            'fichier_nom' => $fichierNom,
            'chemin_stockage' => $cheminFichier,
            'taille_octets' => $tailleOctets,
            'hash_sha256' => $hashSha256,
            'feuille_source' => null,
            'type_donnees' => 'multi',
            'statut' => 'en_cours',
            'execute_par_id' => $userId,
            'execute_at' => now(),
            'options' => [
                'papa_id' => $papaId,
                'dry_run' => $dryRun,
                'feuilles_demandees' => array_column($feuilles, 'nom'),
            ],
        ]);

        $contexte = [
            'papa_id' => $papaId,
            'exercice_id' => $exercice->id,
            'parent_import_id' => $parent->id,
        ];

        $totalCreees = 0;
        $totalErreurs = 0;
        $totalLues = 0;
        $totalMaj = 0;

        foreach ($feuilles as $feuille) {
            $serviceClass = self::SERVICES[$feuille['type']] ?? null;
            if (! $serviceClass) {
                continue; // Type non géré (ex. 'budget' → délégué au BudgetImportService classique)
            }

            /** @var AbstractEntityImportService $service */
            $service = app($serviceClass);
            $importEnfant = $service->importerFeuille(
                $parent,
                $cheminFichier,
                $feuille['nom'],
                $contexte,
                $feuille['mapping'] ?? null,
                $dryRun,
            );

            $stats = $service->getStats();
            $totalLues += $stats['lues'];
            $totalCreees += $stats['creees'];
            $totalMaj += $stats['mises_a_jour'];
            $totalErreurs += $stats['erreurs'];

            // Si erreur critique sur cette feuille, on arrête la cascade (cohérence référentielle)
            if ($importEnfant->statut === 'echec') {
                break;
            }
        }

        $statutFinal = match (true) {
            $totalErreurs > 0 && ! $dryRun => 'echec',
            $dryRun => 'prevu',
            default => 'reussi',
        };

        $parent->update([
            'statut' => $statutFinal,
            'nb_lignes_lues' => $totalLues,
            'nb_lignes_creees' => $totalCreees,
            'nb_lignes_mises_a_jour' => $totalMaj,
            'nb_erreurs' => $totalErreurs,
            'journal' => [
                'feuilles_traitees' => $parent->enfants()->get()->map(fn ($e) => [
                    'feuille' => $e->feuille_source,
                    'type' => $e->type_donnees,
                    'statut' => $e->statut,
                    'creees' => $e->nb_lignes_creees,
                    'mises_a_jour' => $e->nb_lignes_mises_a_jour,
                    'erreurs' => $e->nb_erreurs,
                ])->all(),
            ],
        ]);

        return $parent->fresh(['enfants.erreurs']);
    }
}
