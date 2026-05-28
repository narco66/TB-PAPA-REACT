<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DepartementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Departement::class);

        $query = Departement::query()
            ->with(['commissaire:id,name,email,fonction'])
            ->withCount(['directions', 'axes']);

        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(fn ($w) => $w
                ->where('code', 'like', "%{$q}%")
                ->orWhere('libelle', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%"),
            );
        }
        if ($request->has('actif') && $request->string('actif')->toString() !== '') {
            $query->where('actif', $request->boolean('actif'));
        }

        $departements = $query->orderBy('ordre')->orderBy('code')->paginate(20)->withQueryString();

        // Taux d'exécution moyen par département (sur ses axes)
        $departements->getCollection()->transform(function ($d) {
            $tauxMoyen = $d->axes()->avg('taux_execution') ?? 0;
            $d->taux_execution_moyen = round((float) $tauxMoyen, 1);

            return $d;
        });

        return Inertia::render('admin/departements/index', [
            'departements' => $departements,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'actif' => $request->string('actif')->toString(),
            ],
            'can' => ['create' => $request->user()->can('create', Departement::class)],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Departement::class);

        return Inertia::render('admin/departements/form', [
            'mode' => 'create',
            'commissaires' => $this->listerCommissaires(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Departement::class);

        $validated = $this->valider($request);

        $departement = Departement::create($validated);

        // Mettre à jour le département du commissaire si un est rattaché
        if (! empty($validated['commissaire_id'])) {
            // Note : Direction relie un User à une Direction, le rôle "Chef de Département"
            // est porté par la FK commissaire_id sur Departement.
        }

        return redirect()->route('admin.departements.show', $departement)
            ->with('success', "Département « {$departement->code} » créé.");
    }

    public function show(Departement $departement): Response
    {
        $this->authorize('view', $departement);

        $departement->load([
            'commissaire:id,name,email,fonction,matricule',
            'directions' => fn ($q) => $q->orderBy('libelle'),
            'directions.directeur:id,name',
            'axes' => fn ($q) => $q->orderBy('ordre'),
            'axes.papa:id,annee,libelle',
        ]);

        $stats = [
            'nb_directions' => $departement->directions->count(),
            'nb_directions_techniques' => $departement->directions->where('type', 'technique')->count(),
            'nb_directions_appui' => $departement->directions->where('type', 'appui_soutien')->count(),
            'nb_axes' => $departement->axes->count(),
            'taux_execution_moyen' => round((float) ($departement->axes->avg('taux_execution') ?? 0), 1),
        ];

        return Inertia::render('admin/departements/show', [
            'departement' => $departement,
            'stats' => $stats,
            'can' => [
                'update' => auth()->user()->can('update', $departement),
                'delete' => auth()->user()->can('delete', $departement),
            ],
        ]);
    }

    public function edit(Departement $departement): Response
    {
        $this->authorize('update', $departement);

        return Inertia::render('admin/departements/form', [
            'mode' => 'edit',
            'departement' => $departement,
            'commissaires' => $this->listerCommissaires(),
        ]);
    }

    public function update(Request $request, Departement $departement): RedirectResponse
    {
        $this->authorize('update', $departement);

        $validated = $this->valider($request, $departement);
        $departement->update($validated);

        return redirect()->route('admin.departements.show', $departement)
            ->with('success', 'Département mis à jour.');
    }

    public function destroy(Departement $departement): RedirectResponse
    {
        $this->authorize('delete', $departement);

        if ($departement->directions()->exists() || $departement->axes()->exists()) {
            return back()->with('error', 'Impossible de supprimer : des directions ou des axes y sont rattachés.');
        }

        $departement->delete();

        return redirect()->route('admin.departements.index')->with('success', 'Département supprimé.');
    }

    protected function valider(Request $request, ?Departement $departement = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:16',
                Rule::unique('departements', 'code')->ignore($departement?->id)->whereNull('deleted_at')],
            'libelle' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'commissaire_id' => ['nullable', 'integer', 'exists:users,id'],
            'ordre' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'actif' => ['nullable', 'boolean'],
        ]);
    }

    protected function listerCommissaires(): Collection
    {
        return User::role('commissaire')
            ->where('actif', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'fonction']);
    }
}
