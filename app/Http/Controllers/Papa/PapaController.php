<?php

namespace App\Http\Controllers\Papa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Papa\WorkflowTransitionRequest;
use App\Models\Papa;
use App\Models\User;
use App\Models\Validation;
use App\Notifications\WorkflowTransitionNotification;
use App\Services\Workflow\WorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PapaController extends Controller
{
    public function __construct(protected WorkflowService $workflow) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Papa::class);

        $query = Papa::query()
            ->with(['valideur:id,name', 'createur:id,name'])
            ->withCount('axes as axes_count');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(fn ($q) => $q
                ->where('libelle', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('annee', 'like', "%{$search}%"),
            );
        }
        if ($statut = $request->string('statut')->toString()) {
            $query->where('statut', $statut);
        }

        $papas = $query->orderByDesc('annee')->orderByDesc('id')->paginate(15)->withQueryString();

        $papaActif = Papa::actif()->orderByDesc('annee')->first();

        $stats = [
            'total' => Papa::count(),
            'brouillon' => Papa::where('statut', Papa::STATUT_BROUILLON)->count(),
            'en_validation' => Papa::where('statut', Papa::STATUT_EN_VALIDATION)->count(),
            'valides' => Papa::where('statut', Papa::STATUT_VALIDE)->count(),
            'clotures' => Papa::where('statut', Papa::STATUT_CLOTURE)->count(),
            'archives' => Papa::where('statut', Papa::STATUT_ARCHIVE)->count(),
            'annee_min' => (int) Papa::min('annee'),
            'annee_max' => (int) Papa::max('annee'),
            'papa_actif' => $papaActif ? [
                'id' => $papaActif->id,
                'annee' => $papaActif->annee,
                'libelle' => $papaActif->libelle,
                'statut' => $papaActif->statut,
                'taux_execution_physique' => $papaActif->tauxExecutionPhysique(),
                'taux_execution_financier' => $papaActif->tauxExecutionFinancier(),
                'date_debut' => $papaActif->date_debut?->toIso8601String(),
                'date_fin' => $papaActif->date_fin?->toIso8601String(),
            ] : null,
        ];

        return Inertia::render('papa/index', [
            'papas' => $papas,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'statut' => $request->string('statut')->toString(),
            ],
            'statuts' => Papa::STATUTS,
            'stats' => $stats,
            'can' => ['create' => $request->user()->can('papa.create')],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Papa::class);
        $derniereAnnee = (int) (Papa::max('annee') ?? now()->year);

        return Inertia::render('papa/create', [
            'annee_suggeree' => $derniereAnnee + 1,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Papa::class);

        $validated = $request->validate([
            'annee' => ['required', 'integer', 'min:2024', 'max:2050'],
            'version' => ['required', 'string', 'max:16'],
            'libelle' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'perimetre_institutionnel' => ['nullable', 'string', 'max:5000'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
        ]);

        $papa = Papa::create([
            ...$validated,
            'statut' => Papa::STATUT_BROUILLON,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('papa.show', $papa)->with('success', "PAPA {$papa->annee} (v{$papa->version}) créé.");
    }

    public function show(Papa $papa, Request $request): Response
    {
        $this->authorize('view', $papa);

        $papa->load(['valideur:id,name,fonction', 'createur:id,name,fonction', 'clotureur:id,name,fonction']);
        $papa->loadCount('axes as axes_count');

        $axes = $papa->axes()->orderBy('ordre')->get(['id', 'code', 'libelle', 'statut', 'poids', 'taux_execution']);

        $user = $request->user();
        $transitions = $this->workflow->transitionsDisponibles($papa, $user);
        $historique = $this->workflow->historique($papa)->map(fn ($v) => [
            'id' => $v->id,
            'etape' => $v->etape,
            'decision' => $v->decision,
            'commentaire' => $v->commentaire,
            'demandeur' => $v->demandeur?->name,
            'valideur' => $v->valideur?->name,
            'date' => $v->decide_at?->toIso8601String(),
            'date_relative' => $v->decide_at?->diffForHumans(),
            'avant' => $v->donnees_avant['statut'] ?? null,
            'apres' => $v->donnees_apres['statut'] ?? null,
        ]);

        return Inertia::render('papa/show', [
            'papa' => [
                'id' => $papa->id,
                'annee' => $papa->annee,
                'version' => $papa->version,
                'libelle' => $papa->libelle,
                'description' => $papa->description,
                'perimetre_institutionnel' => $papa->perimetre_institutionnel,
                'statut' => $papa->statut,
                'date_debut' => $papa->date_debut?->toIso8601String(),
                'date_fin' => $papa->date_fin?->toIso8601String(),
                'date_validation' => $papa->date_validation?->toIso8601String(),
                'cloture_le' => $papa->cloture_le?->toIso8601String(),
                'verrouille' => $papa->verrouille,
                'valideur' => $papa->valideur,
                'createur' => $papa->createur,
                'axes_count' => $papa->axes_count,
                'taux_execution_physique' => $papa->tauxExecutionPhysique(),
                'taux_execution_financier' => $papa->tauxExecutionFinancier(),
            ],
            'axes' => $axes,
            'transitions' => $transitions,
            'historique' => $historique,
            'can' => [
                'update' => $user->can('update', $papa),
                'delete' => $user->can('delete', $papa),
                'create_axe' => $user->can('create_axes'),
            ],
        ]);
    }

    /**
     * Exécute une transition de workflow (submit/approve/reject/revise/resubmit/close/archive).
     */
    public function transition(WorkflowTransitionRequest $request, Papa $papa, string $action): RedirectResponse
    {
        try {
            $validation = $this->workflow->transition(
                $papa,
                $request->user(),
                $action,
                $request->validated()['commentaire'] ?? null,
            );

            $this->notifierAuditeurs($validation, $action, $request->user());

            $messages = [
                'submit' => 'PAPA soumis pour validation.',
                'resubmit' => 'PAPA re-soumis après révision.',
                'approve' => 'PAPA validé.',
                'reject' => 'PAPA rejeté — retour brouillon pour révision.',
                'revise' => 'PAPA mis en révision.',
                'close' => 'PAPA clôturé.',
                'archive' => 'PAPA archivé.',
            ];

            return back()->with('success', $messages[$action] ?? 'Transition effectuée.');
        } catch (Throwable $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }
    }

    protected function notifierAuditeurs(Validation $validation, string $action, User $auteur): void
    {
        $libelles = [
            'submit' => 'Soumission pour validation',
            'resubmit' => 'Re-soumission',
            'approve' => 'Approbation',
            'reject' => 'Rejet',
            'revise' => 'Mise en révision',
            'close' => 'Clôture',
            'archive' => 'Archivage',
        ];

        $destinataires = User::role(['audit_interne', 'controle_financier', 'secretaire_general'])
            ->where('actif', true)
            ->where('id', '!=', $auteur->id)
            ->get();

        if ($destinataires->isNotEmpty()) {
            Notification::send($destinataires, new WorkflowTransitionNotification(
                $validation,
                $libelles[$action] ?? $action,
                $auteur,
            ));
        }
    }
}
