<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departement;
use App\Models\Direction;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('service.viewAny');

        $services = Service::query()
            ->with(['direction:id,code,libelle', 'departement:id,code,libelle', 'chefService:id,name'])
            ->withCount('users')
            ->when($request->input('q'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('libelle', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")))
            ->when($request->input('departement_id'), fn ($q, $d) => $q->where('departement_id', $d))
            ->when($request->input('direction_id'), fn ($q, $d) => $q->where('direction_id', $d))
            ->when($request->input('statut'), fn ($q, $s) => $q->where('statut', $s))
            ->orderBy('libelle')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/services/index', [
            'services' => $services,
            'departements' => Departement::orderBy('ordre')->get(['id', 'code', 'libelle']),
            'directions' => Direction::orderBy('libelle')->get(['id', 'code', 'libelle', 'departement_id']),
            'statuts' => Service::STATUTS,
            'filters' => $request->only(['q', 'departement_id', 'direction_id', 'statut']),
            'can' => ['manage' => $request->user()->can('service.manage')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('service.manage');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('services', 'code')],
            'libelle' => 'required|string|max:191',
            'description' => 'nullable|string|max:5000',
            'direction_id' => 'nullable|integer|exists:directions,id',
            'chef_service_id' => 'nullable|integer|exists:users,id',
            'ordre' => 'nullable|integer',
            'statut' => ['nullable', Rule::in(Service::STATUTS)],
        ]);

        $service = Service::create([...$data, 'actif' => true]);

        return back()->with('success', "Service « {$service->libelle} » créé.");
    }

    public function update(Service $service, Request $request): RedirectResponse
    {
        $this->authorize('service.manage');

        $data = $request->validate([
            'libelle' => 'required|string|max:191',
            'description' => 'nullable|string|max:5000',
            'direction_id' => 'nullable|integer|exists:directions,id',
            'chef_service_id' => 'nullable|integer|exists:users,id',
            'ordre' => 'nullable|integer',
            'statut' => ['nullable', Rule::in(Service::STATUTS)],
        ]);

        $service->update($data);

        return back()->with('success', "Service « {$service->libelle} » mis à jour.");
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->authorize('service.manage');

        if ($service->users()->exists()) {
            return back()->with('error', 'Impossible de supprimer : utilisateurs encore rattachés à ce service.');
        }

        $service->delete();

        return back()->with('success', 'Service supprimé.');
    }
}
