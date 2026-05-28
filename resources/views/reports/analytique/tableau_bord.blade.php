@extends('reports.layouts.institutional')

@section('content')
    @php
        $papa = $donnees['papa'];
        $stats = $donnees['stats'];
        $execution = $donnees['execution'];
        $budget = $donnees['budget'];
        $cycleIpsas = $donnees['cycleIpsas'];
        $sources = $donnees['sourceFinancement'];
        $tendance = $donnees['tendance'];
        $topAxes = $donnees['topAxes'];
        $axesRetard = $donnees['axesEnRetard'];
        $departements = $donnees['departements'];
        $alertes = $donnees['alertes'];
        $indCmr = $donnees['indicateursTypologie'];
        $audit = $donnees['audit'];
        $imports = $donnees['imports'];
        $verdict = $donnees['verdict'];
        $recos = $donnees['recommandations'];

        $fmt = fn ($n, $dec = 0) => number_format((float) $n, $dec, ',', ' ');
        $pct = fn ($n) => number_format((float) $n, 1) . '%';
        $tg = $stats['taux_global'];
        $colorTg = $tg >= 75 ? 'success' : ($tg >= 40 ? 'warning' : 'danger');
    @endphp

    @if(!$papa)
        <div class="callout danger">
            <div class="titre">Aucun PAPA actif</div>
            Veuillez créer ou activer un PAPA pour générer ce tableau de bord.
        </div>
    @else
        <div class="metas">
            <table>
                <tr>
                    <td class="label">PAPA</td>
                    <td><strong>{{ $papa->annee }}</strong> — {{ $papa->libelle }} (v{{ $papa->version }})</td>
                    <td class="label">Statut</td>
                    <td><span class="badge {{ $papa->statut === 'valide' ? 'success' : 'secondary' }}">{{ $papa->statut }}</span></td>
                </tr>
                <tr>
                    <td class="label">Période</td>
                    <td>{{ optional($papa->date_debut)->format('d/m/Y') }} → {{ optional($papa->date_fin)->format('d/m/Y') }}</td>
                    <td class="label">Destinataires</td>
                    <td class="small">Présidence · Vice-Présidence · Secrétariat Général · Commissaires</td>
                </tr>
            </table>
        </div>

        {{-- ============================================================== --}}
        {{-- SYNTHÈSE EXÉCUTIVE (résumé en 1ère page)                        --}}
        {{-- ============================================================== --}}
        <div class="callout {{ $verdict['niveau'] }}">
            <div class="titre">Verdict synthétique — Maturité de pilotage : {{ $verdict['maturite'] }}%</div>
            <strong>{{ $verdict['libelleNiveau'] }}</strong> · Score {{ $verdict['score'] }}/{{ $verdict['max'] }}
            <ul style="margin: 6px 0 0 20px; padding: 0;">
                @foreach($verdict['points'] as $p)
                    <li class="text-{{ $p['level'] }}"><strong>{{ $p['icon'] }}</strong> {{ $p['text'] }}</li>
                @endforeach
            </ul>
        </div>

        {{-- ============================================================== --}}
        {{-- I. INDICATEURS CLÉS DE PERFORMANCE                              --}}
        {{-- ============================================================== --}}
        <h1>I. Indicateurs clés de performance</h1>

        <div class="kpi-grid">
            <div class="kpi" style="border-left-color: {{ $colorTg === 'success' ? '#16a34a' : ($colorTg === 'warning' ? '#f59e0b' : '#dc2626') }}">
                <div class="label">Exécution physique</div>
                <div class="value">{{ $pct($tg) }}</div>
                <div class="sub">Moyenne pondérée des axes</div>
            </div>
            <div class="kpi">
                <div class="label">Exécution financière</div>
                <div class="value">{{ $pct($stats['taux_financier']) }}</div>
                <div class="sub">Consommation / Prévision</div>
            </div>
            <div class="kpi" style="border-left-color: {{ $execution['activites_retard'] > 0 ? '#dc2626' : '#16a34a' }}">
                <div class="label">Activités en retard</div>
                <div class="value">{{ $execution['activites_retard'] }}</div>
                <div class="sub">sur {{ $stats['activites'] }} activités</div>
            </div>
            <div class="kpi" style="border-left-color: {{ $alertes['critiques'] > 0 ? '#dc2626' : '#16a34a' }}">
                <div class="label">Alertes critiques</div>
                <div class="value">{{ $alertes['critiques'] }}</div>
                <div class="sub">{{ $alertes['ouvertes'] }} ouvertes au total</div>
            </div>
        </div>

        <h2>1.1 Réalisation des activités</h2>
        <div class="stat-row">
            <div class="item">
                <div class="l">Réalisées</div>
                <div class="v text-success">{{ $execution['activites_realisees'] }}</div>
            </div>
            <div class="item">
                <div class="l">En cours</div>
                <div class="v text-primary">{{ $execution['activites_en_cours'] }}</div>
            </div>
            <div class="item">
                <div class="l">En retard</div>
                <div class="v text-danger">{{ $execution['activites_retard'] }}</div>
            </div>
            <div class="item">
                <div class="l">Tâches réalisées</div>
                <div class="v">{{ $execution['taches_realisees'] }} / {{ $execution['taches_total'] }}</div>
            </div>
            <div class="item">
                <div class="l">Taux tâches</div>
                <div class="v">{{ $pct($execution['taux_taches']) }}</div>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- II. VOLUMÉTRIE RBM/GAR                                          --}}
        {{-- ============================================================== --}}
        <h1>II. Cadre de résultats RBM/GAR</h1>

        <table class="data">
            <thead>
                <tr>
                    <th>Niveau de la chaîne RBM/GAR</th>
                    <th class="right">Volume</th>
                    <th>Référentiel</th>
                </tr>
            </thead>
            <tbody>
                <tr><td class="strong">Axes stratégiques</td><td class="num">{{ $stats['axes'] }}</td><td class="small">Politiques sectorielles validées par les Commissaires</td></tr>
                <tr><td class="strong">Produits</td><td class="num">{{ $stats['produits'] }}</td><td class="small">Livrables institutionnels rattachés aux axes</td></tr>
                <tr><td class="strong">Sous-Produits</td><td class="num">{{ $stats['sous_produits'] }}</td><td class="small">Décompositions opérationnelles des produits</td></tr>
                <tr><td class="strong">Activités</td><td class="num">{{ $stats['activites'] }}</td><td class="small">Actions planifiées (suivi Gantt)</td></tr>
                <tr><td class="strong">Tâches</td><td class="num">{{ $stats['taches'] }}</td><td class="small">Exécution opérationnelle granulaire</td></tr>
                <tr><td class="strong">Indicateurs (KPI)</td><td class="num">{{ $stats['indicateurs'] }}</td><td class="small">Mesure de la performance — typologie OCDE</td></tr>
            </tbody>
        </table>

        @if(!empty($indCmr))
            <h2>2.1 Indicateurs par typologie OCDE/CAD</h2>
            <table class="data">
                <thead>
                    <tr><th>Catégorie</th><th class="right">Nombre</th><th>Taux moyen de réalisation</th></tr>
                </thead>
                <tbody>
                    @foreach($indCmr as $c)
                        <tr>
                            <td class="strong">{{ ucfirst($c['categorie']) }}</td>
                            <td class="num">{{ $c['nb'] }}</td>
                            <td>
                                <div class="progress {{ $c['taux_moyen'] >= 75 ? 'success' : ($c['taux_moyen'] >= 40 ? 'warning' : 'danger') }}">
                                    <div class="fill" style="width: {{ min(100, $c['taux_moyen']) }}%"></div>
                                </div>
                                <div class="small">{{ $pct($c['taux_moyen']) }}</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="page-break"></div>

        {{-- ============================================================== --}}
        {{-- III. EXÉCUTION BUDGÉTAIRE — CYCLE IPSAS                         --}}
        {{-- ============================================================== --}}
        @if($budget)
            <h1>III. Exécution budgétaire — Conformité IPSAS</h1>

            <div class="metas">
                <table>
                    <tr>
                        <td class="label">Exercice</td>
                        <td><strong>{{ $budget['exercice_annee'] }}</strong> ({{ $budget['exercice_statut'] }})</td>
                        <td class="label">Devise</td>
                        <td><strong>{{ $budget['devise'] }}</strong></td>
                    </tr>
                </table>
            </div>

            <table class="data">
                <thead>
                    <tr>
                        <th>Rubrique</th>
                        <th class="right">Montant ({{ $budget['devise'] }})</th>
                        <th>Part / Taux</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><th class="strong" colspan="3" style="background:#eff6ff; color:#1e5cb3;">Prévisions budgétaires</th></tr>
                    <tr><td><strong>Budget total prévisionnel</strong></td><td class="num strong">{{ $fmt($budget['total']) }}</td><td>—</td></tr>
                    <tr><td>Part CEEAC-EM (États Membres)</td><td class="num">{{ $fmt($budget['ceeac_em']) }}</td><td>{{ $pct($budget['taux_ceeac']) }}</td></tr>
                    <tr><td>Part PTF (Partenaires Tech. & Financiers)</td><td class="num">{{ $fmt($budget['ptf']) }}</td><td>{{ $pct($budget['taux_ptf']) }}</td></tr>
                    @if($budget['ann_prec'] > 0)
                    <tr><td class="small">Budget exercice N-1</td><td class="num small">{{ $fmt($budget['ann_prec']) }}</td><td class="small">Variation {{ $fmt($budget['variation_n_n1'], 1) }}%</td></tr>
                    @endif

                    <tr><th class="strong" colspan="3" style="background:#eff6ff; color:#1e5cb3;">Cycle IPSAS — Exécution</th></tr>
                    <tr><td>Engagé</td><td class="num">{{ $fmt($budget['engage']) }}</td><td>{{ $pct($budget['taux_engagement']) }}</td></tr>
                    <tr><td>Liquidé</td><td class="num">{{ $fmt($budget['liquide']) }}</td><td>—</td></tr>
                    <tr><td>Ordonnancé</td><td class="num">{{ $fmt($budget['ordonnance']) }}</td><td>—</td></tr>
                    <tr><td><strong>Payé</strong></td><td class="num strong">{{ $fmt($budget['paye']) }}</td><td class="strong">{{ $pct($budget['taux_paiement']) }}</td></tr>
                    <tr><td>Disponible (non engagé)</td><td class="num">{{ $fmt($budget['disponible']) }}</td><td>—</td></tr>
                </tbody>
            </table>

            @if(!empty($cycleIpsas))
                <h2>3.1 Volumétrie des mouvements IPSAS</h2>
                <table class="data">
                    <thead><tr><th>Type de mouvement</th><th class="right">Nombre</th><th class="right">Montant cumulé ({{ $budget['devise'] }})</th></tr></thead>
                    <tbody>
                        @foreach($cycleIpsas as $m)
                            <tr><td class="strong">{{ ucfirst($m['type']) }}</td><td class="num">{{ $m['nombre'] }}</td><td class="num">{{ $fmt($m['montant']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if(!empty($sources))
                <h2>3.2 Répartition par source de financement</h2>
                <table class="data">
                    <thead><tr><th>Source</th><th>Libellé</th><th>Type</th><th class="right">Montant</th></tr></thead>
                    <tbody>
                        @foreach($sources as $s)
                            <tr>
                                <td class="strong">{{ $s['code'] }}</td>
                                <td>{{ $s['libelle'] }}</td>
                                <td><span class="badge {{ $s['type'] === 'interne' ? 'info' : 'secondary' }}">{{ $s['type'] }}</span></td>
                                <td class="num">{{ $fmt($s['montant']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <div class="page-break"></div>
        @endif

        {{-- ============================================================== --}}
        {{-- IV. PERFORMANCE PAR DÉPARTEMENT                                 --}}
        {{-- ============================================================== --}}
        <h1>IV. Performance par département</h1>
        @if(empty($departements))
            <p class="text-muted small">Aucun département rattaché à des axes.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:8%">Code</th>
                        <th>Département</th>
                        <th style="width:22%">Commissaire</th>
                        <th style="width:8%" class="right">Axes</th>
                        <th style="width:25%">Avancement</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($departements as $d)
                        <tr>
                            <td class="strong">{{ $d['code'] }}</td>
                            <td>{{ $d['libelle'] }}</td>
                            <td class="small">{{ $d['commissaire'] ?? '—' }}</td>
                            <td class="num">{{ $d['axes_count'] }}</td>
                            <td>
                                <div class="progress {{ $d['taux'] >= 75 ? 'success' : ($d['taux'] >= 40 ? 'warning' : 'danger') }}">
                                    <div class="fill" style="width: {{ min(100, $d['taux']) }}%"></div>
                                </div>
                                <div class="small">{{ $pct($d['taux']) }}</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- ============================================================== --}}
        {{-- V. TOP AXES + AXES EN RETARD                                    --}}
        {{-- ============================================================== --}}
        @if(!empty($topAxes))
            <h2>5.1 Top 5 axes les plus performants</h2>
            <table class="data">
                <thead><tr><th style="width:12%">Code</th><th>Libellé</th><th style="width:12%">Dép.</th><th style="width:25%">Avancement</th></tr></thead>
                <tbody>
                    @foreach($topAxes as $a)
                        <tr>
                            <td class="strong">{{ $a['code'] }}</td>
                            <td>{{ $a['libelle'] }}</td>
                            <td class="small">{{ $a['departement'] }}</td>
                            <td>
                                <div class="progress success"><div class="fill" style="width: {{ min(100, $a['taux']) }}%"></div></div>
                                <div class="small">{{ $pct($a['taux']) }}</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if(!empty($axesRetard))
            <h2>5.2 Axes nécessitant une intervention (< 40 %)</h2>
            <table class="data">
                <thead><tr><th style="width:12%">Code</th><th>Libellé</th><th style="width:12%">Dép.</th><th style="width:25%">Avancement</th></tr></thead>
                <tbody>
                    @foreach($axesRetard as $a)
                        <tr>
                            <td class="strong">{{ $a['code'] }}</td>
                            <td>{{ $a['libelle'] }}</td>
                            <td class="small">{{ $a['departement'] }}</td>
                            <td>
                                <div class="progress danger"><div class="fill" style="width: {{ min(100, $a['taux']) }}%"></div></div>
                                <div class="small">{{ $pct($a['taux']) }}</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- ============================================================== --}}
        {{-- VI. ALERTES + AUDIT INTERNE                                     --}}
        {{-- ============================================================== --}}
        <h1>VI. Alertes & audit interne</h1>

        <div class="stat-row">
            <div class="item"><div class="l">Alertes critiques</div><div class="v text-danger">{{ $alertes['critiques'] }}</div></div>
            <div class="item"><div class="l">Alertes attention</div><div class="v text-warning">{{ $alertes['attention'] }}</div></div>
            <div class="item"><div class="l">Résolues 30 j</div><div class="v text-success">{{ $alertes['resolues_30j'] }}</div></div>
            <div class="item"><div class="l">Missions audit en cours</div><div class="v">{{ $audit['missions_en_cours'] }}</div></div>
            <div class="item"><div class="l">Reco. audit en retard</div><div class="v text-danger">{{ $audit['recommandations_en_retard'] }}</div></div>
        </div>

        @if(!empty($alertes['top_critiques']))
            <h2>6.1 Top alertes critiques ouvertes</h2>
            <table class="data">
                <thead><tr><th>Catégorie</th><th>Titre</th><th class="right">Ouverte depuis</th></tr></thead>
                <tbody>
                    @foreach($alertes['top_critiques'] as $a)
                        <tr>
                            <td><span class="badge danger">{{ $a['categorie'] }}</span></td>
                            <td class="strong">{{ $a['titre'] }}</td>
                            <td class="num small">{{ $a['depuis_jours'] }} j</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($imports['total'] > 0)
            <h2>6.2 Qualité des imports budgétaires</h2>
            <div class="stat-row">
                <div class="item"><div class="l">Total imports</div><div class="v">{{ $imports['total'] }}</div></div>
                <div class="item"><div class="l">Réussis</div><div class="v text-success">{{ $imports['reussis'] }}</div></div>
                <div class="item"><div class="l">En échec</div><div class="v text-danger">{{ $imports['echec'] }}</div></div>
                <div class="item"><div class="l">Erreurs cumulées</div><div class="v">{{ $imports['erreurs_total'] }}</div></div>
            </div>
        @endif

        {{-- ============================================================== --}}
        {{-- VII. RECOMMANDATIONS                                            --}}
        {{-- ============================================================== --}}
        <h1>VII. Recommandations prioritaires</h1>
        <table class="data">
            <thead><tr><th style="width:10%">Priorité</th><th>Recommandation</th><th style="width:25%">Responsable</th><th style="width:12%">Échéance</th></tr></thead>
            <tbody>
                @foreach($recos as $r)
                    <tr>
                        <td><span class="badge {{ $r['priorite'] === 'critique' ? 'danger' : ($r['priorite'] === 'haute' ? 'warning' : 'info') }}">{{ $r['priorite'] }}</span></td>
                        <td>
                            <strong>{{ $r['titre'] }}</strong>
                            <div class="small text-muted">{{ $r['detail'] }}</div>
                        </td>
                        <td class="small">{{ $r['responsable'] }}</td>
                        <td class="small">{{ $r['echeance'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- ============================================================== --}}
        {{-- ANNEXES                                                         --}}
        {{-- ============================================================== --}}
        <div class="page-break"></div>

        <h1>Annexe — Méthodologie et conformité</h1>
        <table class="data">
            <tr><th style="width:25%">Référentiel</th><th>Application dans ce rapport</th></tr>
            <tr><td class="strong">RBM/GAR</td><td>Chaîne officielle CEEAC : Axe → Produit → Sous-Produit → Activité → Tâche</td></tr>
            <tr><td class="strong">OCDE/CAD</td><td>Typologie des indicateurs : intrant, produit, effet, impact</td></tr>
            <tr><td class="strong">IPSAS 1 & 24</td><td>Cycle budgétaire : engagement → liquidation → ordonnancement → paiement</td></tr>
            <tr><td class="strong">COSO ERM 2017</td><td>Cartographie des risques, séparation ordonnateur/comptable</td></tr>
            <tr><td class="strong">IIA / IFACI</td><td>Suivi des missions d'audit interne et recommandations</td></tr>
            <tr><td class="strong">ISO 27001 / RGPD</td><td>Traçabilité, hash SHA-256, code de vérification cryptographique</td></tr>
        </table>

        <h2>Mode de calcul des KPI</h2>
        <ul class="small">
            <li><strong>Taux d'exécution physique</strong> : moyenne pondérée des taux d'exécution des axes par leur poids.</li>
            <li><strong>Taux d'exécution financière</strong> : somme des consommations / somme des prévisions sur l'exercice.</li>
            <li><strong>Activités en retard</strong> : <code>date_fin &lt; aujourd'hui</code> ET statut ∉ {realisée, annulée} ET <code>taux_execution &lt; 100 %</code>.</li>
            <li><strong>Maturité de pilotage</strong> : score composite normalisé sur 5 critères (exécution, alertes, retards, cohérence physique/financière, suivi audit).</li>
        </ul>

        <div class="callout">
            <div class="titre">Confidentialité & diffusion</div>
            Document à usage interne institutionnel. Toute reproduction ou diffusion externe doit faire l'objet d'une autorisation expresse du Cabinet de la Présidence ou du Secrétariat Général. La traçabilité de l'émission est assurée par le code de vérification présent en bas du document.
        </div>

        <p class="small text-muted mt-3">
            Snapshot du système au {{ $genere_le->format('d/m/Y H:i') }}. Les données évoluent en temps réel dans l'application source TB-PAPA-CEEAC.
        </p>
    @endif
@endsection
