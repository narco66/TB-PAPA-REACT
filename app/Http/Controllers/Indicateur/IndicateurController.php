<?php

namespace App\Http\Controllers\Indicateur;

use App\Http\Controllers\Controller;
use App\Http\Requests\Indicateur\StoreIndicateurRequest;
use App\Http\Requests\Indicateur\StoreValeurIndicateurRequest;
use App\Models\Indicateur;
use App\Models\SousProduit;
use App\Models\User;
use App\Models\ValeurIndicateur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndicateurController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Indicateur::class);

        $query = Indicateur::query()->with([
            'sousProduit:id,code,libelle,produit_id',
            'sousProduit.produit:id,code,libelle,axe_id',
            'sousProduit.produit.axe:id,code,libelle',
            'responsable:id,name',
        ]);

        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(fn ($w) => $w->where('code', 'like', "%{$q}%")->orWhere('libelle', 'like', "%{$q}%"));
        }
        if ($freq = $request->string('frequence')->toString()) {
            $query->where('frequence_collecte', $freq);
        }
        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }
        if ($categorie = $request->string('categorie')->toString()) {
            $query->where('categorie', $categorie);
        }

        $indicateurs = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $stats = [
            'total' => Indicateur::count(),
            'par_categorie' => [
                'impact' => Indicateur::where('categorie', 'impact')->count(),
                'effet' => Indicateur::where('categorie', 'effet')->count(),
                'produit' => Indicateur::where('categorie', 'produit')->count(),
                'processus' => Indicateur::where('categorie', 'processus')->count(),
            ],
            'atteints' => Indicateur::where('taux_realisation', '>=', 100)->count(),
            'a_risque' => Indicateur::where('taux_realisation', '<', 40)->whereNotNull('valeur_actuelle')->count(),
            'taux_moyen' => round((float) Indicateur::whereNotNull('taux_realisation')->avg('taux_realisation'), 1),
            'desagrege_genre' => Indicateur::where('desagregation_genre', true)->count(),
        ];

        return Inertia::render('indicateurs/index', [
            'indicateurs' => $indicateurs,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'frequence' => $request->string('frequence')->toString(),
                'type' => $request->string('type')->toString(),
                'categorie' => $request->string('categorie')->toString(),
            ],
            'stats' => $stats,
            'options' => [
                'categories' => Indicateur::CATEGORIES_LABELS,
                'frequences' => Indicateur::FREQUENCES,
                'types' => Indicateur::TYPES,
            ],
            'can' => ['create' => $request->user()->can('indicateur.create')],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Indicateur::class);

        return Inertia::render('indicateurs/create', [
            'sousProduits' => SousProduit::with('produit.axe:id,code')
                ->orderBy('ordre')
                ->get(['id', 'code', 'libelle', 'produit_id'])
                ->map(fn ($sp) => [
                    'id' => $sp->id,
                    'code' => $sp->code,
                    'libelle' => $sp->libelle,
                    'parent' => $sp->produit?->axe?->code . ' / ' . $sp->produit?->code,
                ]),
            'responsables' => User::where('actif', true)->orderBy('name')->get(['id', 'name', 'fonction']),
            'options' => [
                'categories' => Indicateur::CATEGORIES_LABELS,
                'polarites' => Indicateur::POLARITES_LABELS,
                'frequences' => Indicateur::FREQUENCES,
                'types' => Indicateur::TYPES,
            ],
        ]);
    }

    public function store(StoreIndicateurRequest $request): RedirectResponse
    {
        $indicateur = Indicateur::create($request->validated());

        return redirect()->route('indicateurs.show', $indicateur)->with('success', "Indicateur {$indicateur->code} créé.");
    }

    public function show(Indicateur $indicateur): Response
    {
        $this->authorize('view', $indicateur);

        $indicateur->load([
            'sousProduit.produit.axe.papa:id,annee,libelle',
            'responsable:id,name,fonction',
            'responsableCollecte:id,name,fonction',
            'valeurs' => fn ($q) => $q->orderByDesc('date_observation')->limit(50),
            'valeurs.saisiPar:id,name',
            'valeurs.validePar:id,name',
        ]);

        $paliers = collect(['T1', 'T2', 'T3', 'T4'])->map(function ($t) use ($indicateur) {
            $cible = $indicateur->ciblePalier($t);
            $valeurTrim = $indicateur->valeurs
                ->where('trimestre', $t)
                ->sortByDesc('date_observation')
                ->first();

            return [
                'trimestre' => $t,
                'cible' => $cible,
                'valeur' => $valeurTrim?->valeur,
                'ecart' => $valeurTrim && $cible !== null ? round($valeurTrim->valeur - $cible, 4) : null,
                'date' => $valeurTrim?->date_observation?->toDateString(),
            ];
        })->all();

        return Inertia::render('indicateurs/show', [
            'indicateur' => array_merge($indicateur->toArray(), [
                'categorie_libelle' => Indicateur::CATEGORIES_LABELS[$indicateur->categorie] ?? null,
                'polarite_libelle' => Indicateur::POLARITES_LABELS[$indicateur->polarite] ?? null,
                'dimensions_desagregation' => $indicateur->dimensionsDesagregation(),
                'hors_plage' => $indicateur->estHorsPlage(),
            ]),
            'paliers' => $paliers,
            'can' => [
                'saisie' => auth()->user()->can('saisie', $indicateur),
                'update' => auth()->user()->can('update', $indicateur),
                'validate' => auth()->user()->can('indicateur.validate'),
            ],
        ]);
    }

    public function storeValeur(StoreValeurIndicateurRequest $request, Indicateur $indicateur): RedirectResponse
    {
        ValeurIndicateur::create([
            ...$request->validated(),
            'indicateur_id' => $indicateur->id,
            'saisi_par_id' => $request->user()->id,
        ]);

        $indicateur->valeur_actuelle = $request->float('valeur');
        $indicateur->recalculerTauxRealisation();
        $indicateur->save();

        return back()->with('success', 'Valeur saisie. Taux de réalisation recalculé.');
    }
}
