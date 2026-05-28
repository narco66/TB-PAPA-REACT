<?php

namespace App\Reports\Listings;

use App\Models\Activite;
use Illuminate\Support\Collection;

class ListeActivitesReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_activites';
    }

    public function titre(): string
    {
        return 'Liste des activités';
    }

    public function description(): string
    {
        return 'Activités planifiées avec calendrier, responsable, statut et taux d\'avancement.';
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Code', 'key' => 'code', 'width' => '10%'],
            ['label' => 'Libellé', 'key' => 'libelle'],
            ['label' => 'Sous-Produit', 'key' => 'sp', 'width' => '12%'],
            ['label' => 'Statut', 'key' => 'statut', 'kind' => 'badge', 'width' => '9%'],
            ['label' => 'Risque', 'key' => 'risque', 'kind' => 'badge', 'width' => '8%'],
            ['label' => 'Début', 'key' => 'date_debut', 'width' => '8%'],
            ['label' => 'Fin', 'key' => 'date_fin', 'width' => '8%'],
            ['label' => 'Responsable', 'key' => 'responsable', 'width' => '12%'],
            ['label' => 'Avancement', 'key' => 'taux_execution', 'kind' => 'progress', 'width' => '13%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        $q = Activite::query()->with(['sousProduit:id,code', 'responsable:id,name']);

        if ($statut = $this->filtre($filtres, 'statut')) {
            $q->where('statut', $statut);
        }
        if ($search = $this->filtre($filtres, 'q')) {
            $q->where(fn ($w) => $w->where('libelle', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
        }
        if ($this->filtre($filtres, 'retard')) {
            $q->whereDate('date_fin', '<', now())
                ->whereNotIn('statut', ['realisee', 'annulee'])
                ->where('taux_execution', '<', 100);
        }

        return $q->orderBy('date_debut')->limit(2000)->get()->map(fn ($a) => [
            'code' => $a->code,
            'libelle' => $a->libelle,
            'sp' => $a->sousProduit?->code ?? '—',
            'statut' => $a->statut,
            'risque' => $a->niveau_risque ?? '—',
            'date_debut' => $a->date_debut?->format('d/m/Y') ?? '—',
            'date_fin' => $a->date_fin?->format('d/m/Y') ?? '—',
            'responsable' => $a->responsable?->name ?? '—',
            'taux_execution' => round((float) $a->taux_execution, 1),
        ]);
    }
}
