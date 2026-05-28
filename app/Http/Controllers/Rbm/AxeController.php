<?php

namespace App\Http\Controllers\Rbm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rbm\StoreAxeRequest;
use App\Http\Requests\Rbm\UpdateAxeRequest;
use App\Models\Axe;
use App\Models\Departement;
use App\Models\Papa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AxeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Axe::class);

        $query = Axe::query()
            ->with(['papa:id,annee,libelle', 'departement:id,code,libelle', 'responsable:id,name'])
            ->withCount('produits as produits_count');

        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(fn ($w) => $w
                ->where('code', 'like', "%{$q}%")
                ->orWhere('libelle', 'like', "%{$q}%"),
            );
        }
        if ($papaId = $request->integer('papa_id')) {
            $query->where('papa_id', $papaId);
        }
        if ($statut = $request->string('statut')->toString()) {
            $query->where('statut', $statut);
        }

        $axes = $query->orderBy('papa_id')->orderBy('ordre')->paginate(20)->withQueryString();

        return Inertia::render('rbm/axes/index', [
            'axes' => $axes,
            'papas' => Papa::orderByDesc('annee')->get(['id', 'annee', 'libelle']),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'papa_id' => $request->integer('papa_id'),
                'statut' => $request->string('statut')->toString(),
            ],
            'statuts' => Axe::STATUTS,
            'can' => ['create' => $request->user()->can('create_axes')],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Axe::class);

        return Inertia::render('rbm/axes/form', [
            'mode' => 'create',
            'papa_id_defaut' => $request->integer('papa_id') ?: Papa::actif()->orderByDesc('annee')->value('id'),
            'papas' => Papa::orderByDesc('annee')->get(['id', 'annee', 'libelle', 'statut']),
            'departements' => Departement::with('commissaire:id,name')->orderBy('ordre')->get(['id', 'code', 'libelle', 'commissaire_id']),
            'utilisateurs' => User::where('actif', true)->orderBy('name')->limit(200)->get(['id', 'name', 'fonction']),
        ]);
    }

    public function store(StoreAxeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $axe = Axe::create([
            ...$validated,
            'statut' => $validated['statut'] ?? 'brouillon',
        ]);

        return redirect()->route('rbm.axes.show', $axe)
            ->with('success', "Axe {$axe->code} créé.");
    }

    public function show(Axe $axe): Response
    {
        $this->authorize('view', $axe);
        $axe->load([
            'papa:id,annee,libelle,statut',
            'departement:id,code,libelle',
            'responsable:id,name,fonction',
            'produits' => fn ($q) => $q->orderBy('ordre'),
        ]);
        $axe->loadCount(['produits as produits_count']);

        return Inertia::render('rbm/axes/show', [
            'axe' => $axe,
            'can' => [
                'update' => auth()->user()->can('update', $axe),
                'delete' => auth()->user()->can('delete', $axe),
                'validate' => auth()->user()->can('validate', $axe),
                'create_produit' => auth()->user()->can('create_produits'),
            ],
        ]);
    }

    public function edit(Axe $axe): Response
    {
        $this->authorize('update', $axe);

        return Inertia::render('rbm/axes/form', [
            'mode' => 'edit',
            'axe' => $axe,
            'papas' => Papa::orderByDesc('annee')->get(['id', 'annee', 'libelle', 'statut']),
            'departements' => Departement::orderBy('ordre')->get(['id', 'code', 'libelle']),
            'utilisateurs' => User::where('actif', true)->orderBy('name')->limit(200)->get(['id', 'name', 'fonction']),
        ]);
    }

    public function update(UpdateAxeRequest $request, Axe $axe): RedirectResponse
    {
        $axe->update($request->validated());

        return redirect()->route('rbm.axes.show', $axe)->with('success', "Axe {$axe->code} mis à jour.");
    }

    public function destroy(Axe $axe): RedirectResponse
    {
        $this->authorize('delete', $axe);
        $axe->delete();

        return redirect()->route('rbm.axes.index')->with('success', 'Axe supprimé.');
    }
}
