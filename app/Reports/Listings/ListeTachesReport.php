<?php

namespace App\Reports\Listings;

use App\Models\Tache;
use Illuminate\Support\Collection;

class ListeTachesReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_taches';
    }

    public function titre(): string
    {
        return 'Liste des tâches';
    }

    public function description(): string
    {
        return 'Tâches opérationnelles avec responsables, calendrier et statut.';
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Code', 'key' => 'code', 'width' => '10%'],
            ['label' => 'Libellé', 'key' => 'libelle'],
            ['label' => 'Activité', 'key' => 'activite', 'width' => '12%'],
            ['label' => 'Statut', 'key' => 'statut', 'kind' => 'badge', 'width' => '10%'],
            ['label' => 'Début', 'key' => 'date_debut', 'width' => '8%'],
            ['label' => 'Fin', 'key' => 'date_fin', 'width' => '8%'],
            ['label' => 'Assigné à', 'key' => 'assigne', 'width' => '14%'],
            ['label' => 'Avancement', 'key' => 'taux_execution', 'kind' => 'progress', 'width' => '13%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        $q = Tache::query()->with(['activite:id,code', 'assigneA:id,name']);

        if ($search = $this->filtre($filtres, 'q')) {
            $q->where(fn ($w) => $w->where('libelle', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
        }
        if ($statut = $this->filtre($filtres, 'statut')) {
            $q->where('statut', $statut);
        }

        return $q->orderBy('date_debut')->limit(3000)->get()->map(fn ($t) => [
            'code' => $t->code,
            'libelle' => $t->libelle,
            'activite' => $t->activite?->code ?? '—',
            'statut' => $t->statut,
            'date_debut' => $t->date_debut?->format('d/m/Y') ?? '—',
            'date_fin' => $t->date_fin?->format('d/m/Y') ?? '—',
            'assigne' => $t->assigneA?->name ?? '—',
            'taux_execution' => round((float) $t->taux_execution, 1),
        ]);
    }
}
