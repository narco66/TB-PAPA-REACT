<?php

namespace App\Http\Controllers;

use App\Models\Alerte;
use App\Services\AlerteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AlerteController extends Controller
{
    public function index(Request $request): Response
    {
        $request->user()->can('alerte.viewAny') || abort(403);

        $query = Alerte::query()
            ->with(['assigneeA:id,name', 'resoluePar:id,name', 'alertable']);

        if ($niveau = $request->string('niveau')->toString()) {
            $query->where('niveau', $niveau);
        }
        if ($categorie = $request->string('categorie')->toString()) {
            $query->where('categorie', $categorie);
        }
        if ($statut = $request->string('statut')->toString()) {
            $query->where('statut', $statut);
        } else {
            $query->whereIn('statut', ['ouverte', 'en_traitement']);
        }

        $alertes = $query->orderByDesc('niveau')->orderByDesc('id')->paginate(25)->withQueryString();

        $stats = [
            'ouvertes' => Alerte::where('statut', 'ouverte')->count(),
            'en_traitement' => Alerte::where('statut', 'en_traitement')->count(),
            'critiques' => Alerte::ouvertes()->critiques()->count(),
            'resolues_30j' => Alerte::where('statut', 'resolue')->where('resolue_at', '>', now()->subDays(30))->count(),
        ];

        return Inertia::render('alertes/index', [
            'alertes' => $alertes,
            'filters' => [
                'niveau' => $request->string('niveau')->toString(),
                'categorie' => $request->string('categorie')->toString(),
                'statut' => $request->string('statut')->toString(),
            ],
            'stats' => $stats,
            'can' => [
                'detect' => $request->user()->hasRole('admin_technique'),
                'resolve' => $request->user()->can('alerte.resolve'),
            ],
        ]);
    }

    public function resolve(Request $request, Alerte $alerte): RedirectResponse
    {
        $request->user()->can('alerte.resolve') || abort(403);

        $validated = $request->validate([
            'action_corrective' => ['required', 'string', 'min:5', 'max:2000'],
            'statut' => ['nullable', Rule::in(['resolue', 'ignoree'])],
        ]);

        $alerte->update([
            'statut' => $validated['statut'] ?? 'resolue',
            'action_corrective' => $validated['action_corrective'],
            'resolue_at' => now(),
            'resolue_par_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Alerte traitée.');
    }

    public function detecter(AlerteService $service): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('admin_technique'), 403);

        $stats = $service->detecterAlertes();
        $total = array_sum($stats);

        return back()->with('success', "{$total} alerte(s) générée(s) : {$stats['retards']} retards, {$stats['derives_budgetaires']} dérives, {$stats['sous_performances']} sous-performances.");
    }
}
