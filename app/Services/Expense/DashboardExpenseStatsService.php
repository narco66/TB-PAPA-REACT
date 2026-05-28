<?php

namespace App\Services\Expense;

use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetMouvement;
use App\Models\ExpenseRequest;
use App\Models\Reception;
use App\Models\ServiceDoneCertificate;
use Carbon\Carbon;

/**
 * Statistiques temps réel du tableau de bord Chaîne de la dépense.
 *
 * Indicateurs livrés :
 *   - Volumétrie par statut (expressions, engagements, paiements...)
 *   - Cycle IPSAS : engagement → liquidation → ordonnancement → paiement
 *   - Performance par département / par type
 *   - Top fournisseurs, en retard, en attente de validation
 *   - Évolution mensuelle des engagements
 */
class DashboardExpenseStatsService
{
    public function build(?int $exerciceId = null): array
    {
        $exercice = $exerciceId
            ? BudgetExercice::find($exerciceId)
            : BudgetExercice::where('statut', 'valide')->orderByDesc('annee')->first()
                ?? BudgetExercice::orderByDesc('annee')->first();

        return [
            'exercice' => $exercice ? [
                'id' => $exercice->id,
                'annee' => $exercice->annee,
                'libelle' => $exercice->libelle,
                'statut' => $exercice->statut,
            ] : null,
            'exercices_disponibles' => BudgetExercice::orderByDesc('annee')->get(['id', 'annee', 'libelle']),
            'expressions' => $this->expressionsParStatut($exercice?->id),
            'cycle_ipsas' => $this->cycleIpsas($exercice?->id),
            'kpis' => $this->kpis($exercice?->id),
            'par_type_engagement' => $this->parTypeEngagement($exercice?->id),
            'par_departement' => $this->parDepartement($exercice?->id),
            'top_fournisseurs' => $this->topFournisseurs($exercice?->id),
            'en_attente_validation' => $this->enAttenteValidation(),
            'evolution_mensuelle' => $this->evolutionMensuelle($exercice),
            'service_fait_pending' => $this->serviceFaitPending(),
            'receptions_pending' => $this->receptionsPending(),
        ];
    }

    protected function expressionsParStatut(?int $exerciceId): array
    {
        $base = ExpenseRequest::query();
        if ($exerciceId) {
            $base->where('exercice_id', $exerciceId);
        }

        $statuts = ExpenseRequest::STATUTS;
        $counts = (clone $base)->selectRaw('statut, COUNT(*) as nb')
            ->groupBy('statut')->pluck('nb', 'statut')->toArray();

        return collect($statuts)->mapWithKeys(fn ($s) => [$s => (int) ($counts[$s] ?? 0)])->all();
    }

    protected function cycleIpsas(?int $exerciceId): array
    {
        $base = BudgetMouvement::query();
        if ($exerciceId) {
            $base->whereHas('ligne', fn ($q) => $q->where('exercice_id', $exerciceId));
        }

        $rows = (clone $base)->selectRaw('type, COUNT(*) as nb, SUM(montant) as montant')
            ->groupBy('type')->get();

        $cycle = [];
        foreach (['engagement', 'liquidation', 'ordonnancement', 'paiement'] as $type) {
            $row = $rows->firstWhere('type', $type);
            $cycle[$type] = [
                'nb' => (int) ($row?->nb ?? 0),
                'montant' => (float) ($row?->montant ?? 0),
            ];
        }

        return $cycle;
    }

    protected function kpis(?int $exerciceId): array
    {
        $expBase = ExpenseRequest::query();
        if ($exerciceId) {
            $expBase->where('exercice_id', $exerciceId);
        }

        $mvtBase = BudgetMouvement::query();
        if ($exerciceId) {
            $mvtBase->whereHas('ligne', fn ($q) => $q->where('exercice_id', $exerciceId));
        }

        $cycle = $this->cycleIpsas($exerciceId);
        $engage = $cycle['engagement']['montant'];
        $paye = $cycle['paiement']['montant'];

        return [
            'expressions_total' => (clone $expBase)->count(),
            'expressions_brouillon' => (clone $expBase)->where('statut', 'brouillon')->count(),
            'expressions_en_validation' => (clone $expBase)->whereIn('statut', ['soumis', 'en_validation_hierarchique'])->count(),
            'expressions_validees' => (clone $expBase)->where('statut', 'valide')->count(),
            'expressions_engagees' => (clone $expBase)->where('statut', 'engage')->count(),
            'expressions_rejetees' => (clone $expBase)->where('statut', 'rejete')->count(),
            'montant_engage' => $engage,
            'montant_paye' => $paye,
            'reste_a_payer' => max(0, $engage - $paye),
            'taux_paiement' => $engage > 0 ? round($paye / $engage * 100, 1) : 0,
            'engagements_en_retard' => $this->engagementsEnRetard($exerciceId),
            'service_fait_en_attente' => ServiceDoneCertificate::where('statut', 'projet')->count(),
            'receptions_en_attente' => Reception::where('statut', 'projet')->count(),
        ];
    }

    protected function engagementsEnRetard(?int $exerciceId): int
    {
        // Engagements sans paiement après date_livraison_souhaitee
        $base = ExpenseRequest::where('statut', 'engage')
            ->whereNotNull('date_livraison_souhaitee')
            ->whereDate('date_livraison_souhaitee', '<', now());

        if ($exerciceId) {
            $base->where('exercice_id', $exerciceId);
        }

        return $base->count();
    }

