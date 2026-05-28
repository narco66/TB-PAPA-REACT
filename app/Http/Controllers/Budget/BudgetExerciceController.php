<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\Budget\BudgetExercice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BudgetExerciceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', BudgetExercice::class);

        $exercices = BudgetExercice::query()
            ->withCount('lignes as lignes_count')
            ->orderByDesc('annee')
            ->paginate(15);

        return Inertia::render('budget/exercices/index', [
            'exercices' => $exercices,
            'statuts' => BudgetExercice::STATUTS,
            'can' => ['create' => $request->user()->can('create_budget')],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', BudgetExercice::class);
        $derniereAnnee = (int) (BudgetExercice::max('annee') ?? now()->year);

        return Inertia::render('budget/exercices/form', [
            'mode' => 'create',
            'annee_suggeree' => $derniereAnnee + 1,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', BudgetExercice::class);

        $validated = $request->validate([
            'annee' => ['required', 'integer', 'min:2024', 'max:2050', 'unique:budget_exercices,annee'],
            'libelle' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'devise' => ['nullable', 'string', 'max:8'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
        ]);

        $exercice = BudgetExercice::create([
            ...$validated,
            'statut' => 'brouillon',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('budget.exercices.show', $exercice)
            ->with('success', "Exercice budgétaire {$exercice->annee} créé.");
    }

    public function show(BudgetExercice $exercice): Response
    {
        $this->authorize('view', $exercice);

        $exercice->load(['valideur:id,name,fonction', 'createur:id,name,fonction']);
        $exercice->loadCount('lignes as lignes_count');

        return Inertia::render('budget/exercices/show', [
            'exercice' => $exercice,
            'can' => [
                'update' => auth()->user()->can('update', $exercice),
                'delete' => auth()->user()->can('delete', $exercice),
                'validate' => auth()->user()->can('validate', $exercice),
                'import' => auth()->user()->can('import_budget'),
                'export' => auth()->user()->can('export_budget'),
            ],
        ]);
    }

    public function edit(BudgetExercice $exercice): Response
    {
        $this->authorize('update', $exercice);

        return Inertia::render('budget/exercices/form', [
            'mode' => 'edit',
            'exercice' => $exercice,
        ]);
    }

    public function update(Request $request, BudgetExercice $exercice): RedirectResponse
    {
        $this->authorize('update', $exercice);

        $validated = $request->validate([
            'libelle' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'statut' => ['sometimes', Rule::in(BudgetExercice::STATUTS)],
        ]);

        $exercice->update([...$validated, 'updated_by' => $request->user()->id]);

        return redirect()->route('budget.exercices.show', $exercice)
            ->with('success', 'Exercice mis à jour.');
    }

    public function destroy(BudgetExercice $exercice): RedirectResponse
    {
        $this->authorize('delete', $exercice);
        $exercice->delete();

        return redirect()->route('budget.exercices.index')->with('success', 'Exercice supprimé.');
    }

    public function valider(Request $request, BudgetExercice $exercice): RedirectResponse
    {
        $this->authorize('validate', $exercice);

        $exercice->update([
            'statut' => 'valide',
            'valide_par_id' => $request->user()->id,
            'valide_at' => now(),
        ]);

        return back()->with('success', "Exercice {$exercice->annee} validé.");
    }

    public function cloturer(BudgetExercice $exercice): RedirectResponse
    {
        $this->authorize('archive', $exercice);
        $exercice->update(['statut' => 'cloture']);

        return back()->with('success', 'Exercice clôturé.');
    }
}
