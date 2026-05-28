<?php

namespace App\Reports\Gouvernance;

use App\Models\Departement;
use App\Reports\Report;

class FicheDepartementReport extends Report
{
    public function key(): string
    {
        return 'fiche_departement';
    }

    public function titre(): string
    {
        return 'Fiche Département technique';
    }

    public function description(): string
    {
        return 'Carte d\'identité d\'un département : Commissaire, Directions rattachées, Axes RBM portés.';
    }

    public function categorie(): string
    {
        return self::CAT_GOUVERNANCE;
    }

    public function template(): string
    {
        return 'reports.gouvernance.departement';
    }

    public function icone(): string
    {
        return 'Building2';
    }

    public function filtres(): array
    {
        return [
            [
                'key' => 'departement_id',
                'label' => 'Département',
                'type' => 'select',
                'required' => true,
                'options' => Departement::orderBy('ordre')->get(['id', 'code', 'libelle'])
                    ->map(fn ($d) => ['value' => $d->id, 'label' => "{$d->code} — {$d->libelle}"])->toArray(),
            ],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $depId = $filtres['departement_id'] ?? Departement::value('id');
        $departement = Departement::with([
            'commissaire:id,name,email,fonction,matricule',
            'directions' => fn ($q) => $q->orderBy('libelle'),
            'directions.directeur:id,name',
            'axes' => fn ($q) => $q->orderBy('ordre'),
            'axes.papa:id,annee',
        ])->findOrFail($depId);

        $stats = [
            'nb_directions' => $departement->directions->count(),
            'nb_directions_techniques' => $departement->directions->where('type', 'technique')->count(),
            'nb_directions_appui' => $departement->directions->where('type', 'appui_soutien')->count(),
            'nb_axes' => $departement->axes->count(),
            'taux_execution_moyen' => round((float) ($departement->axes->avg('taux_execution') ?? 0), 1),
        ];

        return compact('departement', 'stats');
    }
}
