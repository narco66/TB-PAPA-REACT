<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('supplier.viewAny');

        $suppliers = Supplier::query()
            ->when($request->input('q'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('libelle', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")
                ->orWhere('nif', 'like', "%{$s}%")))
            ->when($request->input('statut'), fn ($q, $s) => $q->where('statut', $s))
            ->orderBy('libelle')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('expense/suppliers/index', [
            'suppliers' => $suppliers,
            'types' => Supplier::TYPES,
            'statuts' => Supplier::STATUTS,
            'filters' => $request->only(['q', 'statut']),
            'can' => ['manage' => $request->user()->can('supplier.manage')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('supplier.manage');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('suppliers', 'code')],
            'libelle' => 'required|string|max:191',
            'type' => ['required', Rule::in(Supplier::TYPES)],
            'nif' => 'nullable|string|max:32',
            'rccm' => 'nullable|string|max:64',
            'contact_principal' => 'nullable|string|max:191',
            'email' => 'nullable|email|max:191',
            'telephone' => 'nullable|string|max:32',
            'adresse' => 'nullable|string|max:5000',
            'pays' => 'nullable|string|max:64',
            'compte_bancaire' => 'nullable|string|max:64',
            'banque' => 'nullable|string|max:191',
            'observations' => 'nullable|string|max:5000',
        ]);

        Supplier::create([...$data, 'statut' => 'actif', 'created_by' => $request->user()->id]);

        return back()->with('success', "Fournisseur « {$data['libelle']} » créé.");
    }
}
