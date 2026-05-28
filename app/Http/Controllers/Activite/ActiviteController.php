<?php

namespace App\Http\Controllers\Activite;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activite\StoreActiviteRequest;
use App\Http\Requests\Activite\UpdateAvancementRequest;
use App\Models\Activite;
use App\Models\Direction;
use App\Models\Papa;
use App\Models\SousProduit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActiviteController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Activite::class);

        $query = Activite::query()
            ->with([
                'sousProduit:id,code,libelle,produit_id',
                'sousProduit.produit:id,code,libelle,axe_id',
                'sousProduit.produit.axe:id,code,libelle,papa_id',
                'direction:id,code,libelle',
                'pointFocal:id,name',
                'responsable:id,name',
            ])
            ->withCount('taches as taches_count');

        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(fn ($w) => $w->where('code', 'like', "%{$q}%")->orWhere('libelle', 'like', "%{$q}%"));
        }
        if ($statut = $request->string('statut')->toString()) {
            $query->where('statut', $statut);
        }
        if ($spId = $request->integer('sous_produit_id')) {
            $query->where('sous_produit_id', $spId);
        }
        if ($request->boolean('retard')) {
            $query->whereDate('date_fin', '<', now())
                ->whereNotIn('statut', ['realisee', 'annulee', 'archive'])
                ->where('taux_execution', '<', 100);
        }

        $activites = $query->orderBy('sous_produit_id')->orderBy('ordre')->paginate(25)->withQueryString();

        return Inertia::render('activites/index', [
            'activites' => $activites,
            'papas' => Papa::orderByDesc('annee')->get(['id', 'annee', 'libelle']),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'statut' => $request->string('statut')->toString(),
                'sous_produit_id' => $request->integer('sous_produit_id'),
                'retard' => $request->boolean('retard'),
            ],
            'can' => ['create' => $request->user()->can('create_activites')],
        ]);
    }

    public function gantt(Request $request): Response
    {
        $this->authorize('viewAny', Activite::class);

        $papaId = $request->integer('papa_id') ?: Papa::actif()->orderByDesc('annee')->value('id');

        $activites = Activite::query()
            ->with([
                'sousProduit.produit.axe:id,code,libelle,papa_id',
                'sousProduit.produit.axe.papa:id,annee',
                'pointFocal:id,name',
            ])
            ->when($papaId, fn ($q) => $q->whereHas('sousProduit.produit.axe', fn ($w) => $w->where('papa_id', $papaId)))
            ->orderBy('date_debut')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'code' => $a->code,
                'libelle' => $a->libelle,
                'date_debut' => $a->date_debut?->toDateString(),
                'date_fin' => $a->date_fin?->toDateString(),
                'avancement' => (float) $a->taux_execution,
                'statut' => $a->statut,
                'niveau_risque' => $a->niveau_risque,
                'est_jalon' => (bool) $a->est_jalon,
                'action_code' => $a->sousProduit?->produit?->axe?->code,
                'action_libelle' => $a->sousProduit?->produit?->axe?->libelle,
                'departement' => $a->sousProduit?->code,
                'point_focal' => $a->pointFocal?->name,
                'en_retard' => $a->estEnRetard(),
            ]);

        return Inertia::render('activites/gantt', [
            'activites' => $activites,
            'papas' => Papa::orderByDesc('annee')->get(['id', 'annee', 'libelle']),
            'papa_id' => $papaId,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Activite::class);

        return Inertia::render('activites/create', [
            'sous_produit_id_defaut' => $request->integer('sous_produit_id'),
            'sousProduits' => SousProduit::with('produit.axe:id,code')->orderBy('ordre')
                ->get(['id', 'code', 'libelle', 'produit_id']),
            'directions' => Direction::orderBy('libelle')->get(['id', 'code', 'libelle']),
            'utilisateurs' => User::where('actif', true)->orderBy('name')->limit(200)->get(['id', 'name', 'fonction']),
        ]);
    }

    public function store(StoreActiviteRequest $request): RedirectResponse
    {
        $activite = Activite::create([...$request->validated(), 'statut' => 'planifiee']);

        return redirect()->route('activites.show', $activite)->with('success', "Activité {$activite->code} créée.");
    }

    public function show(Activite $activite): Response
    {
        $this->authorize('view', $activite);

        $activite->load([
            'sousProduit.produit.axe.papa:id,annee,libelle',
            'direction:id,code,libelle',
            'responsable:id,name,fonction',
            'pointFocal:id,name,fonction',
            'taches' => fn ($q) => $q->orderBy('ordre'),
            'taches.assigneA:id,name',
        ]);

        return Inertia::render('activites/show', [
            'activite' => $activite,
            'can' => [
                'update' => auth()->user()->can('update', $activite),
                'updateAvancement' => auth()->user()->can('edit_activites'),
                'delete' => auth()->user()->can('delete', $activite),
                'create_tache' => auth()->user()->can('create_taches'),
            ],
        ]);
    }

    public function updateAvancement(UpdateAvancementRequest $request, Activite $activite): RedirectResponse
    {
        $validated = $request->validated();

        $activite->update([
            'taux_execution' => $validated['taux_execution'],
            'statut' => $validated['statut'],
            'date_debut_reelle' => $activite->date_debut_reelle ?? ($validated['statut'] === 'en_cours' ? now() : null),
            'date_fin_reelle' => in_array($validated['statut'], ['realisee', 'annulee'], true) ? now() : null,
        ]);

        return back()->with('success', 'Avancement mis à jour.');
    }
}
