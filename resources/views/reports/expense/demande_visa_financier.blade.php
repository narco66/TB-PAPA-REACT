@extends('reports.layouts.institutional')

@section('content')
    @php
        $r = $donnees['request'] ?? null;
        $labels = $donnees['types_engagement'] ?? [];
        $fmt = fn ($n) => number_format((float) $n, 2, ',', ' ');
    @endphp

    @if(!$r)
        <div class="callout danger"><div class="titre">Expression introuvable</div></div>
    @else
        <div class="metas">
            <table>
                <tr>
                    <td class="label">N° référence</td>
                    <td><strong>{{ $r->numero }}</strong></td>
                    <td class="label">Statut</td>
                    <td><span class="badge {{ $r->statut === 'valide' ? 'success' : 'info' }}">{{ $r->statut }}</span></td>
                </tr>
                <tr>
                    <td class="label">Destinataire</td>
                    <td colspan="3"><strong>Contrôleur Financier Central — Commission de la CEEAC</strong></td>
                </tr>
            </table>
        </div>

        <h1>Objet : Demande de visa financier</h1>
        <p>Le Demandeur soussigné a l'honneur de solliciter votre visa préalable au titre de l'engagement budgétaire de la dépense suivante.</p>

        <h2>1. Identification de la demande</h2>
        <table class="data">
            <tr><th style="width:25%">Type d'engagement</th><td>{{ $labels[$r->type_engagement] ?? $r->type_engagement }}</td></tr>
            <tr><th>Objet</th><td><strong>{{ $r->objet }}</strong></td></tr>
            <tr><th>Justification</th><td>{{ $r->justification }}</td></tr>
            @if($r->description_detaillee)
                <tr><th>Description détaillée</th><td class="small">{{ $r->description_detaillee }}</td></tr>
            @endif
        </table>

        <h2>2. Imputation institutionnelle</h2>
        <table class="data">
            <tr>
                <th style="width:25%">Exercice</th>
                <td>{{ $r->exercice->annee ?? '—' }}</td>
                <th>Département</th>
                <td>{{ $r->departement?->libelle ?? '—' }}</td>
            </tr>
            <tr>
                <th>Direction</th>
                <td>{{ $r->direction?->libelle ?? '—' }}</td>
                <th>Source financement</th>
                <td>{{ $r->sourceFinancement?->libelle ?? '—' }}</td>
            </tr>
        </table>

        <h2>3. Montant à engager</h2>
        <table class="data">
            <tr>
                <th style="width:25%">Montant total estimé</th>
                <td class="num strong" style="font-size:13pt;">{{ $fmt($r->montant_estime) }} {{ $r->devise }}</td>
            </tr>
            <tr><th>Part CEEAC-EM</th><td class="num">{{ $fmt($r->montant_estime_ceeac) }}</td></tr>
            <tr><th>Part PTF</th><td class="num">{{ $fmt($r->montant_estime_ptf) }}</td></tr>
        </table>

        @if($r->supplierPressenti)
            <h2>4. Fournisseur pressenti</h2>
            <table class="data">
                <tr><th style="width:25%">Code</th><td><strong>{{ $r->supplierPressenti->code }}</strong></td></tr>
                <tr><th>Libellé</th><td>{{ $r->supplierPressenti->libelle }}</td></tr>
                @if($r->supplierPressenti->nif)<tr><th>NIF</th><td>{{ $r->supplierPressenti->nif }}</td></tr>@endif
            </table>
        @endif

        <div class="callout">
            <div class="titre">Demande de visa préalable</div>
            En application du Règlement Général de Comptabilité Publique (RGCP) et des principes de
            contrôle interne (COSO ERM 2017), le présent dossier est transmis au Contrôle financier
            pour visa préalable à l'engagement budgétaire.
        </div>

        <div class="signatures mt-3">
            <div class="sig">
                <div class="role">Demandeur</div>
                <div class="nom">{{ $r->demandeur->name ?? '_______________________' }}</div>
            </div>
            <div class="sig">
                <div class="role">Visa du Contrôle financier</div>
                <div class="nom">_______________________</div>
            </div>
            <div class="sig">
                <div class="role">Date</div>
                <div class="nom">{{ now()->format('d/m/Y') }}</div>
            </div>
        </div>
    @endif
@endsection
