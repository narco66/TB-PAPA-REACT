@extends('reports.layouts.institutional')

@section('content')
    @php
        $exercice = $donnees['exercice'];
        $totaux = $donnees['totaux'];
        $depensesParType = $donnees['depensesParType'];
        $tauxConsoFin = $totaux['depenses'] > 0 ? ($totaux['paye'] / $totaux['depenses']) * 100 : 0;
        $tauxEngagement = $totaux['depenses'] > 0 ? ($totaux['engage'] / $totaux['depenses']) * 100 : 0;
        $libellesTypes = [
            'fonctionnement' => 'Dépenses de fonctionnement',
            'investissement' => 'Dépenses d\'investissement',
            'equipement' => 'Dépenses d\'équipement',
            'dette' => 'Charges de la dette',
            'dotation' => 'Dotations institutionnelles',
            'transfert' => 'Transferts',
            'autre' => 'Autres',
        ];
    @endphp

    <div class="metas">
        <table>
            <tr>
                <td class="label">Exercice</td>
                <td><strong>{{ $exercice->annee }}</strong> — {{ $exercice->libelle }}</td>
                <td class="label">Statut</td>
                <td><span class="badge {{ $exercice->statut === 'valide' ? 'success' : 'secondary' }}">{{ $exercice->statut }}</span></td>
                <td class="label">Devise</td>
                <td><strong>{{ $exercice->devise }}</strong></td>
            </tr>
        </table>
    </div>

    <h1>I. Synthèse budgétaire</h1>

    <div class="kpi-grid">
        <div class="kpi">
            <div class="label">Recettes totales</div>
            <div class="value">{{ number_format($totaux['recettes'], 0, ',', ' ') }}</div>
            <div class="sub">Internes : {{ number_format($totaux['recettes_internes'], 0, ',', ' ') }}</div>
        </div>
        <div class="kpi">
            <div class="label">Dépenses totales</div>
            <div class="value">{{ number_format($totaux['depenses'], 0, ',', ' ') }}</div>
            <div class="sub">CEEAC-EM + PTF</div>
        </div>
        <div class="kpi">
            <div class="label">Part CEEAC-EM</div>
            <div class="value">{{ number_format($totaux['depenses_ceeac_em'], 0, ',', ' ') }}</div>
            <div class="sub">{{ $totaux['depenses'] > 0 ? round($totaux['depenses_ceeac_em'] / $totaux['depenses'] * 100, 1) : 0 }}% du budget</div>
        </div>
        <div class="kpi">
            <div class="label">Part PTF</div>
            <div class="value">{{ number_format($totaux['depenses_ptf'], 0, ',', ' ') }}</div>
            <div class="sub">{{ $totaux['depenses'] > 0 ? round($totaux['depenses_ptf'] / $totaux['depenses'] * 100, 1) : 0 }}% du budget</div>
        </div>
    </div>

    <h1>II. Recettes budgétaires</h1>
    <table class="data">
        <thead>
            <tr>
                <th style="width:14%">Catégorie</th>
                <th class="right" style="width:18%">Montant</th>
                <th class="right" style="width:18%">% du total</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="strong">Recettes internes</td>
                <td class="num">{{ number_format($totaux['recettes_internes'], 0, ',', ' ') }}</td>
                <td class="num">{{ $totaux['recettes'] > 0 ? number_format($totaux['recettes_internes'] / $totaux['recettes'] * 100, 1) : 0 }}%</td>
                <td>Contributions des États membres (11 pays)</td>
            </tr>
            <tr>
                <td class="strong">Recettes externes (PTF)</td>
                <td class="num">{{ number_format($totaux['recettes_externes'], 0, ',', ' ') }}</td>
                <td class="num">{{ $totaux['recettes'] > 0 ? number_format($totaux['recettes_externes'] / $totaux['recettes'] * 100, 1) : 0 }}%</td>
                <td>Dons institutions internationales (UE, BAD, BM, AUDA-NEPAD, etc.)</td>
            </tr>
            <tr style="background: #1e5cb3; color: white;">
                <td class="strong">TOTAL RECETTES</td>
                <td class="num strong">{{ number_format($totaux['recettes'], 0, ',', ' ') }}</td>
                <td class="num strong">100%</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <h1>III. Dépenses par type</h1>
    <table class="data">
        <thead>
            <tr>
                <th>Type de dépense</th>
                <th class="right" style="width:14%">Total</th>
                <th class="right" style="width:14%">CEEAC-EM</th>
                <th class="right" style="width:14%">PTF</th>
                <th class="right" style="width:10%">Engagé</th>
                <th class="right" style="width:10%">Payé</th>
                <th class="right" style="width:14%">% Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($depensesParType as $type => $lignes)
                @php
                    $totalType = $lignes->sum('montant_total');
                    $totalEm = $lignes->sum('montant_ceeac_em');
                    $totalPtf = $lignes->sum('montant_ptf');
                    $totalEng = $lignes->sum('montant_engage');
                    $totalPaye = $lignes->sum('montant_paye');
                @endphp
                <tr>
                    <td class="strong">{{ $libellesTypes[$type] ?? $type }}</td>
                    <td class="num">{{ number_format($totalType, 0, ',', ' ') }}</td>
                    <td class="num">{{ number_format($totalEm, 0, ',', ' ') }}</td>
                    <td class="num">{{ number_format($totalPtf, 0, ',', ' ') }}</td>
                    <td class="num">{{ number_format($totalEng, 0, ',', ' ') }}</td>
                    <td class="num">{{ number_format($totalPaye, 0, ',', ' ') }}</td>
                    <td class="num">{{ $totaux['depenses'] > 0 ? number_format($totalType / $totaux['depenses'] * 100, 1) : 0 }}%</td>
                </tr>
            @endforeach
            <tr style="background: #1e5cb3; color: white;">
                <td class="strong">TOTAL DÉPENSES</td>
                <td class="num strong">{{ number_format($totaux['depenses'], 0, ',', ' ') }}</td>
                <td class="num strong">{{ number_format($totaux['depenses_ceeac_em'], 0, ',', ' ') }}</td>
                <td class="num strong">{{ number_format($totaux['depenses_ptf'], 0, ',', ' ') }}</td>
                <td class="num strong">{{ number_format($totaux['engage'], 0, ',', ' ') }}</td>
                <td class="num strong">{{ number_format($totaux['paye'], 0, ',', ' ') }}</td>
                <td class="num strong">100%</td>
            </tr>
        </tbody>
    </table>

    <h1>IV. Exécution budgétaire</h1>

    <table class="data">
        <thead>
            <tr>
                <th>Indicateur</th>
                <th class="right" style="width:20%">Valeur</th>
                <th style="width:35%">Progression</th>
                <th>Appréciation</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="strong">Taux d'engagement</td>
                <td class="num">{{ number_format($tauxEngagement, 2) }}%</td>
                <td>
                    <div class="progress {{ $tauxEngagement >= 75 ? 'success' : ($tauxEngagement >= 40 ? 'warning' : 'danger') }}">
                        <div class="fill" style="width: {{ min(100, $tauxEngagement) }}%"></div>
                    </div>
                </td>
                <td class="small">
                    @if($tauxEngagement >= 75) <span class="badge success">Satisfaisant</span>
                    @elseif($tauxEngagement >= 40) <span class="badge warning">À surveiller</span>
                    @else <span class="badge danger">Sous-engagement</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="strong">Taux de consommation (payé)</td>
                <td class="num">{{ number_format($tauxConsoFin, 2) }}%</td>
                <td>
                    <div class="progress {{ $tauxConsoFin >= 75 ? 'success' : ($tauxConsoFin >= 40 ? 'warning' : 'danger') }}">
                        <div class="fill" style="width: {{ min(100, $tauxConsoFin) }}%"></div>
                    </div>
                </td>
                <td class="small">
                    @if($tauxConsoFin >= 75) <span class="badge success">Forte exécution</span>
                    @elseif($tauxConsoFin >= 40) <span class="badge warning">Exécution modérée</span>
                    @else <span class="badge danger">Sous-consommation</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="strong">Disponible (prévu - engagé)</td>
                <td class="num">{{ number_format($totaux['depenses'] - $totaux['engage'], 0, ',', ' ') }} {{ $exercice->devise }}</td>
                <td colspan="2" class="small text-muted">Reste à engager au {{ now()->format('d/m/Y') }}</td>
            </tr>
        </tbody>
    </table>

    @if($totaux['budget_n_1'] > 0)
        <h1>V. Comparatif inter-annuel</h1>
        <table class="data">
            <thead>
                <tr>
                    <th>Période</th>
                    <th class="right">Budget</th>
                    <th class="right">Réalisations</th>
                    <th class="right">Taux d'exécution</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="strong">Exercice {{ $exercice->annee - 1 }}</td>
                    <td class="num">{{ number_format($totaux['budget_n_1'], 0, ',', ' ') }}</td>
                    <td class="num">{{ number_format($totaux['realisation_n_1'], 0, ',', ' ') }}</td>
                    <td class="num">{{ $totaux['budget_n_1'] > 0 ? number_format($totaux['realisation_n_1'] / $totaux['budget_n_1'] * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td class="strong">Exercice {{ $exercice->annee }}</td>
                    <td class="num">{{ number_format($totaux['depenses'], 0, ',', ' ') }}</td>
                    <td class="num">{{ number_format($totaux['paye'], 0, ',', ' ') }}</td>
                    <td class="num">{{ number_format($tauxConsoFin, 1) }}%</td>
                </tr>
                <tr>
                    <td class="strong">Variation Budget N / N-1</td>
                    <td class="num">
                        @if($totaux['budget_n_1'] > 0)
                            {{ number_format(($totaux['depenses'] - $totaux['budget_n_1']) / $totaux['budget_n_1'] * 100, 1) }}%
                        @else — @endif
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="mt-3 small text-muted">
        Document à valeur institutionnelle, conforme aux normes IPSAS de comptabilité publique.
        Les montants sont arrêtés à la date d'émission.
    </div>
@endsection
