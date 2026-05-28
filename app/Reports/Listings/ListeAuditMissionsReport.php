<?php

namespace App\Reports\Listings;

use App\Models\Audit\AuditMission;
use Illuminate\Support\Collection;

class ListeAuditMissionsReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_audit_missions';
    }

    public function titre(): string
    {
        return 'Liste des missions d\'audit';
    }

    public function description(): string
    {
        return 'Missions d\'audit IGS avec type, priorité, statut, période et nombre de constats.';
    }

    public function categorie(): string
    {
        return self::CAT_AUDIT;
    }

    public function permission(): string
    {
        return 'audit_interne.view';
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Code', 'key' => 'code', 'width' => '10%'],
            ['label' => 'Titre', 'key' => 'titre'],
            ['label' => 'Plan', 'key' => 'plan', 'width' => '8%'],
            ['label' => 'Type', 'key' => 'type', 'kind' => 'badge', 'width' => '10%'],
            ['label' => 'Priorité', 'key' => 'priorite', 'kind' => 'badge', 'width' => '8%'],
            ['label' => 'Statut', 'key' => 'statut', 'kind' => 'badge', 'width' => '10%'],
            ['label' => 'Chef', 'key' => 'chef', 'width' => '14%'],
            ['label' => 'Constats', 'key' => 'constats_count', 'kind' => 'num', 'width' => '8%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        $q = AuditMission::query()
            ->with(['plan:id,annee', 'chefMission:id,name'])
            ->withCount('constats as constats_count');

        if ($statut = $this->filtre($filtres, 'statut')) {
            $q->where('statut', $statut);
        }
        if ($type = $this->filtre($filtres, 'type')) {
            $q->where('type', $type);
        }

        return $q->latest()->limit(500)->get()->map(fn ($m) => [
            'code' => $m->code,
            'titre' => $m->titre,
            'plan' => $m->plan?->annee ?? '—',
            'type' => $m->type,
            'priorite' => $m->priorite,
            'statut' => $m->statut,
            'chef' => $m->chefMission?->name ?? '—',
            'constats_count' => $m->constats_count,
        ]);
    }
}
