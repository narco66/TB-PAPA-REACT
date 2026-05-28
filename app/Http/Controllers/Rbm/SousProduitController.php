<?php

namespace App\Http\Controllers\Rbm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rbm\StoreSousProduitRequest;
use App\Http\Requests\Rbm\UpdateSousProduitRequest;
use App\Models\Axe;
use App\Models\Direction;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SousProduitController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SousProduit::class);

        $query = SousProduit::query()
            ->with([
                'produit:id,code,libelle,axe_id',
                'produit.axe:id,code,libelle',
                'direction:id,code,libelle',
                'responsable:id,name',
            ])
            ->withCount(['activites as activites_count', 'indicateurs as indicateurs_count']);

        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(fn ($w) => $w->where('code', 'like', "%{$q}%")->orWhere('libelle', 'like', "%{$q}%"));
        }
        if ($pid = $request->integer('produit_id')) {
            $query->where('produit_id', $pid);
        }

        $sousProduits = $query->orderBy('produit_id')->orderBy('ordre')->paginate(20)->withQueryString();

        return Inertia::render('rbm/sous-produits/index', [
            'sousProduits' => $sousProduits,
            'produits' => Produit::with('axe:id,code')->orderBy('ordre')->get(['id', 'code', 'libelle', 'axe_id']),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'produit_id' => $request->integer('produit_id'),
            ],
            'statuts' => Axe::STATUTS,
            'can' => ['create' => $request->user()->can('create_sous_produits')],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', SousProduit::class);

        return Inertia::render('rbm/sous-produits/form', [
            'mode' => 'create',
            'produit_id_defaut' => $request->integer('produit_id'),
            'produits' => Produit::with('axe:id,code')->orderBy('ordre')->get(['id', 'code', 'libelle', 'axe_id']),
            'directions' => Direction::orderBy('libelle')->get(['id', 'code', 'libelle']),
            'utilisateurs' => User::where('actif', true)->orderBy('name')->limit(200)->get(['id', 'name']),
        ]);
    }

    public function store(StoreSousProduitRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $sp = SousProduit::create([...$validated, 'statut' => $validated['statut'] ?? 'brouillon']);

        return redirect()->route('rbm.sous-produits.show', $sp)->with('success', "Sous-Produit {$sp->code} créé.");
    }

    public function show(SousProduit $sousProduit): Response
    {
        $this->authorize('view', $sousProduit);
        $sousProduit->load([
            'produit:id,code,libelle,axe_id',
            'produit.axe:id,code,libelle,papa_id',
            'produit.axe.papa:id,annee',
            'direction:id,code,libelle',
            'responsable:id,name,fonction',
            'activites' => fn ($q) => $q->orderBy('ordre'),
            'indicateurs',
        ]);

        return Inertia::render('rbm/sous-produits/show', [
            'sousProduit' => $sousProduit,
            'can' => [
                'update' => auth()->user()->can('update', $sousProduit),
                'delete' => auth()->user()->can('delete', $sousProduit),
                'create_activite' => auth()->user()->can('create_activites'),
                'create_indicateur' => auth()->user()->can('indicateur.create'),
            ],
        ]);
    }

    public function edit(SousProduit $sousProduit): Response
    {
        $this->authorize('update', $sousProduit);

        return Inertia::render('rbm/sous-produits/form', [
            'mode' => 'edit',
            'sousProduit' => $sousProduit,
            'produits' => Produit::with('axe:id,code')->orderBy('ordre')->get(['id', 'code', 'libelle', 'axe_id']),
            'directions' => Direction::orderBy('libelle')->get(['id', 'code', 'libelle']),
            'utilisateurs' => User::where('actif', true)->orderBy('name')->limit(200)->get(['id', 'name']),
        ]);
    }

    public function update(UpdateSousProduitRequest $request, SousProduit $sousProduit): RedirectResponse
    {
        $sousProduit->update($request->validated());

        return redirect()->route('rbm.sous-produits.show', $sousProduit)->with('success', 'Sous-Produit mis à jour.');
    }

    public function destroy(SousProduit $sousProduit): RedirectResponse
    {
        $this->authorize('delete', $sousProduit);
        $sousProduit->delete();

        return redirect()->route('rbm.sous-produits.index')->with('success', 'Sous-Produit supprimé.');
    }
}
