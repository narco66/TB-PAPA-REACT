<?php

namespace App\Reports\Listings;

use App\Models\Axe;
use App\Models\Papa;
use Illuminate\Support\Collection;

class ListeAxesReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_axes';
    }

    public function titre(): string
    {
        return 'Liste des axes stratégiques';
    }

    public function description(): string
    {
        return 'Axes stratégiques RBM/GAR avec département, responsable et taux d\'exécution.';
    }

    public function filtres(): array
    {
        return [
            ['key' => 'papa_id', 'label' => 'PAPA', 'type' => 'select',
                'options' => Papa::orderByDesc('annee')->get(['id', 'annee'])
                    ->map(fn ($p) => ['value' => $p->id, 'label' => "PAPA {$p->annee}"])->toArray()],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Code', 'key' => 'code', 'width' => '10%'],
            ['label' => 'Libellé', 'key' => 'libelle'],
            ['label' => 'Département', 'key' => 'departement', 'width' => '14%'],
            ['label' => 'Responsable', 'key' => 'responsable', 'width' => '15%'],
            ['label' => 'Statut', 'key' => 'statut', 'kind' => 'badge', 'width' => '10%'],
            ['label' => 'Exécution', 'key' => 'taux_execution', 'kind' => 'progress', 'width' => '15%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        $q = Axe::query()->with(['departement:id,code', 'responsable:id,name', 'papa:id,annee']);

        if ($papaId = $this->filtre($filtres, 'papa_id')) {
            $q->where('papa_id', $papaId);
        }
        if ($search = $this->filtre($filtres, 'q')) {
            $q->where(fn ($w) => $w
                ->where('libelle', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"));
        }
        if ($statut = $this->filtre($filtres, 'statut')) {
            $q->where('statut', $statut);
        }

        return $q->orderBy('ordre')->limit(500)->get()->map(fn ($a) => [
            'code' => $a->code,
            'libelle' => $a->libelle,
            'departement' => $a->departement?->code ?? '—',
            'responsable' => $a->responsable?->name ?? '—',
            'statut' => $a->statut,
            'taux_execution' => round((float) $a->taux_execution, 1),
        ]);
    }
}
