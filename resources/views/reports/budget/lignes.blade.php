@extends('reports.layouts.institutional')

@section('content')
    @php
        $lignes = $donnees['lignes'];
        $totaux = $donnees['totaux'];
        $exercice = $donnees['exercice'];
        $axe = $donnees['axe'];
        $source = $donnees['source'];
        $filtres = $donnees['filtresAppliques'] ?? [];
        $libellesType = [
            'recette_interne' => 'Recettes internes',
            'recette_externe' => 'Recettes externes',
            'fonctionnement' => 'Fonctionnement',
            'investissement' => 'Investissement',
            'equipement' => 'Équipement',
            'dotation' => 'Dotation',
            'dette' => 'Dette',
            'transfert' => 'Transfert',
            'autre' => 'Autre',
        ];
    @endphp

    <div class="metas">
        <table>
            <tr>
                <td class="label">Exercice</td>
                <td><strong>{{ $exercice ? $exercice->annee . ' — ' . $exercice->libelle : 'Tous exercices' }}</strong></td>
                <td class="label">Lignes filtrées</td>
                <td><strong>{{ $totaux['count'] }}</strong> ligne(s)</td>
            </tr>
            <tr>
                <td class="label">Filtres appliqués</td>
                <td colspan="3" class="small">
                    @php
                        $resumeFiltres = [];
                        if (! empty($filtres['q'])) $resumeFiltres[] = 'Recherche : « ' . $filtres['q'] . ' »';
                        if (! empty($filtres['nature'])) $resumeFiltres[] = 'Nature : ' . $filtres['nature'];
                        if (! empty($filtres['type_budget'])) $resumeFiltres[] = 'Type : ' . ($libellesType[$filtres['type_budget']] ?? $filtres['type_budget']);
                        if (! empty($filtres['pilier'])) $resumeFiltres[] = 'Pilier : ' . $filtres['pilier'];
                        if ($axe) $resumeFiltres[] = 'Axe : ' . $axe->code;
                        if ($source) $resumeFiltres[] = 'Source : ' . $source->code;
                    @endphp
                    {{ ! empty($resumeFiltres) ? implode(' · ', $resumeFiltres) : 'Aucun filtre — extraction complète' }}
                </td>
            </tr>
        </table>
    </div>

    <h1>I. Synthèse</h1>

    <div class="kpi-grid">
        <div class="kpi">
            <div class="label">Lignes</div>
            <div class="value">{{ $totaux['count'] }}</div>
        </div>
        <div class="kpi">
            <div class="label">Montant total</div>
            <div class="value">{{ number_format($totaux['total'], 0, ',', ' ') }}</div>
        </div>
        <div class="kpi">
            <div class="label">CEEAC-EM</div>
            <div class="value">{{ number_format($totaux['ceeac_em'], 0, ',', ' ') }}</div>
            <div class="sub">{{ $totaux['total'] > 0 ? round($totaux['ceeac_em']/$totaux['total']*100, 1) : 0 }}% du total</div>
        </div>
        <div class="kpi">
            <div class="label">PTF</div>
            <div class="value">{{ number_format($totaux['ptf'], 0, ',', ' ') }}</div>
            <div class="sub">{{ $totaux['total'] > 0 ? round($totaux['ptf']/$totaux['total']*100, 1) : 0 }}% du total</div>
        </div>
    </div>

    <table class="data mt-3">
        <tr>
            <th style="width:30%">Engagement</th>
            <td class="num">{{ number_format($totaux['engage'], 0, ',', ' ') }}</td>
            <th style="width:20%">% du budget</th>
            <td class="num">{{ $totaux['total'] > 0 ? number_format($totaux['engage']/$totaux['total']*100, 2) : 0 }}%</td>
        </tr>
        <tr>
            <th>Paiements</th>
            <td class="num">{{ number_format($totaux['paye'], 0, ',', ' ') }}</td>
            <th>% du budget</th>
            <td class="num">{{ $totaux['total'] > 0 ? number_format($totaux['paye']/$totaux['total']*100, 2) : 0 }}%</td>
        </tr>
        <tr>
            <th>Disponible</th>
            <td class="num">{{ number_format($totaux['total'] - $totaux['engage'], 0, ',', ' ') }}</td>
            <th>Reste à engager</th>
            <td class="num">{{ $totaux['total'] > 0 ? number_format(($totaux['total'] - $totaux['engage'])/$totaux['total']*100, 2) : 0 }}%</td>
        </tr>
    </table>

    <h1>II. Détail des lignes ({{ $totaux['count'] }})</h1>

    @if($lignes->isEmpty())
        <p class="text-muted small">Aucune ligne ne correspond aux filtres sélectionnés.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width:6%">Titre</th>
                    <th style="width:5%">Chap.</th>
                    <th style="width:5%">Art.</th>
                    <th style="width:7%">Code</th>
                    <th>Libellé</th>
                    <th style="width:9%">Type</th>
                    <th style="width:6%">Axe</th>
                    <th style="width:9%" class="right">Total</th>
                    <th style="width:9%" class="right">CEEAC-EM</th>
                    <th style="width:9%" class="right">PTF</th>
                    <th style="width:9%" class="right">Engagé</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignes as $l)
                    <tr>
                        <td class="font-mono small">{{ $l->titre_code }}</td>
                        <td class="font-mono small">{{ $l->chapitre_code }}</td>
                        <td class="font-mono small">{{ $l->article_code }}</td>
                        <td class="font-mono small">{{ $l->code_action }}</td>
                        <td class="small">{{ \Illuminate\Support\Str::limit($l->libelle, 80) }}</td>
                        <td class="small">{{ $libellesType[$l->type_budget] ?? $l->type_budget }}</td>
                        <td class="font-mono small">{{ $l->axe?->code ?? '—' }}</td>
                        <td class="num">{{ number_format((float) $l->montant_total, 0, ',', ' ') }}</td>
                        <td class="num">{{ number_format((float) $l->montant_ceeac_em, 0, ',', ' ') }}</td>
                        <td class="num">{{ number_format((float) $l->montant_ptf, 0, ',', ' ') }}</td>
                        <td class="num">{{ number_format((float) $l->montant_engage, 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
                <tr style="background: #1e5cb3; color: white;">
                    <td colspan="7" class="strong" style="text-align: right;">TOTAL</td>
                    <td class="num strong">{{ number_format($totaux['total'], 0, ',', ' ') }}</td>
                    <td class="num strong">{{ number_format($totaux['ceeac_em'], 0, ',', ' ') }}</td>
                    <td class="num strong">{{ number_format($totaux['ptf'], 0, ',', ' ') }}</td>
                    <td class="num strong">{{ number_format($totaux['engage'], 0, ',', ' ') }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @if($lignes->count() >= 2000)
        <p class="small text-danger mt-2 strong">
            ⚠ Résultat tronqué à 2000 lignes maximum. Affinez les filtres pour obtenir une extraction complète.
        </p>
    @endif

    <div class="mt-3 small text-muted">
        Document à valeur institutionnelle, conforme aux normes IPSAS de comptabilité publique.
        Montants en {{ $exercice?->devise ?? 'XAF' }}. Filtres et extraction horodatés.
    </div>
@endsection
