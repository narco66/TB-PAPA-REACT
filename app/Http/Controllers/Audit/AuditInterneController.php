<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Audit\StoreAuditConstatRequest;
use App\Http\Requests\Audit\StoreAuditMissionRequest;
use App\Http\Requests\Audit\StoreAuditPlanRequest;
use App\Http\Requests\Audit\StoreAuditRecommandationRequest;
use App\Http\Requests\Audit\StoreAuditSuiviRequest;
use App\Models\Audit\AuditConstat;
use App\Models\Audit\AuditMission;
use App\Models\Audit\AuditPlan;
use App\Models\Audit\AuditRecommandation;
use App\Models\Audit\AuditSuiviRecommandation;
use App\Models\Departement;
use App\Models\Direction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Module Audit interne IGS — conformité IIA/IPPF, IFACI, ISO 19011, COSO.
 *
 * Gère le cycle complet : Plan annuel → Missions → Constats → Recommandations → Suivis.
 */
class AuditInterneController extends Controller
{
    // === Dashboard ===

    public function dashboard(Request $request): Response
    {
        $this->authorize('audit_interne.view');

        $annee = (int) ($request->input('annee') ?? now()->year);

        $stats = [
            'plans_total' => AuditPlan::count(),
            'plan_en_cours' => AuditPlan::where('annee', $annee)->first(),
            'missions_total' => AuditMission::count(),
            'missions_en_cours' => AuditMission::where('statut', 'en_cours')->count(),
            'missions_cloturees' => AuditMission::where('statut', 'cloturee')->count(),
            'constats_total' => AuditConstat::count(),
            'constats_critiques' => AuditConstat::where('gravite', 'critique')->count(),
            'recommandations_total' => AuditRecommandation::count(),
            'recommandations_ouvertes' => AuditRecommandation::whereNotIn('statut', ['verifiee', 'rejetee', 'abandonnee'])->count(),
            'recommandations_en_retard' => AuditRecommandation::whereDate('date_echeance', '<', now())
                ->whereNotIn('statut', ['verifiee', 'rejetee', 'abandonnee'])
                ->count(),
        ];

        $missionsRecentes = AuditMission::with(['plan:id,annee,libelle', 'chefMission:id,name'])
            ->latest()
            ->limit(8)
            ->get();

        return Inertia::render('audit/dashboard', [
            'stats' => $stats,
            'annee' => $annee,
            'missions_recentes' => $missionsRecentes,
        ]);
    }

    // === Plans d'audit ===

    public function plansIndex(Request $request): Response
    {
        $this->authorize('audit_interne.view');

        $plans = AuditPlan::query()
            ->withCount('missions')
            ->orderByDesc('annee')
            ->paginate(15);

        return Inertia::render('audit/plans/index', [
            'plans' => $plans,
            'statuts' => AuditPlan::STATUTS,
            'can' => ['create' => $request->user()->can('audit_interne.plan_create')],
        ]);
    }

    public function plansCreate(): Response
    {
        $this->authorize('audit_interne.plan_create');

        return Inertia::render('audit/plans/form', [
            'mode' => 'create',
            'annee_suggeree' => (int) (AuditPlan::max('annee') ?? now()->year) + 1,
            'statuts' => AuditPlan::STATUTS,
        ]);
    }

