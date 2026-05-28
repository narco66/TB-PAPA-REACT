@extends('reports.layouts.institutional')

@section('content')
    @php
        $papa = $donnees['papa'];
        $stats = $donnees['stats'];
        $execution = $donnees['execution'];
        $budget = $donnees['budget'];
        $topAxes = $donnees['topAxes'];
        $axesRetard = $donnees['axesEnRetard'];
        $indCmr = $donnees['indicateursCmr'];
        $alertesCritiques = $donnees['alertesCritiques'];
        $cartoRisques = $donnees['cartoRisques'];
        $audit = $donnees['audit'];
        $validations = $donnees['validations'];
        $decisions = $donnees['decisions'];
        $recos = $donnees['recommandations'];

        $fmt = fn ($n, $dec = 0) => number_format((float) $n, $dec, ',', ' ');
        $pct = fn ($n) => number_format((float) $n, 1) . '%';
        $tg = $stats['taux_global'];
    @endphp

    <div class="metas">
        <table>
            <tr>
                <td class="label">Exercice</td>
                <td><strong>PAPA {{ $papa->annee }}</strong> (v{{ $papa->version }})</td>
                <td class="label">Statut</td>
                <td><span class="badge {{ $papa->statut === 'valide' ? 'success' : 'warning' }}">{{ $papa->statut }}</span></td>
            </tr>
            <tr>
                <td class="label">Période</td>
                <td>{{ optional($papa->date_debut)->format('d/m/Y') }} → {{ optional($papa->date_fin)->format('d/m/Y') }}</td>
                <td class="label">Validé par</td>
                <td>{{ $papa->valideur->name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Confidentialité</td>
                <td colspan="3"><span class="badge danger">CABINET PRÉSIDENTIEL</span> — Diffusion restreinte aux membres du Cabinet et du Secrétariat Général</td>
            </tr>
        </table>
    </div>

    {{-- ============================================================== --}}
    {{-- RÉSUMÉ EXÉCUTIF (Top of page)                                   --}}
    {{-- ============================================================== --}}
    @php
        $niveauPil = $tg >= 75 ? 'success' : ($tg >= 40 ? 'warning' : 'danger');
        $libellePil = $tg >= 75 ? 'Pilotage maîtrisé' : ($tg >= 40 ? 'Pilotage à renforcer' : 'Pilotage critique');
    @endphp
    <div class="callout {{ $niveauPil }}">
        <div class="titre">Résumé exécutif — {{ $libellePil }}</div>
        Au {{ $genere_le->format('d/m/Y') }}, le PAPA {{ $papa->annee }} présente un taux d'exécution physique de <strong>{{ $pct($tg) }}</strong>
        et un taux d'exécution financière de <strong>{{ $pct($stats['taux_financier']) }}</strong>.
        {{ $alertesCritiques->count() }} alerte(s) critique(s) en cours,
        {{ $execution['activites_retard'] }} activité(s) en retard,
        {{ $audit['recommandations_en_retard'] }} recommandation(s) d'audit en retard.
        {{ count($decisions) }} décision(s) présidentielle(s) requise(s).
    </div>

    {{-- ============================================================== --}}
    {{-- I. INDICATEURS CLÉS                                             --}}
    {{-- ============================================================== --}}
    <h1>I. Indicateurs clés de performance</h1>

    <div class="kpi-grid">
        <div class="kpi" style="border-left-color: {{ $niveauPil === 'success' ? '#16a34a' : ($niveauPil === 'warning' ? '#f59e0b' : '#dc2626') }}">
            <div class="label">Exécution physique</div>
            <div class="value">{{ $pct($tg) }}</div>
            <div class="sub">Moyenne pondérée des axes</div>
        </div>
        <div class="kpi">
            <div class="label">Exécution financière</div>
            <div class="value">{{ $pct($stats['taux_financier']) }}</div>
            <div class="sub">Consommation / Prévision</div>
        </div>
        <div class="kpi">
            <div class="label">Indicateurs CMR</div>
            <div class="value">{{ $indCmr['total'] }}</div>
            <div class="sub">Taux moyen {{ $pct($indCmr['taux_moyen']) }}</div>
        </div>
        <div class="kpi" style="border-left-color: {{ $alertesCritiques->count() > 0 ? '#dc2626' : '#16a34a' }}">
            <div class="label">Alertes critiques</div>
            <div class="value">{{ $alertesCritiques->count() }}</div>
            <div class="sub">En attente de résolution</div>
        </div>
    </div>

    <h2>1.1 Réalisation des activités</h2>
    <div class="stat-row">
        <div class="item"><div class="l">Réalisées</div><div class="v text-success">{{ $execution['activites_realisees'] }}</div></div>
        <div class="item"><div class="l">En cours</div><div class="v text-primary">{{ $execution['activites_en_cours'] }}</div></div>
        <div class="item"><div class="l">Planifiées</div><div class="v">{{ $execution['activites_planifiees'] }}</div></div>
        <div class="item"><div class="l">Suspendues</div><div class="v text-warning">{{ $execution['activites_suspendues'] }}</div></div>
        <div class="item"><div class="l">En retard</div><div class="v text-danger">{{ $execution['activites_retard'] }}</div></div>
    </div>

    {{-- ============================================================== --}}
    {{-- II. VOLUMÉTRIE RBM/GAR                                          --}}
    {{-- ============================================================== --}}
    <h1>II. Volumétrie de la chaîne RBM/GAR</h1>

    <table class="data">
        <thead><tr><th>Niveau</th><th class="right">Compte</th><th>Code</th><th>Description</th></tr></thead>
        <tbody>
            <tr><td class="strong">Axes stratégiques</td><td class="num">{{ $stats['axes'] }}</td><td>AXE N</td><td class="small">Politiques sectorielles validées par les Commissaires</td></tr>
            <tr><td class="strong">Produits</td><td class="num">{{ $stats['produits'] }}</td><td>P.N.N</td><td class="small">Livrables institutionnels rattachés aux axes</td></tr>
            <tr><td class="strong">Sous-Produits</td><td class="num">{{ $stats['sous_produits'] }}</td><td>SP.N.N.N</td><td class="small">Décompositions opérationnelles des produits</td></tr>
            <tr><td class="strong">Activités</td><td class="num">{{ $stats['activites'] }}</td><td>ACT.N.N.N.N</td><td class="small">Actions planifiées (suivi Gantt)</td></tr>
            <tr><td class="strong">Tâches</td><td class="num">{{ $stats['taches'] }}</td><td>T.N.N.N.N.N</td><td class="small">Exécution opérationnelle granulaire</td></tr>
        </tbody>
    </table>

    {{-- ============================================================== --}}
    {{-- III. BUDGET INSTITUTIONNEL                                      --}}
    {{-- ============================================================== --}}
    @if($budget)
        <h1>III. Cadre budgétaire</h1>

        <table class="data">
            <tr>
                <th>Budget total prévisionnel ({{ $budget['devise'] }})</th>
                <td class="num strong">{{ $fmt($budget['total']) }}</td>
                <th>Engagé</th>
                <td class="num">{{ $fmt($budget['engage']) }} <span class="small text-muted">({{ $pct($budget['taux_engagement']) }})</span></td>
            </tr>
            <tr>
                <th>Part CEEAC-EM</th>
                <td class="num">{{ $fmt($budget['ceeac']) }} <span class="small text-muted">({{ $pct($budget['taux_ceeac']) }})</span></td>
                <th>Payé</th>
                <td class="num">{{ $fmt($budget['paye']) }} <span class="small text-muted">({{ $pct($budget['taux_paiement']) }})</span></td>
            </tr>
            <tr>
                <th>Part PTF</th>
                <td class="num">{{ $fmt($budget['ptf']) }} <span class="small text-muted">({{ $pct($budget['taux_ptf']) }})</span></td>
                <th>Cohérence physique/payé</th>
                <td>
                    @php($ecart = abs($budget['taux_paiement'] - $tg))
                    <span class="badge {{ $ecart <= 15 ? 'success' : 'danger' }}">{{ $fmt($ecart, 1) }} pts d'écart</span>
                </td>
            </tr>
        </table>
    @endif

    {{-- ============================================================== --}}
    {{-- IV. INDICATEURS CMR (OCDE)                                      --}}
    {{-- ============================================================== --}}
    <h1>IV. Indicateurs CMR — Typologie OCDE/CAD</h1>

    <div class="stat-row">
        <div class="item"><div class="l">Total</div><div class="v">{{ $indCmr['total'] }}</div></div>
        <div class="item"><div class="l">Atteints (≥100%)</div><div class="v text-success">{{ $indCmr['atteints'] }}</div></div>
        <div class="item"><div class="l">À risque (&lt;40%)</div><div class="v text-danger">{{ $indCmr['a_risque'] }}</div></div>
        <div class="item"><div class="l">Taux moyen</div><div class="v">{{ $pct($indCmr['taux_moyen']) }}</div></div>
    </div>

    @if(!empty($indCmr['par_categorie']))
        <table class="data">
            <thead><tr><th>Catégorie</th><th class="right">Nombre</th><th>Taux moyen de réalisation</th></tr></thead>
            <tbody>
                @foreach($indCmr['par_categorie'] as $c)
                    <tr>
                        <td class="strong">{{ ucfirst($c['categorie']) }}</td>
                        <td class="num">{{ $c['nb'] }}</td>
                        <td>
                            <div class="progress {{ $c['taux'] >= 75 ? 'success' : ($c['taux'] >= 40 ? 'warning' : 'danger') }}">
                                <div class="fill" style="width: {{ min(100, $c['taux']) }}%"></div>
                            </div>
                            <div class="small">{{ $pct($c['taux']) }}</div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="page-break"></div>

    {{-- ============================================================== --}}
    {{-- V. TOP AXES + AXES EN RETARD                                    --}}
    {{-- ============================================================== --}}
    <h1>V. Performance des axes stratégiques</h1>

    <h2>5.1 Top 5 — Axes les plus performants</h2>
    @if($topAxes->isEmpty())
        <p class="text-muted small">Aucune donnée disponible.</p>
    @else
        <table class="data">
            <thead><tr><th style="width:12%">Code</th><th>Libellé</th><th style="width:15%">Département</th><th style="width:25%">Avancement</th></tr></thead>
            <tbody>
                @foreach($topAxes as $axe)
                    <tr>
                        <td class="strong">{{ $axe->code }}</td>
                        <td>{{ $axe->libelle }}</td>
                        <td>{{ $axe->departement->code ?? '—' }}</td>
                        <td>
                            <div class="progress success"><div class="fill" style="width: {{ min(100, $axe->taux_execution) }}%"></div></div>
                            <div class="small">{{ $pct($axe->taux_execution) }}</div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>5.2 Axes nécessitant une intervention (&lt; 40 %)</h2>
    @if($axesRetard->isEmpty())
        <p class="text-success small strong"><span class="traffic success"></span>Aucun axe stratégique en sous-performance.</p>
    @else
        <table class="data">
            <thead><tr><th style="width:12%">Code</th><th>Libellé</th><th style="width:15%">Département</th><th style="width:25%">Avancement</th></tr></thead>
            <tbody>
                @foreach($axesRetard as $axe)
                    <tr>
                        <td class="strong">{{ $axe->code }}</td>
                        <td>{{ $axe->libelle }}</td>
                        <td>{{ $axe->departement->code ?? '—' }}</td>
                        <td>
                            <div class="progress danger"><div class="fill" style="width: {{ min(100, $axe->taux_execution) }}%"></div></div>
                            <div class="small">{{ $pct($axe->taux_execution) }}</div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ============================================================== --}}
    {{-- VI. CARTOGRAPHIE DES RISQUES                                    --}}
    {{-- ============================================================== --}}
    <h1>VI. Cartographie des risques</h1>

    <table class="data">
        <thead><tr><th>Niveau de risque</th><th class="right">Nombre d'alertes ouvertes</th><th>Action requise</th></tr></thead>
        <tbody>
            <tr><td><span class="traffic danger"></span><strong>Critique</strong></td><td class="num">{{ $cartoRisques['critique'] ?? 0 }}</td><td class="small">Traitement immédiat (< 3 jours)</td></tr>
            <tr><td><span class="traffic warning"></span><strong>Attention</strong></td><td class="num">{{ $cartoRisques['attention'] ?? 0 }}</td><td class="small">Plan d'action sous 14 jours</td></tr>
            <tr><td><span class="traffic"></span style="background:#3b82f6"><strong>Info</strong></td><td class="num">{{ $cartoRisques['info'] ?? 0 }}</td><td class="small">Suivi de routine</td></tr>
        </tbody>
    </table>

    @if($alertesCritiques->isNotEmpty())
        <h2>6.1 Top alertes critiques ouvertes</h2>
        <table class="data">
            <thead><tr><th style="width:15%">Catégorie</th><th>Titre</th><th>Message</th><th style="width:12%">Émise le</th></tr></thead>
            <tbody>
                @foreach($alertesCritiques as $alerte)
                    <tr>
                        <td><span class="badge danger">{{ $alerte->categorie }}</span></td>
                        <td class="strong">{{ $alerte->titre }}</td>
                        <td class="small">{{ \Illuminate\Support\Str::limit($alerte->message, 90) }}</td>
                        <td class="small">{{ $alerte->created_at->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ============================================================== --}}
    {{-- VII. AUDIT INTERNE & VALIDATIONS                                --}}
    {{-- ============================================================== --}}
    <h1>VII. Suivi audit interne</h1>
    <div class="stat-row">
        <div class="item"><div class="l">Missions en cours</div><div class="v">{{ $audit['missions_en_cours'] }}</div></div>
        <div class="item"><div class="l">Clôturées (30 j)</div><div class="v text-success">{{ $audit['missions_cloturees_30j'] }}</div></div>
        <div class="item"><div class="l">Reco. ouvertes</div><div class="v">{{ $audit['recommandations_ouvertes'] }}</div></div>
        <div class="item"><div class="l">Reco. en retard</div><div class="v text-danger">{{ $audit['recommandations_en_retard'] }}</div></div>
    </div>

    @if($validations['total'] > 0)
        <h2>7.1 Validations en attente</h2>
        <div class="callout warning">
            <div class="titre">{{ $validations['total'] }} validation(s) en attente d'arbitrage</div>
            @if(!empty($validations['recentes']))
                <ul style="margin:6px 0 0 20px;">
                    @foreach($validations['recentes'] as $v)
                        <li class="small">{{ $v['type'] }} — étape <strong>{{ $v['etape'] }}</strong> (en attente depuis {{ $v['depuis_jours'] }} j)</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    {{-- ============================================================== --}}
    {{-- VIII. DÉCISIONS PRÉSIDENTIELLES REQUISES                        --}}
    {{-- ============================================================== --}}
    @if(!empty($decisions))
        <h1>VIII. Décisions présidentielles requises</h1>
        <table class="data">
            <thead><tr><th style="width:12%">Urgence</th><th>Décision</th><th>Justification</th></tr></thead>
            <tbody>
                @foreach($decisions as $d)
                    <tr>
                        <td><span class="badge {{ $d['urgence'] === 'critique' ? 'danger' : ($d['urgence'] === 'haute' ? 'warning' : 'info') }}">{{ $d['urgence'] }}</span></td>
                        <td class="strong">{{ $d['titre'] }}</td>
                        <td class="small">{{ $d['detail'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ============================================================== --}}
    {{-- IX. RECOMMANDATIONS STRATÉGIQUES                                --}}
    {{-- ============================================================== --}}
    <h1>IX. Recommandations stratégiques</h1>
    <ul>
        @foreach($recos as $r)
            <li>{{ $r }}</li>
        @endforeach
    </ul>

    {{-- ============================================================== --}}
    {{-- SIGNATURES                                                       --}}
    {{-- ============================================================== --}}
    <div class="signatures">
        <div class="sig">
            <div class="role">Président de la Commission</div>
            <div class="nom">_______________________</div>
        </div>
        <div class="sig">
            <div class="role">Secrétaire Général</div>
            <div class="nom">_______________________</div>
        </div>
        <div class="sig">
            <div class="role">Date</div>
            <div class="nom">{{ $genere_le->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="callout mt-3">
        <div class="titre">Diffusion restreinte</div>
        Ce document de synthèse exécutive est destiné exclusivement à la Présidence et à la Vice-Présidence de la Commission de la CEEAC, ainsi qu'au Secrétariat Général.
        Toute diffusion en dehors de ce cercle restreint nécessite l'accord exprès du Cabinet.
    </div>
@endsection
