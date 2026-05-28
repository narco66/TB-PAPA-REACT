<?php

namespace App\Http\Controllers\Rbm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rbm\StoreProduitRequest;
use App\Http\Requests\Rbm\UpdateProduitRequest;
use App\Models\Axe;
use App\Models\Direction;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProduitController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Produit::class);

        $query = Produit::query()
            ->with(['axe:id,code,libelle,papa_id', 'axe.papa:id,annee', 'direction:id,code,libelle', 'responsable:id,name'])
            ->withCount('sousProduits as sous_produits_count');

        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(fn ($w) => $w->where('code', 'like', "%{$q}%")->orWhere('libelle', 'like', "%{$q}%"));
        }
        if ($axeId = $request->integer('axe_id')) {
            $query->where('axe_id', $axeId);
        }
        if ($statut = $request->string('statut')->toString()) {
            $query->where('statut', $statut);
        }

        $produits = $query->orderBy('axe_id')->orderBy('ordre')->paginate(20)->withQueryString();

        return Inertia::render('rbm/produits/index', [
            'produits' => $produits,
            'axes' => Axe::orderBy('papa_id')->orderBy('ordre')->get(['id', 'code', 'libelle']),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'axe_id' => $request->integer('axe_id'),
                'statut' => $request->string('statut')->toString(),
            ],
            'statuts' => Axe::STATUTS,
            'can' => ['create' => $request->user()->can('create_produits')],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Produit::class);

        return Inertia::render('rbm/produits/form', [
            'mode' => 'create',
            'axe_id_defaut' => $request->integer('axe_id'),
            'axes' => Axe::with('papa:id,annee')->orderBy('papa_id')->orderBy('ordre')->get(['id', 'code', 'libelle', 'papa_id']),
            'directions' => Direction::orderBy('libelle')->get(['id', 'code', 'libelle']),
            'utilisateurs' => User::where('actif', true)->orderBy('name')->limit(200)->get(['id', 'name']),
        ]);
    }

    public function store(StoreProduitRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $produit = Produit::create([...$validated, 'statut' => $validated['statut'] ?? 'brouillon']);

        return redirect()->route('rbm.produits.show', $produit)->with('success', "Produit {$produit->code} créé.");
    }

    public function show(Produit $produit): Response
    {
        $this->authorize('view', $produit);
        $produit->load([
            'axe:id,code,libelle,papa_id',
            'axe.papa:id,annee,libelle',
            'direction:id,code,libelle',
            'responsable:id,name,fonction',
            'sousProduits' => fn ($q) => $q->orderBy('ordre'),
        ]);

        return Inertia::render('rbm/produits/show', [
            'produit' => $produit,
            'can' => [
                'update' => auth()->user()->can('update', $produit),
                'delete' => auth()->user()->can('delete', $produit),
                'create_sous_produit' => auth()->user()->can('create_sous_produits'),
            ],
        ]);
    }

    public function edit(Produit $produit): Response
    {
        $this->authorize('update', $produit);

        return Inertia::render('rbm/produits/form', [
            'mode' => 'edit',
            'produit' => $produit,
            'axes' => Axe::with('papa:id,annee')->orderBy('papa_id')->orderBy('ordre')->get(['id', 'code', 'libelle', 'papa_id']),
            'directions' => Direction::orderBy('libelle')->get(['id', 'code', 'libelle']),
            'utilisateurs' => User::where('actif', true)->orderBy('name')->limit(200)->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProduitRequest $request, Produit $produit): RedirectResponse
    {
        $produit->update($request->validated());

        return redirect()->route('rbm.produits.show', $produit)->with('success', 'Produit mis à jour.');
    }

    public function destroy(Produit $produit): RedirectResponse
    {
        $this->authorize('delete', $produit);
        $produit->delete();

        return redirect()->route('rbm.produits.index')->with('success', 'Produit supprimé.');
    }
}
