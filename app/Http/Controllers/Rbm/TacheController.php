<?php

namespace App\Http\Controllers\Rbm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rbm\StoreTacheRequest;
use App\Http\Requests\Rbm\UpdateTacheRequest;
use App\Models\Activite;
use App\Models\Tache;
use App\Models\User;
use App\Services\Rbm\RecalculAvancementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TacheController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Tache::class);

        $query = Tache::query()
            ->with([
                'activite:id,code,libelle,sous_produit_id',
                'activite.sousProduit:id,code,libelle',
                'assigneA:id,name',
                'responsable:id,name',
            ]);

        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(fn ($w) => $w->where('code', 'like', "%{$q}%")->orWhere('libelle', 'like', "%{$q}%"));
        }
        if ($actId = $request->integer('activite_id')) {
            $query->where('activite_id', $actId);
        }

        $taches = $query->orderBy('activite_id')->orderBy('ordre')->paginate(25)->withQueryString();

        return Inertia::render('rbm/taches/index', [
            'taches' => $taches,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'activite_id' => $request->integer('activite_id'),
            ],
            'can' => ['create' => $request->user()->can('create_taches')],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Tache::class);

        return Inertia::render('rbm/taches/form', [
            'mode' => 'create',
            'activite_id_defaut' => $request->integer('activite_id'),
            'activites' => Activite::with('sousProduit:id,code')->orderBy('ordre')
                ->get(['id', 'code', 'libelle', 'sous_produit_id']),
            'utilisateurs' => User::where('actif', true)->orderBy('name')->limit(200)->get(['id', 'name']),
        ]);
    }

    public function store(StoreTacheRequest $request, RecalculAvancementService $recalcul): RedirectResponse
    {
        $validated = $request->validated();
        $tache = Tache::create([...$validated, 'statut' => $validated['statut'] ?? 'planifiee']);

        return redirect()->route('rbm.taches.show', $tache)->with('success', "Tâche {$tache->code} créée.");
    }

    public function show(Tache $tache): Response
    {
        $this->authorize('view', $tache);
        $tache->load([
            'activite:id,code,libelle,sous_produit_id',
            'activite.sousProduit.produit.axe',
            'responsable:id,name,fonction',
            'assigneA:id,name,fonction',
        ]);

        return Inertia::render('rbm/taches/show', [
            'tache' => $tache,
            'can' => [
                'update' => auth()->user()->can('update', $tache),
                'delete' => auth()->user()->can('delete', $tache),
            ],
        ]);
    }

    public function edit(Tache $tache): Response
    {
        $this->authorize('update', $tache);

        return Inertia::render('rbm/taches/form', [
            'mode' => 'edit',
            'tache' => $tache,
            'activites' => Activite::with('sousProduit:id,code')->orderBy('ordre')
                ->get(['id', 'code', 'libelle', 'sous_produit_id']),
            'utilisateurs' => User::where('actif', true)->orderBy('name')->limit(200)->get(['id', 'name']),
        ]);
    }

    public function update(UpdateTacheRequest $request, Tache $tache, RecalculAvancementService $recalcul): RedirectResponse
    {
        $tache->update($request->validated());

        return redirect()->route('rbm.taches.show', $tache)->with('success', 'Tâche mise à jour.');
    }

    public function destroy(Tache $tache): RedirectResponse
    {
        $this->authorize('delete', $tache);
        $tache->delete();

        return redirect()->route('rbm.taches.index')->with('success', 'Tâche supprimée.');
    }
}