    protected function parTypeEngagement(?int $exerciceId): array
    {
        $base = ExpenseRequest::query();
        if ($exerciceId) {
            $base->where('exercice_id', $exerciceId);
        }

        return $base->selectRaw('type_engagement, COUNT(*) as nb, SUM(montant_estime) as montant')
            ->groupBy('type_engagement')
            ->orderByDesc('montant')
            ->get()
            ->map(fn ($r) => [
                'type' => $r->type_engagement,
                'label' => ExpenseRequest::TYPES_ENGAGEMENT[$r->type_engagement] ?? $r->type_engagement,
                'nb' => (int) $r->nb,
                'montant' => (float) $r->montant,
            ])->all();
    }

    protected function parDepartement(?int $exerciceId): array
    {
        $base = ExpenseRequest::query()->whereNotNull('departement_id')
            ->with('departement:id,code,libelle');
        if ($exerciceId) {
            $base->where('exercice_id', $exerciceId);
        }

        return $base->selectRaw('departement_id, COUNT(*) as nb, SUM(montant_estime) as montant')
            ->groupBy('departement_id')
            ->orderByDesc('montant')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'code' => $r->departement?->code ?? '—',
                'libelle' => $r->departement?->libelle ?? '—',
                'nb' => (int) $r->nb,
                'montant' => (float) $r->montant,
            ])->all();
    }

    protected function topFournisseurs(?int $exerciceId): array
    {
        $base = BudgetMouvement::query()->where('type', 'engagement')
            ->whereNotNull('supplier_id')
            ->with('supplier:id,code,libelle,type');
        if ($exerciceId) {
            $base->whereHas('ligne', fn ($q) => $q->where('exercice_id', $exerciceId));
        }

        return $base->selectRaw('supplier_id, COUNT(*) as nb, SUM(montant) as montant')
            ->groupBy('supplier_id')
            ->orderByDesc('montant')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'code' => $r->supplier?->code ?? '—',
                'libelle' => $r->supplier?->libelle ?? '—',
                'type' => $r->supplier?->type ?? '—',
                'nb_engagements' => (int) $r->nb,
                'montant' => (float) $r->montant,
            ])->all();
    }

    protected function enAttenteValidation(): array
    {
        return ExpenseRequest::whereIn('statut', ['soumis', 'en_validation_hierarchique'])
            ->with(['demandeur:id,name', 'departement:id,code'])
            ->orderBy('created_at')
            ->limit(8)
            ->get(['id', 'numero', 'objet', 'montant_estime', 'devise', 'statut', 'demandeur_id', 'departement_id', 'created_at'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'numero' => $r->numero,
                'objet' => $r->objet,
                'montant' => (float) $r->montant_estime,
                'devise' => $r->devise,
                'demandeur' => $r->demandeur?->name,
                'departement' => $r->departement?->code,
                'depuis_jours' => $r->created_at?->diffInDays(now()) ?? 0,
            ])->all();
    }

    protected function evolutionMensuelle(?BudgetExercice $exercice): array
    {
        if (! $exercice) {
            return [];
        }

        $debut = Carbon::parse($exercice->date_debut ?? "{$exercice->annee}-01-01")->startOfMonth();
        $fin = Carbon::parse($exercice->date_fin ?? "{$exercice->annee}-12-31")->endOfMonth();

        $mois = [];
        $curseur = $debut->copy();
        while ($curseur <= $fin && count($mois) < 12) {
            $finMois = $curseur->copy()->endOfMonth();
            $engagements = BudgetMouvement::where('type', 'engagement')
                ->whereHas('ligne', fn ($q) => $q->where('exercice_id', $exercice->id))
                ->whereDate('date_mouvement', '<=', $finMois)
                ->sum('montant');
            $paiements = BudgetMouvement::where('type', 'paiement')
                ->whereHas('ligne', fn ($q) => $q->where('exercice_id', $exercice->id))
                ->whereDate('date_mouvement', '<=', $finMois)
                ->sum('montant');
            $mois[] = [
                'mois' => $curseur->isoFormat('MMM YY'),
                'engagements' => (float) $engagements,
                'paiements' => (float) $paiements,
            ];
            $curseur->addMonth();
        }

        return $mois;
    }

    protected function serviceFaitPending(): array
    {
        return ServiceDoneCertificate::where('statut', 'projet')
            ->with(['mouvement:id,reference', 'constatePar:id,name'])
            ->latest()
            ->limit(5)
            ->get(['id', 'reference', 'budget_mouvement_id', 'date_constatation', 'montant_constate', 'constate_par_id'])
            ->map(fn ($c) => [
                'reference' => $c->reference,
                'engagement' => $c->mouvement?->reference,
                'date' => $c->date_constatation?->format('d/m/Y'),
                'montant' => (float) $c->montant_constate,
                'constate_par' => $c->constatePar?->name,
            ])->all();
    }

    protected function receptionsPending(): array
    {
        return Reception::where('statut', 'projet')
            ->with(['mouvement:id,reference', 'saisiPar:id,name'])
            ->latest()
            ->limit(5)
            ->get(['id', 'reference', 'budget_mouvement_id', 'date_reception', 'montant_recu', 'type_reception', 'nature', 'conformite', 'saisi_par_id'])
            ->map(fn ($r) => [
                'reference' => $r->reference,
                'engagement' => $r->mouvement?->reference,
                'date' => $r->date_reception?->format('d/m/Y'),
                'montant' => (float) $r->montant_recu,
                'type' => $r->type_reception,
                'nature' => $r->nature,
                'conformite' => $r->conformite,
            ])->all();
    }
}
