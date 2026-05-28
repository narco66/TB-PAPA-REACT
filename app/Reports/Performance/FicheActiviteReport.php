<?php

namespace App\Reports\Performance;

use App\Models\Activite;
use App\Reports\Report;

class FicheActiviteReport extends Report
{
    public function key(): string
    {
        return 'fiche_activite';
    }

    public function titre(): string
    {
        return 'Fiche Activité';
    }

    public function description(): string
    {
        return 'Fiche détaillée d\'une activité : sous-produit parent, tâches, planning, jalons, point focal.';
    }

    public function categorie(): string
    {
        return self::CAT_OPERATIONNEL;
    }

    public function template(): string
    {
        return 'reports.performance.activite';
    }

    public function icone(): string
    {
        return 'GanttChartSquare';
    }

    public function filtres(): array
    {
        return [
            [
                'key' => 'activite_id',
                'label' => 'Activité',
                'type' => 'select',
                'required' => true,
                'options' => Activite::orderBy('ordre')->get(['id', 'code', 'libelle'])
                    ->map(fn ($a) => ['value' => $a->id, 'label' => "{$a->code} — {$a->libelle}"])->toArray(),
            ],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $actId = $filtres['activite_id'] ?? Activite::value('id');
        $activite = Activite::with([
            'sousProduit:id,code,libelle,produit_id',
            'sousProduit.produit:id,code,libelle,axe_id',
            'sousProduit.produit.axe:id,code,libelle,papa_id',
            'sousProduit.produit.axe.papa:id,annee',
            'direction:id,code,libelle',
            'responsable:id,name,fonction',
            'pointFocal:id,name,fonction',
            'taches' => fn ($q) => $q->orderBy('ordre'),
            'taches.assigneA:id,name',
        ])->findOrFail($actId);

        return ['activite' => $activite];
    }
}
