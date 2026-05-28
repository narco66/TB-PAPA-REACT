<?php

namespace App\Reports\Listings;

use App\Models\Audit\AuditPlan;
use Illuminate\Support\Collection;

class ListeAuditPlansReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_audit_plans';
    }

    public function titre(): string
    {
        return 'Liste des plans d\'audit annuels';
    }

    public function description(): string
    {
        return 'Plans d\'audit interne IGS — programmation annuelle des missions.';
    }

    public function categorie(): string
    {
        return self::CAT_AUDIT;
    }

    public function orientation(): string
    {
        return 'portrait';
    }

    public function permission(): string
    {
        return 'audit_interne.view';
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Année', 'key' => 'annee', 'kind' => 'num', 'width' => '8%'],
            ['label' => 'Libellé', 'key' => 'libelle'],
            ['label' => 'Statut', 'key' => 'statut', 'kind' => 'badge', 'width' => '12%'],
            ['label' => 'Missions', 'key' => 'missions_count', 'kind' => 'num', 'width' => '10%'],
            ['label' => 'Période', 'key' => 'periode', 'width' => '22%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        return AuditPlan::query()
            ->withCount('missions as missions_count')
            ->orderByDesc('annee')
            ->get()
            ->map(fn ($p) => [
                'annee' => $p->annee,
                'libelle' => $p->libelle,
                'statut' => $p->statut,
                'missions_count' => $p->missions_count,
                'periode' => ($p->date_debut?->format('d/m/Y') ?? '—') . ' → ' . ($p->date_fin?->format('d/m/Y') ?? '—'),
            ]);
    }
}
