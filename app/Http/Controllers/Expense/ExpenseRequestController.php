<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequestRequest;
use App\Models\Activite;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetSourceFinancement;
use App\Models\Departement;
use App\Models\Direction;
use App\Models\ExpenseRequest;
use App\Models\Supplier;
use App\Models\Tache;
use App\Services\Expense\ExpenseRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseRequestController extends Controller
{
    public function __construct(protected ExpenseRequestService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('expense.viewAny');

        $items = ExpenseRequest::query()
            ->with(['demandeur:id,name', 'departement:id,code,libelle', 'exercice:id,annee', 'supplierPressenti:id,code,libelle'])
            ->when($request->input('statut'), fn ($q, $s) => $q->where('statut', $s))
            ->when($request->input('type'), fn ($q, $t) => $q->where('type_engagement', $t))
            ->when($request->input('q'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('numero', 'like', "%{$s}%")
                ->orWhere('objet', 'like', "%{$s}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('expense/requests/index', [
            'requests' => $items,
            'statuts' => ExpenseRequest::STATUTS,
            'types' => ExpenseRequest::TYPES_ENGAGEMENT,
            'filters' => $request->only(['statut', 'type', 'q']),
            'can' => ['create' => $request->user()->can('expense.create')],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('expense.create');

        return Inertia::render('expense/requests/form', [
            'mode' => 'create',
            'exercices' => BudgetExercice::orderByDesc('annee')->get(['id', 'annee', 'libelle', 'statut']),
            'departements' => Departement::orderBy('libelle')->get(['id', 'code', 'libelle']),
            'directions' => Direction::orderBy('libelle')->get(['id', 'code', 'libelle']),
            'activites' => Activite::orderBy('code')->limit(500)->get(['id', 'code', 'libelle']),
            'taches' => Tache::orderBy('code')->limit(500)->get(['id', 'code', 'libelle']),
            'sources' => BudgetSourceFinancement::where('actif', true)->orderBy('libelle')->get(['id', 'code', 'libelle']),
            'suppliers' => Supplier::where('statut', 'actif')->orderBy('libelle')->get(['id', 'code', 'libelle']),
            'types_engagement' => ExpenseRequest::TYPES_ENGAGEMENT,
        ]);
    }

    public function store(StoreExpenseRequestRequest $request): RedirectResponse
    {
        $expenseRequest = $this->service->creer($request->validated(), $request->user());

        return redirect()->route('expense.requests.show', $expenseRequest)
            ->with('success', "Expression du besoin {$expenseRequest->numero} créée en brouillon.");
    }

    public function show(ExpenseRequest $request, Request $http): Response
    {
        $this->authorize('expense.view');

        $request->load([
            'exercice:id,annee,libelle',
            'demandeur:id,name,fonction',
            'departement:id,code,libelle',
            'direction:id,code,libelle',
            'activite:id,code,libelle',
            'tache:id,code,libelle',
            'sourceFinancement:id,code,libelle',
            'supplierPressenti:id,code,libelle,type,nif',
            'valideurHierarchique:id,name',
            'engagement:id,reference,montant,date_mouvement,statut_mouvement',
        ]);

        return Inertia::render('expense/requests/show', [
            'request' => $request,
            'types_engagement' => ExpenseRequest::TYPES_ENGAGEMENT,
            'can' => [
                'submit' => $http->user()->can('expense.submit') && in_array($request->statut, ['brouillon', 'retourne_correction'], true),
                'validate' => $http->user()->can('expense.validate_hierarchique') && in_array($request->statut, ['soumis', 'en_validation_hierarchique'], true),
                'reject' => $http->user()->can('expense.reject') && ! in_array($request->statut, ['engage', 'annule'], true),
                'engage' => $http->user()->can('expense.engage') && $request->statut === 'valide',
                'cancel' => $http->user()->can('expense.cancel') && $request->statut !== 'engage' && $request->statut !== 'annule',
            ],
        ]);
    }

    public function submit(ExpenseRequest $request, Request $http): RedirectResponse
    {
        $this->authorize('expense.submit');
        $this->service->soumettre($request, $http->user());

        return back()->with('success', "Expression {$request->numero} soumise pour validation hiérarchique.");
    }

    public function validateHierarchique(ExpenseRequest $request, Request $http): RedirectResponse
    {
        $this->authorize('expense.validate_hierarchique');
        $commentaire = $http->validate(['commentaire' => 'nullable|string|max:5000'])['commentaire'] ?? null;
        $this->service->valider($request, $http->user(), $commentaire);

        return back()->with('success', "Expression {$request->numero} validée.");
    }

    public function reject(ExpenseRequest $request, Request $http): RedirectResponse
    {
        $this->authorize('expense.reject');
        $motif = $http->validate(['motif' => 'required|string|max:5000'])['motif'];
        $this->service->rejeter($request, $http->user(), $motif);

        return back()->with('success', "Expression {$request->numero} rejetée.");
    }

    public function return(ExpenseRequest $request, Request $http): RedirectResponse
    {
        $this->authorize('expense.validate_hierarchique');
        $motif = $http->validate(['motif' => 'required|string|max:5000'])['motif'];
        $this->service->retourner($request, $http->user(), $motif);

        return back()->with('success', "Expression {$request->numero} retournée pour correction.");
    }

    public function engage(ExpenseRequest $request, Request $http): RedirectResponse
    {
        $this->authorize('expense.engage');

        $data = $http->validate([
            'imputations' => 'required|array|min:1',
            'imputations.*.budget_ligne_id' => 'required|integer|exists:budget_lignes,id',
            'imputations.*.libelle' => 'nullable|string|max:255',
            'imputations.*.montant' => 'required|numeric|min:0.01',
            'imputations.*.montant_ceeac' => 'nullable|numeric|min:0',
            'imputations.*.montant_ptf' => 'nullable|numeric|min:0',
            'imputations.*.source_financement_id' => 'nullable|integer|exists:budget_sources_financement,id',
            'imputations.*.imputation_analytique' => 'nullable|string|max:64',
            'numero_piece' => 'nullable|string|max:64',
            'motif' => 'nullable|string|max:5000',
        ]);

        try {
            $mouvement = $this->service->engager(
                $request,
                $http->user(),
                $data['imputations'],
                $data['numero_piece'] ?? null,
                $data['motif'] ?? null,
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Échec engagement : ' . $e->getMessage());
        }

        return back()->with('success', "Engagement créé (réf {$mouvement->reference}).");
    }

    public function cancel(ExpenseRequest $request, Request $http): RedirectResponse
    {
        $this->authorize('expense.cancel');
        $motif = $http->validate(['motif' => 'required|string|max:5000'])['motif'];
        $this->service->annuler($request, $http->user(), $motif);

        return back()->with('success', "Expression {$request->numero} annulée.");
    }
}
