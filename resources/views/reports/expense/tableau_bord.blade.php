@extends('reports.layouts.institutional')

@section('content')
    @php
        $exercice = $donnees['exercice'] ?? null;
        $kpis = $donnees['kpis'] ?? [];
        $cycle = $donnees['cycle_ipsas'] ?? [];
        $expressions = $donnees['expressions'] ?? [];
        $parType = $donnees['par_type_engagement'] ?? [];
        $parDep = $donnees['par_departement'] ?? [];
        $topF = $donnees['top_fournisseurs'] ?? [];
        $enAttente = $donnees['en_attente_validation'] ?? [];

        $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ');
        $pct = fn ($n) => number_format((float) $n, 1) . '%';
    @endphp

    <div class="metas">
        <table>
            <tr>
                <td class="label">Exercice</td>
                <td><strong>{{ $exercice['annee'] ?? '—' }}</strong> — {{ $exercice['libelle'] ?? '' }}</td>
                <td class="label">Statut</td>
                <td><span class="badge {{ ($exercice['statut'] ?? '') === 'valide' ? 'success' : 'secondary' }}">{{ $exercice['statut'] ?? '—' }}</span></td>
            </tr>
            <tr>
                <td class="label">Périmètre</td>
                <td colspan="3">Chaîne RGCP : expression du besoin → engagement → liquidation → ordonnancement → paiement</td>
            </tr>
        </table>
    </div>

    <h1>I. Indicateurs synthétiques</h1>
    <div class="kpi-grid">
        <div class="kpi">
            <div class="label">Expressions du besoin</div>
            <div class="value">{{ $kpis['expressions_total'] ?? 0 }}</div>
            <div class="sub">dont {{ $kpis['expressions_engagees'] ?? 0 }} engagées</div>
        </div>
        <div class="kpi">
            <div class="label">Montant engagé</div>
            <div class="value">{{ $fmt($kpis['montant_engage'] ?? 0) }}</div>
        </div>
        <div class="kpi" style="border-left-color:#16a34a">
            <div class="label">Montant payé</div>
            <div class="value">{{ $fmt($kpis['montant_paye'] ?? 0) }}</div>
            <div class="sub">{{ $pct($kpis['taux_paiement'] ?? 0) }}</div>
        </div>
        <div class="kpi" style="border-left-color:{{ ($kpis['engagements_en_retard'] ?? 0) > 0 ? '#dc2626' : '#16a34a' }}">
            <div class="label">Engagements en retard</div>
            <div class="value">{{ $kpis['engagements_en_retard'] ?? 0 }}</div>
        </div>
    </div>

    <h1>II. Cycle IPSAS</h1>
    <table class="data">
        <thead>
            <tr>
                <th>Étape</th>
                <th class="right">Nombre</th>
                <th class="right">Montant cumulé</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cycle as $type => $v)
                <tr>
                    <td class="strong">{{ ucfirst($type) }}</td>
                    <td class="num">{{ $v['nb'] }}</td>
                    <td class="num">{{ $fmt($v['montant']) }}</td>
                </tr>
            @endforeach
            <tr style="background:#eff6ff;">
                <td class="strong">Reste à payer</td>
                <td>—</td>
                <td class="num strong">{{ $fmt($kpis['reste_a_payer'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    <h1>III. Expressions du besoin par statut</h1>
    <table class="data">
        <thead><tr><th>Statut</th><th class="right">Nombre</th></tr></thead>
        <tbody>
            @foreach($expressions as $s => $n)
                <tr><td>{{ $s }}</td><td class="num">{{ $n }}</td></tr>
            @endforeach
        </tbody>
    </table>

    @if(!empty($parType))
        <div class="page-break"></div>
        <h1>IV. Engagements par type (18 types métiers)</h1>
        <table class="data">
            <thead><tr><th>Type</th><th class="right">Nb</th><th class="right">Montant estimé</th></tr></thead>
            <tbody>
                @foreach($parType as $t)
                    <tr>
                        <td>{{ $t['label'] }}</td>
                        <td class="num">{{ $t['nb'] }}</td>
                        <td class="num">{{ $fmt($t['montant']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(!empty($parDep))
        <h1>V. Performance par département</h1>
        <table class="data">
            <thead><tr><th>Code</th><th>Libellé</th><th class="right">Nb</th><th class="right">Montant</th></tr></thead>
            <tbody>
                @foreach($parDep as $d)
                    <tr>
                        <td class="strong">{{ $d['code'] }}</td>
                        <td>{{ $d['libelle'] }}</td>
                        <td class="num">{{ $d['nb'] }}</td>
                        <td class="num">{{ $fmt($d['montant']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(!empty($topF))
        <h1>VI. Top fournisseurs</h1>
        <table class="data">
            <thead><tr><th>Code</th><th>Libellé</th><th>Type</th><th class="right">Engagements</th><th class="right">Montant</th></tr></thead>
            <tbody>
                @foreach($topF as $f)
                    <tr>
                        <td class="strong">{{ $f['code'] }}</td>
                        <td>{{ $f['libelle'] }}</td>
                        <td class="small">{{ $f['type'] }}</td>
                        <td class="num">{{ $f['nb_engagements'] }}</td>
                        <td class="num">{{ $fmt($f['montant']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if(!empty($enAttente))
        <h1>VII. Validations en attente</h1>
        <table class="data">
            <thead><tr><th>N°</th><th>Objet</th><th>Demandeur</th><th>Dép.</th><th class="right">Montant</th><th class="right">Depuis</th></tr></thead>
            <tbody>
                @foreach($enAttente as $r)
                    <tr>
                        <td class="font-mono small">{{ $r['numero'] }}</td>
                        <td class="small">{{ $r['objet'] }}</td>
                        <td class="small">{{ $r['demandeur'] }}</td>
                        <td class="small">{{ $r['departement'] }}</td>
                        <td class="num small">{{ $fmt($r['montant']) }}</td>
                        <td class="num small">{{ $r['depuis_jours'] }} j</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="callout mt-3 small">
        <div class="titre">Conformité institutionnelle</div>
        Pilotage conforme au Règlement Général de Comptabilité Publique (RGCP), aux normes IPSAS 1 & 24
        (présentation des états financiers, comptabilité budgétaire) et au cadre COSO ERM 2017
        (séparation des fonctions, contrôle interne). Hash SHA-256 + code de vérification garantissent
        l'intégrité du document.
    </div>
@endsection
