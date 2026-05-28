<?php

namespace App\Services\Budget\Import;

use App\Models\Activite;
use App\Models\Axe;
use App\Models\Budget\BudgetImport;
use App\Models\Budget\BudgetLigne;
use App\Models\Indicateur;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\Tache;
use Illuminate\Support\Facades\DB;

/**
 * Annule un import multi-feuilles : soft-delete toutes les ressources créées
 * par cet import (et ses imports enfants), et marque l'import en `rollback`.
 *
 * Préservation de l'audit trail : les ressources sont soft-deleted (deleted_at)
 * — récupérables si nécessaire via SoftDeletes::restore().
 */
class ImportRollbackService
{
    /** Modèles instrumentés (ordre inverse de la cascade RBM pour respecter les FK). */
    protected array $modeles = [
        Tache::class,
        Activite::class,
        Indicateur::class,
        SousProduit::class,
        Produit::class,
        Axe::class,
        BudgetLigne::class,
    ];

    /**
     * @return array{
     *   import_id: int,
     *   ressources_supprimees: array<string, int>,
     *   total: int
     * }
     */
    public function annulerImport(BudgetImport $import): array
    {
        // Collecter les IDs d'imports concernés (parent + enfants)
        $importIds = collect([$import->id])->merge($import->enfants()->pluck('id'))->all();

        $compteurs = [];
        $total = 0;

        DB::transaction(function () use ($importIds, &$compteurs, &$total) {
            foreach ($this->modeles as $modeleClass) {
                $count = $modeleClass::whereIn('originated_from_import_id', $importIds)->count();
                if ($count > 0) {
                    $modeleClass::whereIn('originated_from_import_id', $importIds)->delete();
                    $compteurs[class_basename($modeleClass)] = $count;
                    $total += $count;
                }
            }
        });

        // Marquer l'import en rollback
        $import->update([
            'statut' => 'rollback',
            'journal' => array_merge((array) ($import->journal ?? []), [
                'rollback' => [
                    'date' => now()->toIso8601String(),
                    'ressources_supprimees' => $compteurs,
                    'total' => $total,
                ],
            ]),
        ]);

        // Marquer les enfants aussi
        $import->enfants()->update(['statut' => 'rollback']);

        return [
            'import_id' => $import->id,
            'ressources_supprimees' => $compteurs,
            'total' => $total,
        ];
    }
}
