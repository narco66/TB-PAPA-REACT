<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\Axe;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetLigne;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $request->user()->can('view_budget_dashboard') || abort(403);

        $exerciceId = $request->integer('exercice_id') ?: BudgetExercice::orderByDesc('annee')->value('id');
        $exercice = $exerciceId ? BudgetExercice::find($exerciceId) : null;

        $synthese = null;
        $repartitionSource = [];
        $repartitionType = [];
        $repartitionPilier = [];
        $avancementAxes = [];

        if ($exercice) {
            $lignes = BudgetLigne::where('exercice_id', $exercice->id);

            $synthese = [
                'total' => (float) $exercice->total_depenses,
                'total_ceeac_em' => (float) $exercice->total_depenses_ceeac_em,
                'total_ptf' => (float) $exercice->total_depenses_ptf,
                'total_recettes' => (float) $exercice->total_recettes,
                'total_recettes_internes' => (float) $exercice->total_recettes_internes,
                'total_recettes_externes' => (float) $exercice->total_recettes_externes,
                'fonctionnement' => (float) $exercice->total_fonctionnement,
                'investissement' => (float) $exercice->total_investissement,
                'equipement' => (float) $exercice->total_equipement,
                'engage' => (float) (clone $lignes)->sum('montant_engage'),
                'paye' => (float) (clone $lignes)->sum('montant_paye'),
            ];
            $synthese['disponible'] = max(0, $synthese['total'] - $synthese['engage']);
            $synthese['taux_engagement'] = $synthese['total'] > 0 ? round(($synthese['engage'] / $synthese['total']) * 100, 1) : 0;
            $synthese['taux_consommation'] = $synthese['total'] > 0 ? round(($synthese['paye'] / $synthese['total']) * 100, 1) : 0;

            // Répartition par source de financement
            $repartitionSource = BudgetLigne::where('exercice_id', $exercice->id)
                ->whereNotNull('source_financement_id')
                ->with('source:id,code,libelle,type')
                ->selectRaw('source_financement_id, SUM(montant_total) as total')
                ->groupBy('source_financement_id')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($r) => [
                    'source' => $r->source?->libelle ?? '—',
                    'code' => $r->source?->code,
                    'type' => $r->source?->type,
                    'total' => (float) $r->total,
                ])->toArray();

            // Par type de budget
            $repartitionType = BudgetLigne::where('exercice_id', $exercice->id)
                ->where('nature', 'depense')
                ->selectRaw('type_budget, SUM(montant_total) as total, SUM(montant_ceeac_em) as ceeac, SUM(montant_ptf) as ptf')
                ->groupBy('type_budget')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($r) => [
                    'type' => $r->type_budget,
                    'total' => (float) $r->total,
                    'ceeac_em' => (float) $r->ceeac,
                    'ptf' => (float) $r->ptf,
                ])->toArray();

            // Par pilier (Plan Annuel de Performance)
            $repartitionPilier = BudgetLigne::where('exercice_id', $exercice->id)
                ->whereNotNull('pilier')
                ->selectRaw('pilier, SUM(montant_total) as total, SUM(montant_ceeac_em) as ceeac, SUM(montant_ptf) as ptf')
                ->groupBy('pilier')
                ->orderBy('pilier')
                ->get()
                ->map(fn ($r) => [
                    'pilier' => 'PILIER ' . $r->pilier,
                    'total' => (float) $r->total,
                    'ceeac_em' => (float) $r->ceeac,
                    'ptf' => (float) $r->ptf,
                ])->toArray();

            // Avancement par Axe RBM
            $avancementAxes = BudgetLigne::where('exercice_id', $exercice->id)
                ->whereNotNull('axe_id')
                ->with('axe:id,code,libelle')
                ->selectRaw('axe_id, SUM(montant_total) as total, SUM(montant_engage) as engage, SUM(montant_paye) as paye')
                ->groupBy('axe_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(fn ($r) => [
                    'axe' => ($r->axe?->code ?? '—') . ' — ' . ($r->axe?->libelle ?? ''),
                    'total' => (float) $r->total,
                    'engage' => (float) $r->engage,
                    'paye' => (float) $r->paye,
                    'taux_engagement' => $r->total > 0 ? round(($r->engage / $r->total) * 100, 1) : 0,
                ])->toArray();
        }

        return Inertia::render('budget/dashboard', [
            'exercices' => BudgetExercice::orderByDesc('annee')->get(['id', 'annee', 'libelle', 'statut']),
            'exercice' => $exercice,
            'synthese' => $synthese,
            'repartitionSource' => $repartitionSource,
            'repartitionType' => $repartitionType,
            'repartitionPilier' => $repartitionPilier,
            'avancementAxes' => $avancementAxes,
        ]);
    }
}
