<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\Activite;
use App\Models\Axe;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetLigne;
use App\Models\Budget\BudgetSourceFinancement;
use App\Models\Departement;
use App\Models\Direction;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\Tache;
use App\Services\Budget\BudgetNomenclatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BudgetLigneController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', BudgetLigne::class);

        $query = BudgetLigne::query()->with([
            'exercice:id,annee,libelle',
            'source:id,code,libelle',
            'axe:id,code,libelle',
            'produit:id,code,libelle',
            'departement:id,code',
        ]);

        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(fn ($w) => $w
                ->where('libelle', 'like', "%{$q}%")
                ->orWhere('code_action', 'like', "%{$q}%")
                ->orWhere('paragraphe_code', 'like', "%{$q}%"),
            );
        }
        if ($exerciceId = $request->integer('exercice_id')) {
            $query->where('exercice_id', $exerciceId);
        }
        if ($nature = $request->string('nature')->toString()) {
            $query->where('nature', $nature);
        }
        if ($type = $request->string('type_budget')->toString()) {
            $query->where('type_budget', $type);
        }
        if ($pilier = $request->integer('pilier')) {
            $query->where('pilier', $pilier);
        }
        if ($axeId = $request->integer('axe_id')) {
            $query->where('axe_id', $axeId);
        }
        if ($sourceId = $request->integer('source_financement_id')) {
            $query->where('source_financement_id', $sourceId);
        }

        $lignes = $query->orderBy('titre_code')->orderBy('chapitre_code')
            ->orderBy('article_code')->orderBy('code_action')
            ->paginate(50)->withQueryString();

        return Inertia::render('budget/lignes/index', [
            'lignes' => $lignes,
            'exercices' => BudgetExercice::orderByDesc('annee')->get(['id', 'annee', 'libelle']),
            'axes' => Axe::orderBy('papa_id')->orderBy('ordre')->get(['id', 'code', 'libelle']),
            'sources' => BudgetSourceFinancement::where('actif', true)->orderBy('libelle')->get(['id', 'code', 'libelle', 'type']),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'exercice_id' => $request->integer('exercice_id'),
                'nature' => $request->string('nature')->toString(),
                'type_budget' => $request->string('type_budget')->toString(),
                'pilier' => $request->integer('pilier'),
                'axe_id' => $request->integer('axe_id'),
                'source_financement_id' => $request->integer('source_financement_id'),
            ],
            'can' => ['create' => $request->user()->can('create_budget')],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', BudgetLigne::class);

        return Inertia::render('budget/lignes/form', [
            'mode' => 'create',
            'exercice_id_defaut' => $request->integer('exercice_id'),
            ...$this->referentielsForm(),
        ]);
    }

    public function store(Request $request, BudgetNomenclatureService $nomenclature): RedirectResponse
    {
        $this->authorize('create', BudgetLigne::class);

        $validated = $this->validerLigne($request);
        $exercice = BudgetExercice::findOrFail($validated['exercice_id']);

        try {
            $validated = $nomenclature->normaliserLigne($validated, $exercice, $request->user()->id);
            if (! empty($validated['budget_ligne_code']) && $nomenclature->codeExisteDeja($exercice, $validated['budget_ligne_code'])) {
                return back()->withErrors(['code_action' => 'Code de ligne budgétaire déjà utilisé pour cet exercice.'])->withInput();
            }
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['code_action' => $e->getMessage()])->withInput();
        }

        $ligne = BudgetLigne::create([
            ...$validated,
            'statut' => 'brouillon',
            'created_by' => $request->user()->id,
        ]);

        // Recalcul de l'exercice
        $ligne->exercice?->recalculerTotaux();

        return redirect()->route('budget.lignes.show', $ligne)
            ->with('success', "Ligne budgétaire {$ligne->code_action} créée.");
    }

    public function show(BudgetLigne $ligne): Response
    {
        $this->authorize('view', $ligne);

        $ligne->load([
            'exercice:id,annee,libelle,statut',
            'parent:id,libelle,code_action',
            'enfants',
            'source:id,code,libelle,type',
            'partenaire:id,code,libelle',
            'axe:id,code,libelle',
            'produit:id,code,libelle',
            'sousProduit:id,code,libelle',
            'activite:id,code,libelle',
            'tache:id,code,libelle',
            'departement:id,code,libelle',
            'direction:id,code,libelle',
            'mouvements' => fn ($q) => $q->orderBy('date_mouvement')->orderBy('id')->limit(50),
            'mouvements.saisiPar:id,name',
            'mouvements.ordonnateur:id,name',
            'mouvements.comptable:id,name',
            'mouvements.suivants:id,parent_mouvement_id,type,statut_mouvement',
        ]);

        $user = auth()->user();

        return Inertia::render('budget/lignes/show', [
            'ligne' => $ligne,
            'can' => [
                'update' => $user->can('update', $ligne),
                'delete' => $user->can('delete', $ligne),
                'validate' => $user->can('validate', $ligne),
                'engager_budget' => $user->can('engager_budget'),
                'liquider_budget' => $user->can('liquider_budget'),
                'ordonnancer_budget' => $user->can('ordonnancer_budget'),
                'payer_budget' => $user->can('payer_budget'),
            ],
        ]);
    }

    public function edit(BudgetLigne $ligne): Response
    {
        $this->authorize('update', $ligne);

        return Inertia::render('budget/lignes/form', [
            'mode' => 'edit',
            'ligne' => $ligne,
            ...$this->referentielsForm(),
        ]);
    }

    public function update(Request $request, BudgetLigne $ligne, BudgetNomenclatureService $nomenclature): RedirectResponse
    {
        $this->authorize('update', $ligne);

        $validated = $this->validerLigne($request);
        $exercice = BudgetExercice::findOrFail($validated['exercice_id']);

        try {
            $validated = $nomenclature->normaliserLigne($validated, $exercice, $request->user()->id);
            if (! empty($validated['budget_ligne_code']) && $nomenclature->codeExisteDeja($exercice, $validated['budget_ligne_code'], $ligne->id)) {
                return back()->withErrors(['code_action' => 'Code de ligne budgétaire déjà utilisé pour cet exercice.'])->withInput();
            }
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['code_action' => $e->getMessage()])->withInput();
        }

        $ligne->update([...$validated, 'updated_by' => $request->user()->id]);
        $ligne->exercice?->recalculerTotaux();

        return redirect()->route('budget.lignes.show', $ligne)->with('success', 'Ligne mise à jour.');
    }

    public function destroy(BudgetLigne $ligne): RedirectResponse
    {
        $this->authorize('delete', $ligne);
        $exercice = $ligne->exercice;
        $ligne->delete();
        $exercice?->recalculerTotaux();

        return redirect()->route('budget.lignes.index')->with('success', 'Ligne supprimée.');
    }

    protected function validerLigne(Request $request): array
    {
        return $request->validate([
            'exercice_id' => ['required', 'integer', 'exists:budget_exercices,id'],
            'parent_id' => ['nullable', 'integer', 'exists:budget_lignes,id'],
            'titre_code' => ['nullable', 'string', 'max:16'],
            'chapitre_code' => ['nullable', 'string', 'max:16'],
            'article_code' => ['nullable', 'string', 'max:16'],
            'paragraphe_code' => ['nullable', 'string', 'max:16'],
            'budget_ligne_code' => ['nullable', 'string', 'max:16'],
            'code_action' => ['nullable', 'string', 'max:32'],
            'code_projet' => ['nullable', 'string', 'max:32'],
            'libelle' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'nature' => ['required', Rule::in(BudgetLigne::NATURES)],
            'type_budget' => ['required', Rule::in(BudgetLigne::TYPES_BUDGET)],
            'pilier' => ['nullable', 'integer', 'min:1', 'max:10'],
            'montant_total' => ['required', 'numeric', 'min:0'],
            'montant_ceeac_em' => ['required', 'numeric', 'min:0'],
            'montant_ptf' => ['required', 'numeric', 'min:0'],
            'devise' => ['nullable', 'string', 'size:3'],
            'budget_annee_precedente' => ['nullable', 'numeric', 'min:0'],
            'realisation_annee_precedente' => ['nullable', 'numeric', 'min:0'],
            'source_financement_id' => ['nullable', 'integer', 'exists:budget_sources_financement,id'],
            'partenaire_id' => ['nullable', 'integer', 'exists:partenaires,id'],
            'axe_id' => ['nullable', 'integer', 'exists:axes,id'],
            'produit_id' => ['nullable', 'integer', 'exists:produits,id'],
            'sous_produit_id' => ['nullable', 'integer', 'exists:sous_produits,id'],
            'activite_id' => ['nullable', 'integer', 'exists:activites,id'],
            'tache_id' => ['nullable', 'integer', 'exists:taches,id'],
            'departement_id' => ['nullable', 'integer', 'exists:departements,id'],
            'direction_id' => ['nullable', 'integer', 'exists:directions,id'],
            'observations' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    protected function referentielsForm(): array
    {
        return [
            'exercices' => BudgetExercice::orderByDesc('annee')->get(['id', 'annee', 'libelle', 'statut']),
            'sources' => BudgetSourceFinancement::where('actif', true)->orderBy('libelle')->get(['id', 'code', 'libelle', 'type']),
            'axes' => Axe::orderBy('papa_id')->orderBy('ordre')->get(['id', 'code', 'libelle']),
            'produits' => Produit::orderBy('ordre')->get(['id', 'code', 'libelle', 'axe_id']),
            'sous_produits' => SousProduit::orderBy('ordre')->get(['id', 'code', 'libelle', 'produit_id']),
            'activites' => Activite::orderBy('ordre')->get(['id', 'code', 'libelle', 'sous_produit_id']),
            'taches' => Tache::orderBy('ordre')->get(['id', 'code', 'libelle', 'activite_id']),
            'departements' => Departement::orderBy('ordre')->get(['id', 'code', 'libelle']),
            'directions' => Direction::orderBy('libelle')->get(['id', 'code', 'libelle']),
        ];
    }
}