    public function plansStore(StoreAuditPlanRequest $request): RedirectResponse
    {
        $plan = AuditPlan::create([
            ...$request->validated(),
            'statut' => $request->input('statut', 'projet'),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('audit.plans.show', $plan)
            ->with('success', "Plan d'audit {$plan->annee} créé.");
    }

    public function plansShow(AuditPlan $plan, Request $request): Response
    {
        $this->authorize('audit_interne.view');

        $plan->load(['validePar:id,name', 'createur:id,name']);
        $plan->loadCount('missions');

        $missions = $plan->missions()
            ->with(['chefMission:id,name', 'departementAudite:id,libelle'])
            ->withCount(['constats', 'recommandations'])
            ->orderByDesc('date_debut_prevue')
            ->get();

        return Inertia::render('audit/plans/show', [
            'plan' => $plan,
            'missions' => $missions,
            'can' => [
                'update' => $request->user()->can('audit_interne.plan_create'),
                'validate' => $request->user()->can('audit_interne.plan_validate'),
                'mission_create' => $request->user()->can('audit_interne.mission_create'),
            ],
        ]);
    }

    public function plansValider(AuditPlan $plan, Request $request): RedirectResponse
    {
        $this->authorize('audit_interne.plan_validate');

        if ($plan->statut !== 'soumis') {
            return back()->with('error', 'Seul un plan soumis peut être validé.');
        }

        $plan->update([
            'statut' => 'valide',
            'valide_par_id' => $request->user()->id,
            'valide_at' => now(),
        ]);

        return back()->with('success', "Plan d'audit {$plan->annee} validé.");
    }

    // === Missions ===

    public function missionsIndex(Request $request): Response
    {
        $this->authorize('audit_interne.view');

        $missions = AuditMission::query()
            ->with(['plan:id,annee,libelle', 'chefMission:id,name', 'departementAudite:id,libelle'])
            ->withCount(['constats', 'recommandations'])
            ->when($request->input('plan_id'), fn ($q, $id) => $q->where('plan_id', $id))
            ->when($request->input('statut'), fn ($q, $s) => $q->where('statut', $s))
            ->when($request->input('type'), fn ($q, $t) => $q->where('type', $t))
            ->orderByDesc('date_debut_prevue')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('audit/missions/index', [
            'missions' => $missions,
            'plans' => AuditPlan::orderByDesc('annee')->get(['id', 'annee', 'libelle']),
            'types' => AuditMission::TYPES,
            'statuts' => AuditMission::STATUTS,
            'filters' => $request->only(['plan_id', 'statut', 'type']),
            'can' => ['create' => $request->user()->can('audit_interne.mission_create')],
        ]);
    }

    public function missionsCreate(Request $request): Response
    {
        $this->authorize('audit_interne.mission_create');

        return Inertia::render('audit/missions/form', [
            'mode' => 'create',
            'plans' => AuditPlan::whereIn('statut', ['valide', 'execute'])
                ->orderByDesc('annee')->get(['id', 'annee', 'libelle']),
            'plan_id_initial' => (int) $request->input('plan_id', 0) ?: null,
            'departements' => Departement::orderBy('libelle')->get(['id', 'libelle']),
            'directions' => Direction::orderBy('libelle')->get(['id', 'libelle']),
            'auditeurs' => User::role(['audit_interne', 'admin_technique'])
                ->orderBy('name')
                ->get(['id', 'name']),
            'types' => AuditMission::TYPES,
            'priorites' => AuditMission::PRIORITES,
            'statuts' => AuditMission::STATUTS,
        ]);
    }

    public function missionsStore(StoreAuditMissionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $equipe = $data['equipe'] ?? [];
        unset($data['equipe']);

        $mission = DB::transaction(function () use ($data, $equipe, $request) {
            $mission = AuditMission::create([
                ...$data,
                'statut' => $data['statut'] ?? 'planifiee',
                'created_by' => $request->user()->id,
            ]);

            foreach ($equipe as $membre) {
                $mission->equipe()->attach($membre['user_id'], [
                    'role_mission' => $membre['role_mission'],
                ]);
            }

            return $mission;
        });

        return redirect()->route('audit.missions.show', $mission)
            ->with('success', "Mission « {$mission->code} » créée.");
    }

    public function missionsShow(AuditMission $mission, Request $request): Response
    {
        $this->authorize('audit_interne.view');

        $mission->load([
            'plan:id,annee,libelle',
            'chefMission:id,name,fonction',
            'departementAudite:id,libelle',
            'directionAuditee:id,libelle',
            'equipe:id,name,fonction',
            'createur:id,name',
        ]);

        $constats = $mission->constats()
            ->with(['saisiPar:id,name'])
            ->withCount('recommandations')
            ->orderBy('code')
            ->get();

        return Inertia::render('audit/missions/show', [
            'mission' => $mission,
            'constats' => $constats,
            'can' => [
                'update' => $request->user()->can('audit_interne.mission_create'),
                'execute' => $request->user()->can('audit_interne.mission_execute'),
                'close' => $request->user()->can('audit_interne.mission_close'),
                'constat_create' => $request->user()->can('audit_interne.constat_create'),
            ],
        ]);
    }

    // === Constats ===

    public function constatsStore(StoreAuditConstatRequest $request): RedirectResponse
    {
        $constat = AuditConstat::create([
            ...$request->validated(),
            'saisi_par_id' => $request->user()->id,
        ]);

        return redirect()->route('audit.constats.show', $constat)
            ->with('success', "Constat « {$constat->code} » créé.");
    }

    public function constatsShow(AuditConstat $constat, Request $request): Response
    {
        $this->authorize('audit_interne.view');

        $constat->load([
            'mission:id,code,titre,plan_id',
            'mission.plan:id,annee,libelle',
            'saisiPar:id,name',
        ]);

        $recommandations = $constat->recommandations()
            ->with(['responsable:id,name', 'suivis' => fn ($q) => $q->latest('date_suivi')->limit(3)])
            ->orderBy('code')
            ->get();

        return Inertia::render('audit/constats/show', [
            'constat' => $constat,
            'recommandations' => $recommandations,
            'gravites' => AuditConstat::GRAVITES,
            'natures' => AuditConstat::NATURES,
            'can' => [
                'recommandation_create' => $request->user()->can('audit_interne.recommandation_create'),
                'update' => $request->user()->can('audit_interne.constat_create'),
            ],
        ]);
    }

    // === Recommandations ===

    public function recommandationsIndex(Request $request): Response
    {
        $this->authorize('audit_interne.view');

        $recommandations = AuditRecommandation::query()
            ->with([
                'constat:id,mission_id,code,libelle',
                'constat.mission:id,code,titre',
                'responsable:id,name',
            ])
            ->when($request->input('statut'), fn ($q, $s) => $q->where('statut', $s))
            ->when($request->input('priorite'), fn ($q, $p) => $q->where('priorite', $p))
            ->when($request->boolean('en_retard'), fn ($q) => $q->whereDate('date_echeance', '<', now())
                ->whereNotIn('statut', ['verifiee', 'rejetee', 'abandonnee']))
            ->orderBy('date_echeance')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('audit/recommandations/index', [
            'recommandations' => $recommandations,
            'statuts' => AuditRecommandation::STATUTS,
            'priorites' => AuditRecommandation::PRIORITES,
            'filters' => $request->only(['statut', 'priorite', 'en_retard']),
        ]);
    }

    public function recommandationsStore(StoreAuditRecommandationRequest $request): RedirectResponse
    {
        $recommandation = AuditRecommandation::create([
            ...$request->validated(),
            'statut' => $request->input('statut', 'ouverte'),
            'saisi_par_id' => $request->user()->id,
        ]);

        return redirect()->route('audit.recommandations.show', $recommandation)
            ->with('success', "Recommandation « {$recommandation->code} » créée.");
    }

    public function recommandationsShow(AuditRecommandation $recommandation, Request $request): Response
    {
        $this->authorize('audit_interne.view');

        $recommandation->load([
            'constat:id,mission_id,code,libelle',
            'constat.mission:id,code,titre,plan_id',
            'constat.mission.plan:id,annee,libelle',
            'responsable:id,name,fonction',
            'saisiPar:id,name',
        ]);

        $suivis = $recommandation->suivis()
            ->with('suiviPar:id,name')
            ->orderByDesc('date_suivi')
            ->get();

        return Inertia::render('audit/recommandations/show', [
            'recommandation' => $recommandation,
            'suivis' => $suivis,
            'etats' => AuditSuiviRecommandation::ETATS,
            'can' => [
                'suivi_create' => $request->user()->can('audit_interne.suivi_create'),
                'update' => $request->user()->can('audit_interne.recommandation_create'),
            ],
        ]);
    }

    // === Suivis ===

    public function suivisStore(StoreAuditSuiviRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $suivi = DB::transaction(function () use ($data, $request) {
            $suivi = AuditSuiviRecommandation::create([
                ...$data,
                'suivi_par_id' => $request->user()->id,
            ]);

            $recommandation = AuditRecommandation::find($data['recommandation_id']);
            if ($recommandation) {
                $statutMap = [
                    'non_demarre' => 'ouverte',
                    'en_cours' => 'en_cours',
                    'realise' => 'mise_en_oeuvre',
                    'bloque' => 'en_cours',
                    'abandonne' => 'abandonnee',
                ];

                $recommandation->update([
                    'pourcentage_avancement' => $data['pourcentage'],
                    'statut' => $statutMap[$data['etat_avancement']] ?? $recommandation->statut,
                ]);
            }

            return $suivi;
        });

        return redirect()->route('audit.recommandations.show', $data['recommandation_id'])
            ->with('success', "Suivi enregistré au {$suivi->date_suivi->format('d/m/Y')}.");
    }
}
