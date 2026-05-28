<?php

namespace App\Reports\Performance;

use App\Models\Tache;
use App\Reports\Report;

class FicheTacheReport extends Report
{
    public function key(): string
    {
        return 'fiche_tache';
    }

    public function titre(): string
    {
        return 'Fiche Tâche';
    }

    public function description(): string
    {
        return 'Fiche d\'une tâche avec hiérarchie RBM complète, assignation et délais.';
    }

    public function categorie(): string
    {
        return self::CAT_OPERATIONNEL;
    }

    public function template(): string
    {
        return 'reports.performance.tache';
    }

    public function icone(): string
    {
        return 'ListChecks';
    }

    public function filtres(): array
    {
        return [
            [
                'key' => 'tache_id',
                'label' => 'Tâche',
                'type' => 'select',
                'required' => true,
                'options' => Tache::orderBy('ordre')->limit(500)->get(['id', 'code', 'libelle'])
                    ->map(fn ($t) => ['value' => $t->id, 'label' => "{$t->code} — {$t->libelle}"])->toArray(),
            ],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $tacheId = $filtres['tache_id'] ?? Tache::value('id');
        $tache = Tache::with([
            'activite:id,code,libelle,sous_produit_id',
            'activite.sousProduit:id,code,libelle,produit_id',
            'activite.sousProduit.produit:id,code,libelle,axe_id',
            'activite.sousProduit.produit.axe:id,code,libelle,papa_id',
            'activite.sousProduit.produit.axe.papa:id,annee',
            'responsable:id,name,fonction',
            'assigneA:id,name,fonction',
        ])->findOrFail($tacheId);

        return ['tache' => $tache];
    }
}
