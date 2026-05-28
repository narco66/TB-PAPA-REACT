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
                    <td class="label">Référence</td>
                    <td><strong>{{ $r->numero }}</strong></td>
                    <td class="label">Date du visa</td>
                    <td>{{ $r->valide_at?->format('d/m/Y') ?? '—' }}</td>
                </tr>
            </table>
        </div>

        <div class="callout success">
            <div class="titre">Visa financier accordé</div>
            Le Contrôle financier de la Commission de la CEEAC, après examen du dossier, accorde le visa préalable
            à l'engagement de la dépense visée ci-dessous, conformément au RGCP et aux normes IPSAS 1 & 24.
        </div>

        <h1>I. Identification de la dépense</h1>
        <table class="data">
            <tr><th style="width:25%">Type</th><td><span class="badge info">{{ $labels[$r->type_engagement] ?? $r->type_engagement }}</span></td></tr>
            <tr><th>Objet</th><td><strong>{{ $r->objet }}</strong></td></tr>
            <tr><th>Demandeur</th><td>{{ $r->demandeur->name ?? '—' }} <span class="small text-muted">{{ $r->demandeur->fonction ?? '' }}</span></td></tr>
            <tr><th>Département</th><td>{{ $r->departement?->libelle ?? '—' }}</td></tr>
        </table>

        <h1>II. Conformité budgétaire</h1>
        <table class="data">
            <tr>
                <th style="width:25%">Exercice budgétaire</th>
                <td><strong>{{ $r->exercice->annee ?? '—' }}</strong></td>
                <th>Source de financement</th>
                <td>{{ $r->sourceFinancement?->code ?? '—' }} — {{ $r->sourceFinancement?->libelle ?? '' }}</td>
            </tr>
            <tr>
                <th>Montant visé</th>
                <td class="num strong" style="font-size:13pt;">{{ $fmt($r->montant_estime) }} {{ $r->devise }}</td>
                <th>Conformité</th>
                <td><span class="badge success">VISA ACCORDÉ</span></td>
            </tr>
        </table>

        <h1>III. Contrôles effectués</h1>
        <ul class="small">
            <li>✓ Disponibilité budgétaire vérifiée</li>
            <li>✓ Imputation budgétaire correcte (exercice, ligne, source)</li>
            <li>✓ Pièces justificatives présentes et conformes</li>
            <li>✓ Cohérence Total = Part CEEAC + Part PTF respectée</li>
            <li>✓ Type d'engagement conforme à la nomenclature institutionnelle (18 types CEEAC)</li>
        </ul>

        @if($r->motif_decision)
            <h1>IV. Observations</h1>
            <p class="small">{{ $r->motif_decision }}</p>
        @endif

        <div class="signatures mt-3">
            <div class="sig">
                <div class="role">Contrôleur Financier</div>
                <div class="nom">{{ $r->valideurHierarchique->name ?? '_______________________' }}</div>
            </div>
            <div class="sig">
                <div class="role">Cachet</div>
                <div class="nom"></div>
            </div>
            <div class="sig">
                <div class="role">Date</div>
                <div class="nom">{{ $r->valide_at?->format('d/m/Y') ?? '—' }}</div>
            </div>
        </div>

        <div class="callout mt-3 small">
            <div class="titre">Conformité réglementaire</div>
            Visa délivré conformément au Règlement Général de Comptabilité Publique (RGCP), aux normes
            IPSAS 1 & 24 et au cadre COSO ERM 2017 (séparation des fonctions ordonnateur/comptable).
        </div>
    @endif
@endsection
