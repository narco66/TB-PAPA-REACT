<?php

namespace App\Reports\Listings;

use App\Models\Alerte;
use Illuminate\Support\Collection;

class ListeAlertesReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_alertes';
    }

    public function titre(): string
    {
        return 'Liste des alertes';
    }

    public function description(): string
    {
        return 'Alertes système automatiques et manuelles avec niveau, catégorie et statut.';
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Niveau', 'key' => 'niveau', 'kind' => 'badge', 'width' => '10%'],
            ['label' => 'Catégorie', 'key' => 'categorie', 'width' => '12%'],
            ['label' => 'Titre', 'key' => 'titre'],
            ['label' => 'Statut', 'key' => 'statut', 'kind' => 'badge', 'width' => '10%'],
            ['label' => 'Assigné à', 'key' => 'assigneeA', 'width' => '14%'],
            ['label' => 'Émise le', 'key' => 'created_at', 'width' => '10%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        $q = Alerte::query()->with('assigneeA:id,name');

        if ($niveau = $this->filtre($filtres, 'niveau')) {
            $q->where('niveau', $niveau);
        }
        if ($statut = $this->filtre($filtres, 'statut')) {
            $q->where('statut', $statut);
        }
        if ($search = $this->filtre($filtres, 'q')) {
            $q->where('titre', 'like', "%{$search}%");
        }

        return $q->latest()->limit(500)->get()->map(fn ($a) => [
            'niveau' => $a->niveau,
            'categorie' => $a->categorie ?? '—',
            'titre' => $a->titre,
            'statut' => $a->statut,
            'assigneeA' => $a->assigneeA?->name ?? '—',
            'created_at' => $a->created_at?->format('d/m/Y H:i'),
        ]);
    }
}
