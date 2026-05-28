@extends('reports.layouts.institutional')

@section('content')
    @php
        $papa = $donnees['papa'];
        $axes = $donnees['axes'];
        $stats = $donnees['stats'];
        $budgetParAxe = $donnees['budgetParAxe'];
        $indParAxe = $donnees['indicateursParAxe'];
        $calendrier = $donnees['calendrier'];
        $gouv = $donnees['gouvernance'];

        $fmt = fn ($n, $dec = 0) => number_format((float) $n, $dec, ',', ' ');
        $pct = fn ($n) => number_format((float) $n, 1) . '%';
        $tg = $stats['taux_execution_global'];
    @endphp

    {{-- ============================================================== --}}
    {{-- PAGE DE GARDE                                                   --}}
    {{-- ============================================================== --}}
    <div class="cover">
        @if(!empty($logo_ceeac))
            <img src="{{ $logo_ceeac }}" alt="Logo CEEAC" width="110" height="110" class="cover-logo">
        @endif

        <div class="cover-titre">Plan d'Action Prioritaire Annuel</div>
        <div class="cover-sous">PAPA {{ $papa->annee }} — Commission de la CEEAC</div>

        <div class="cover-meta">
            <table>
                <tr><td><strong>Référence</strong></td><td>: PAPA-{{ $papa->annee }} (version {{ $papa->version }})</td></tr>
                <tr><td><strong>Période</strong></td><td>: {{ optional($papa->date_debut)->format('d/m/Y') }} → {{ optional($papa->date_fin)->format('d/m/Y') }}</td></tr>
                <tr><td><strong>Statut</strong></td><td>: <span class="badge {{ $papa->statut === 'valide' ? 'success' : 'secondary' }}">{{ strtoupper($papa->statut) }}</span></td></tr>
                <tr><td><strong>Validé le</strong></td><td>: {{ $papa->date_validation ? $papa->date_validation->format('d/m/Y') : '—' }}</td></tr>
                <tr><td><strong>Validé par</strong></td><td>: {{ $gouv['valideur'] ?? '—' }} <span class="small text-muted">({{ $gouv['valideur_fonction'] ?? '—' }})</span></td></tr>
                <tr><td><strong>Émis le</strong></td><td>: {{ $genere_le->format('d/m/Y à H:i') }}</td></tr>
                <tr><td><strong>Code de vérification</strong></td><td>: <span style="font-family:monospace;">{{ $code_verification }}</span></td></tr>
            </table>
        </div>

        <div class="classification normal">Document institutionnel — Diffusion autorisée</div>

        <p class="mt-4 small text-muted" style="max-width:520px;margin:24px auto 0 auto;">
            Le présent document constitue le Plan d'Action Prioritaire Annuel (PAPA) de la Commission de la
            Communauté Économique des États de l'Afrique Centrale pour l'exercice {{ $papa->annee }}.
            Il est élaboré conformément aux principes de la Gestion Axée sur les Résultats (RBM/GAR)
            et aux normes internationales applicables aux organisations régionales africaines.
        </p>
    </div>

    <div class="page-break"></div>

    {{-- ============================================================== --}}
    {{-- AVANT-PROPOS                                                     --}}
    {{-- ============================================================== --}}
    <h1>Avant-propos</h1>

    <p>
        Le Plan d'Action Prioritaire Annuel (PAPA) est l'instrument central de planification opérationnelle de la
        Commission de la CEEAC. Il décline les orientations stratégiques régionales en programmes, projets et activités
        mesurables, conformément au cadre de la Gestion Axée sur les Résultats (GAR/RBM).
    </p>

    @if($papa->description)
        <h2>Contexte institutionnel</h2>
        <p>{{ $papa->description }}</p>
    @endif

    @if($papa->perimetre_institutionnel)
        <h2>Périmètre institutionnel</h2>
        <p>{{ $papa->perimetre_institutionnel }}</p>
    @endif

    {{-- ============================================================== --}}
    {{-- I. SYNTHÈSE EXÉCUTIVE                                           --}}
    {{-- ============================================================== --}}
    <h1>1. Synthèse exécutive</h1>

    <div class="kpi-grid">
        <div class="kpi"><div class="label">Axes stratégiques</div><div class="value">{{ $stats['nb_axes'] }}</div></div>
        <div class="kpi"><div class="label">Produits</div><div class="value">{{ $stats['nb_produits'] }}</div></div>
        <div class="kpi"><div class="label">Sous-Produits</div><div class="value">{{ $stats['nb_sous_produits'] }}</div></div>
        <div class="kpi" style="border-left-color: {{ $tg >= 75 ? '#16a34a' : ($tg >= 40 ? '#f59e0b' : '#dc2626') }}">
            <div class="label">Exécution physique</div>
            <div class="value">{{ $pct($tg) }}</div>
            <div class="sub">Financière {{ $pct($stats['taux_execution_financier']) }}</div>
        </div>
    </div>

    <div class="stat-row">
        <div class="item"><div class="l">Activités</div><div class="v">{{ $stats['nb_activites'] }}</div></div>
        <div class="item"><div class="l">Tâches</div><div class="v">{{ $stats['nb_taches'] }}</div></div>
        <div class="item"><div class="l">Indicateurs CMR</div><div class="v">{{ $stats['nb_indicateurs'] }}</div></div>
    </div>

    {{-- ============================================================== --}}
    {{-- II. CADRE RBM/GAR — Détail par axe                              --}}
    {{-- ============================================================== --}}
    <div class="page-break"></div>
    <h1>2. Cadre de résultats RBM/GAR — Axes stratégiques</h1>

    @forelse($axes as $axe)
        <h2>{{ $axe->code }} — {{ $axe->libelle }}</h2>

        <table class="data">
            <tr>
                <th style="width:20%">Département</th>
                <td>{{ $axe->departement ? $axe->departement->code . ' — ' . $axe->departement->libelle : '—' }}</td>
                <th style="width:15%">Responsable</th>
                <td>{{ $axe->responsable->name ?? '—' }} <span class="small text-muted">{{ $axe->responsable->fonction ?? '' }}</span></td>
            </tr>
            <tr>
                <th>Poids stratégique</th>
                <td class="num">{{ $fmt($axe->poids, 2) }}</td>
                <th>Statut</th>
                <td><span class="badge {{ $axe->statut === 'valide' ? 'success' : 'secondary' }}">{{ $axe->statut }}</span></td>
            </tr>
            <tr>
                <th>Période</th>
                <td>{{ optional($axe->date_debut)->format('d/m/Y') }} → {{ optional($axe->date_fin)->format('d/m/Y') }}</td>
                <th>Taux d'exécution</th>
                <td>
                    <div class="progress {{ $axe->taux_execution >= 75 ? 'success' : ($axe->taux_execution >= 40 ? 'warning' : 'danger') }}">
                        <div class="fill" style="width: {{ min(100, $axe->taux_execution) }}%"></div>
                    </div>
                    <div class="small mt-1">{{ $pct($axe->taux_execution) }}</div>
                </td>
            </tr>
            @if($axe->description)
                <tr><th>Description</th><td colspan="3" class="small">{{ $axe->description }}</td></tr>
            @endif
        </table>

        @if($axe->produits->isNotEmpty())
            <h3>Produits rattachés ({{ $axe->produits->count() }})</h3>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:12%">Code</th>
                        <th>Libellé</th>
                        <th style="width:10%">Statut</th>
                        <th style="width:7%" class="right">Poids</th>
                        <th style="width:18%">Exécution</th>
                        <th style="width:7%" class="right">SP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($axe->produits as $produit)
                        <tr>
                            <td class="strong">{{ $produit->code }}</td>
                            <td>{{ $produit->libelle }}</td>
                            <td><span class="badge {{ $produit->statut === 'valide' ? 'success' : 'secondary' }}">{{ $produit->statut }}</span></td>
                            <td class="num">{{ $fmt($produit->poids, 0) }}</td>
                            <td>
                                <div class="progress {{ $produit->taux_execution >= 75 ? 'success' : ($produit->taux_execution >= 40 ? 'warning' : 'danger') }}">
                                    <div class="fill" style="width: {{ min(100, $produit->taux_execution) }}%"></div>
                                </div>
                                <div class="small">{{ $pct($produit->taux_execution) }}</div>
                            </td>
                            <td class="num">{{ $produit->sousProduits->count() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-muted small">Aucun produit rattaché à cet axe.</p>
        @endif

        <div class="mt-2"></div>
    @empty
        <p class="text-muted">Aucun axe défini pour ce PAPA.</p>
    @endforelse

    {{-- ============================================================== --}}
    {{-- III. INDICATEURS CMR PAR AXE                                    --}}
    {{-- ============================================================== --}}
    @if(!empty($indParAxe))
        <div class="page-break"></div>
        <h1>3. Indicateurs CMR par axe</h1>

        <p class="small text-muted">
            Les indicateurs CMR (Cadre de Mesure des Résultats) sont rattachés au niveau Sous-Produit conformément
            à la typologie OCDE/CAD (intrant, produit, effet, impact). Le taux de réalisation est calculé à partir
            de la valeur observée rapportée à la cible annuelle.
        </p>

        <table class="data">
            <thead>
                <tr>
                    <th style="width:12%">Axe</th>
                    <th>Libellé</th>
                    <th class="right" style="width:8%">Indicateurs</th>
                    <th class="right" style="width:8%">Atteints</th>
                    <th class="right" style="width:8%">À risque</th>
                    <th style="width:22%">Taux moyen</th>
                </tr>
            </thead>
            <tbody>
                @foreach($indParAxe as $i)
                    <tr>
                        <td class="strong">{{ $i['code'] }}</td>
                        <td class="small">{{ $i['libelle'] }}</td>
                        <td class="num">{{ $i['nb'] }}</td>
                        <td class="num text-success">{{ $i['atteints'] }}</td>
                        <td class="num text-danger">{{ $i['a_risque'] }}</td>
                        <td>
                            <div class="progress {{ $i['taux_moyen'] >= 75 ? 'success' : ($i['taux_moyen'] >= 40 ? 'warning' : 'danger') }}">
                                <div class="fill" style="width: {{ min(100, $i['taux_moyen']) }}%"></div>
                            </div>
                            <div class="small">{{ $pct($i['taux_moyen']) }}</div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ============================================================== --}}
    {{-- IV. BUDGÉTISATION PAR AXE (CEEAC vs PTF)                        --}}
    {{-- ============================================================== --}}
    @if(!empty($budgetParAxe))
        <div class="page-break"></div>
        <h1>4. Mobilisation budgétaire par axe</h1>

        <p class="small text-muted">
            Budget institutionnel CEEAC (États Membres) + Partenaires Techniques et Financiers, conformément à la
            structure budgétaire CEEAC (Chapitre → Article → Paragraphe → Ligne) et aux normes IPSAS 1 & 24.
        </p>

        <table class="data">
            <thead>
                <tr>
                    <th style="width:10%">Axe</th>
                    <th>Libellé</th>
                    <th class="right" style="width:6%">Lignes</th>
                    <th class="right" style="width:13%">Total</th>
                    <th class="right" style="width:13%">CEEAC-EM</th>
                    <th class="right" style="width:13%">PTF</th>
                    <th class="right" style="width:8%">Eng.</th>
                </tr>
            </thead>
            <tbody>
                @php $totals = ['t' => 0, 'c' => 0, 'p' => 0, 'e' => 0]; @endphp
                @foreach($budgetParAxe as $b)
                    @php
                        $totals['t'] += $b['total'];
                        $totals['c'] += $b['ceeac'];
                        $totals['p'] += $b['ptf'];
                        $totals['e'] += $b['engage'];
                    @endphp
                    <tr>
                        <td class="strong">{{ $b['code'] }}</td>
                        <td class="small">{{ $b['libelle'] }}</td>
                        <td class="num">{{ $b['nb_lignes'] }}</td>
                        <td class="num">{{ $fmt($b['total']) }}</td>
                        <td class="num small">{{ $fmt($b['ceeac']) }}</td>
                        <td class="num small">{{ $fmt($b['ptf']) }}</td>
                        <td class="num small">{{ $pct($b['taux_engagement']) }}</td>
                    </tr>
                @endforeach
                <tr style="background:#eff6ff;">
                    <td colspan="3" class="strong">TOTAL</td>
                    <td class="num strong">{{ $fmt($totals['t']) }}</td>
                    <td class="num strong">{{ $fmt($totals['c']) }}</td>
                    <td class="num strong">{{ $fmt($totals['p']) }}</td>
                    <td class="num strong">—</td>
                </tr>
            </tbody>
        </table>

        @php
            $tauxCeeac = $totals['t'] > 0 ? round($totals['c'] / $totals['t'] * 100, 1) : 0;
            $tauxPtf = $totals['t'] > 0 ? round($totals['p'] / $totals['t'] * 100, 1) : 0;
        @endphp
        <div class="callout">
            <div class="titre">Structure de financement</div>
            <strong>{{ $pct($tauxCeeac) }}</strong> de contributions des États membres (CEEAC-EM) et
            <strong>{{ $pct($tauxPtf) }}</strong> de partenaires techniques et financiers (PTF) sur l'enveloppe totale du PAPA.
        </div>
    @endif

    {{-- ============================================================== --}}
    {{-- V. CALENDRIER CONSOLIDÉ                                         --}}
    {{-- ============================================================== --}}
    @if(!empty($calendrier['jalons_par_trimestre']))
        <h1>5. Calendrier consolidé</h1>

        <table class="data">
            <tr>
                <th>Période globale du PAPA</th>
                <td>{{ $calendrier['date_debut_min'] ? \Carbon\Carbon::parse($calendrier['date_debut_min'])->format('d/m/Y') : '—' }}
                    →
                    {{ $calendrier['date_fin_max'] ? \Carbon\Carbon::parse($calendrier['date_fin_max'])->format('d/m/Y') : '—' }}</td>
            </tr>
        </table>

        <h2>5.1 Répartition trimestrielle des activités</h2>
        <table class="data">
            <thead><tr><th>Trimestre</th><th class="right">Activités à livrer</th><th class="right">Activités réalisées</th><th>Avancement</th></tr></thead>
            <tbody>
                @foreach($calendrier['jalons_par_trimestre'] as $j)
                    @php $txt = $j['activites'] > 0 ? round($j['realisees'] / $j['activites'] * 100, 1) : 0; @endphp
                    <tr>
                        <td class="strong">{{ $j['trimestre'] }}</td>
                        <td class="num">{{ $j['activites'] }}</td>
                        <td class="num text-success">{{ $j['realisees'] }}</td>
                        <td>
                            <div class="progress {{ $txt >= 75 ? 'success' : ($txt >= 40 ? 'warning' : 'danger') }}">
                                <div class="fill" style="width: {{ min(100, $txt) }}%"></div>
                            </div>
                            <div class="small">{{ $pct($txt) }}</div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ============================================================== --}}
    {{-- VI. GOUVERNANCE                                                  --}}
    {{-- ============================================================== --}}
    <h1>6. Gouvernance du PAPA</h1>
    <table class="data">
        <tr>
            <th style="width:20%">Créé par</th>
            <td>{{ $gouv['createur'] ?? '—' }} <span class="small text-muted">{{ $gouv['createur_fonction'] ?? '' }}</span></td>
            <th style="width:20%">Validé par</th>
            <td>{{ $gouv['valideur'] ?? '—' }} <span class="small text-muted">{{ $gouv['valideur_fonction'] ?? '' }}</span></td>
        </tr>
        <tr>
            <th>Date de validation</th>
            <td>{{ $gouv['date_validation'] ? $gouv['date_validation']->format('d/m/Y H:i') : '—' }}</td>
            <th>Verrouillé</th>
            <td>{{ $gouv['verrouille'] ? 'Oui' : 'Non' }}</td>
        </tr>
        <tr>
            <th>Période d'exécution</th>
            <td colspan="3">
                {{ $gouv['periode']['debut'] ? $gouv['periode']['debut']->format('d/m/Y') : '—' }}
                →
                {{ $gouv['periode']['fin'] ? $gouv['periode']['fin']->format('d/m/Y') : '—' }}
            </td>
        </tr>
    </table>

    <h2>6.1 Organes de pilotage</h2>
    <ul class="small">
        <li><strong>Comité de pilotage stratégique (COPIL)</strong> : Présidence, Vice-Présidence, Commissaires — revue mensuelle des indicateurs stratégiques.</li>
        <li><strong>Comité technique opérationnel (COTECH)</strong> : Directeurs techniques + Points focaux — revue hebdomadaire des activités et tâches.</li>
        <li><strong>Inspection Générale des Services (IGS)</strong> : Audit interne — missions trimestrielles + suivi des recommandations.</li>
        <li><strong>Contrôle financier central</strong> : Validation des engagements et liquidations selon le cycle IPSAS.</li>
    </ul>

    {{-- ============================================================== --}}
    {{-- SIGNATURES                                                       --}}
    {{-- ============================================================== --}}
    <div class="signatures mt-3">
        <div class="sig">
            <div class="role">Secrétaire Général</div>
            <div class="nom">_______________________</div>
        </div>
        <div class="sig">
            <div class="role">Président de la Commission</div>
            <div class="nom">_______________________</div>
        </div>
        <div class="sig">
            <div class="role">Date</div>
            <div class="nom">{{ $genere_le->format('d/m/Y') }}</div>
        </div>
    </div>

    {{-- ============================================================== --}}
    {{-- ANNEXES                                                          --}}
    {{-- ============================================================== --}}
    <div class="page-break"></div>
    <h1>Annexe A — Glossaire RBM/GAR et nomenclature</h1>

    <table class="data">
        <tr><th style="width:20%">Terme</th><th>Définition</th></tr>
        <tr><td class="strong">PAPA</td><td>Plan d'Action Prioritaire Annuel — instrument de planification opérationnelle annuelle de la Commission CEEAC.</td></tr>
        <tr><td class="strong">RBM / GAR</td><td>Results-Based Management / Gestion Axée sur les Résultats — démarche de pilotage par les résultats mesurables.</td></tr>
        <tr><td class="strong">Axe stratégique</td><td>Niveau 1 de la chaîne RBM : orientation politique sectorielle validée par les Commissaires.</td></tr>
        <tr><td class="strong">Produit</td><td>Niveau 2 : livrable institutionnel rattaché à un axe stratégique.</td></tr>
        <tr><td class="strong">Sous-Produit</td><td>Niveau 3 : décomposition opérationnelle d'un produit.</td></tr>
        <tr><td class="strong">Activité</td><td>Niveau 4 : action planifiée avec calendrier, responsable et budget (suivi Gantt).</td></tr>
        <tr><td class="strong">Tâche</td><td>Niveau 5 : exécution opérationnelle granulaire d'une activité.</td></tr>
        <tr><td class="strong">Indicateur CMR</td><td>Cadre de Mesure des Résultats — indicateur quantitatif/qualitatif rattaché au niveau Sous-Produit.</td></tr>
        <tr><td class="strong">CEEAC-EM</td><td>Contributions statutaires des États membres de la CEEAC (financement interne).</td></tr>
        <tr><td class="strong">PTF</td><td>Partenaires Techniques et Financiers (UE, BAD, BM, ONUDI, etc.) — financements externes.</td></tr>
        <tr><td class="strong">Cycle IPSAS</td><td>Engagement → Liquidation → Ordonnancement → Paiement (IPSAS 1 & 24).</td></tr>
    </table>

    <h1>Annexe B — Référentiels de conformité</h1>
    <table class="data">
        <tr><th style="width:25%">Norme / Référentiel</th><th>Domaine couvert</th></tr>
        <tr><td class="strong">RBM/GAR</td><td>Gestion Axée sur les Résultats — chaîne de résultats officielle CEEAC</td></tr>
        <tr><td class="strong">OCDE / CAD</td><td>Typologie des indicateurs (intrant, produit, effet, impact) — paragraphe 1.43 du Manuel CAD</td></tr>
        <tr><td class="strong">IPSAS 1 & 24</td><td>Présentation des états financiers et comptabilité budgétaire publique</td></tr>
        <tr><td class="strong">COSO ERM 2017</td><td>Cartographie des risques et contrôle interne — séparation ordonnateur/comptable</td></tr>
        <tr><td class="strong">IIA / IPPF</td><td>International Professional Practices Framework (Standards 1000, 2010, 2200, 2400, 2500)</td></tr>
        <tr><td class="strong">IFACI</td><td>Cadre de référence de l'audit interne</td></tr>
        <tr><td class="strong">ISO 19011</td><td>Lignes directrices pour l'audit des systèmes de management</td></tr>
        <tr><td class="strong">ISO 27001 / RGPD</td><td>Sécurité de l'information et protection des données personnelles</td></tr>
    </table>

    <p class="small text-muted mt-3">
        Ce document constitue une attestation institutionnelle de l'état stratégique du PAPA {{ $papa->annee }}
        au {{ $genere_le->format('d/m/Y à H:i') }}. Toute modification ultérieure du PAPA ne sera pas reflétée
        dans cette version archivée. La traçabilité complète est assurée par le journal d'audit système
        (référentiel ISO 27001) et le code de vérification cryptographique présent en bas de chaque page.
    </p>
@endsection
