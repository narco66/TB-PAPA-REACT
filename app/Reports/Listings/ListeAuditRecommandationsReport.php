<?php

namespace App\Reports\Listings;

use App\Models\Audit\AuditRecommandation;
use Illuminate\Support\Collection;

class ListeAuditRecommandationsReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_audit_recommandations';
    }

    public function titre(): string
    {
        return 'Liste des recommandations d\'audit';
    }

    public function description(): string
    {
        return 'Recommandations IIA/IFACI avec priorité, échéance, responsable et avancement.';
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
            ['label' => 'Code', 'key' => 'code', 'width' => '8%'],
            ['label' => 'Libellé', 'key' => 'libelle'],
            ['label' => 'Mission', 'key' => 'mission', 'width' => '10%'],
            ['label' => 'Priorité', 'key' => 'priorite', 'kind' => 'badge', 'width' => '8%'],
            ['label' => 'Statut', 'key' => 'statut', 'kind' => 'badge', 'width' => '12%'],
            ['label' => 'Échéance', 'key' => 'date_echeance', 'width' => '10%'],
            ['label' => 'Responsable', 'key' => 'responsable', 'width' => '14%'],
            ['label' => 'Avancement', 'key' => 'pourcentage_avancement', 'kind' => 'progress', 'width' => '12%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        $q = AuditRecommandation::query()
            ->with(['constat:id,mission_id', 'constat.mission:id,code', 'responsable:id,name']);

        if ($statut = $this->filtre($filtres, 'statut')) {
            $q->where('statut', $statut);
        }
        if ($priorite = $this->filtre($filtres, 'priorite')) {
            $q->where('priorite', $priorite);
        }
        if ($this->filtre($filtres, 'en_retard')) {
            $q->whereDate('date_echeance', '<', now())
                ->whereNotIn('statut', ['verifiee', 'rejetee', 'abandonnee']);
        }

        return $q->orderBy('date_echeance')->limit(1000)->get()->map(fn ($r) => [
            'code' => $r->code,
            'libelle' => $r->libelle,
            'mission' => $r->constat?->mission?->code ?? '—',
            'priorite' => $r->priorite,
            'statut' => $r->statut,
            'date_echeance' => $r->date_echeance?->format('d/m/Y') ?? '—',
            'responsable' => $r->responsable?->name ?? '—',
            'pourcentage_avancement' => (int) $r->pourcentage_avancement,
        ]);
    }
}
