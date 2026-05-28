<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departement;
use App\Models\Direction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DirectionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('direction.manage');

        $directions = Direction::query()
            ->with(['departement:id,code,libelle', 'directeur:id,name,fonction'])
            ->withCount(['utilisateurs', 'services'])
            ->when($request->input('q'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('libelle', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")))
            ->when($request->input('departement_id'), fn ($q, $d) => $q->where('departement_id', $d))
            ->when($request->input('type'), fn ($q, $t) => $q->where('type', $t))
            ->orderBy('libelle')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/directions/index', [
            'directions' => $directions,
            'departements' => Departement::orderBy('ordre')->get(['id', 'code', 'libelle']),
            'types' => ['technique', 'appui_soutien'],
            'filters' => $request->only(['q', 'departement_id', 'type']),
            'can' => ['manage' => $request->user()->can('direction.manage')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('direction.manage');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('directions', 'code')],
            'libelle' => 'required|string|max:191',
            'description' => 'nullable|string|max:5000',
            'type' => ['required', Rule::in(['technique', 'appui_soutien'])],
            'departement_id' => 'nullable|integer|exists:departements,id',
            'directeur_id' => 'nullable|integer|exists:users,id',
            'ordre' => 'nullable|integer',
        ]);

        $direction = Direction::create([...$data, 'actif' => true]);

        return back()->with('success', "Direction « {$direction->libelle} » créée.");
    }

    public function update(Direction $direction, Request $request): RedirectResponse
    {
        $this->authorize('direction.manage');

        $data = $request->validate([
            'libelle' => 'required|string|max:191',
            'description' => 'nullable|string|max:5000',
            'type' => ['required', Rule::in(['technique', 'appui_soutien'])],
            'departement_id' => 'nullable|integer|exists:departements,id',
            'directeur_id' => 'nullable|integer|exists:users,id',
            'ordre' => 'nullable|integer',
        ]);

        $direction->update($data);

        return back()->with('success', "Direction mise à jour.");
    }

    public function destroy(Direction $direction): RedirectResponse
    {
        $this->authorize('direction.manage');

        if ($direction->services()->exists()) {
            return back()->with('error', 'Impossible de supprimer : services encore rattachés.');
        }

        $direction->delete();

        return back()->with('success', 'Direction supprimée.');
    }
}
