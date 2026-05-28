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
                    <td class="label">Destinataire</td>
                    <td>{{ $r->demandeur->name ?? '—' }}</td>
                </tr>
            </table>
        </div>

        <div class="callout warning">
            <div class="titre">Décision : Retour pour correction</div>
            La demande d'engagement budgétaire référencée ci-dessous est <strong>retournée pour correction</strong>.
            Veuillez apporter les modifications demandées avant de la resoumettre.
        </div>

        <h1>I. Demande concernée</h1>
        <table class="data">
            <tr><th style="width:25%">Référence</th><td><strong>{{ $r->numero }}</strong></td></tr>
            <tr><th>Type</th><td>{{ $labels[$r->type_engagement] ?? $r->type_engagement }}</td></tr>
            <tr><th>Objet</th><td>{{ $r->objet }}</td></tr>
            <tr><th>Montant estimé</th><td class="num">{{ $fmt($r->montant_estime) }} {{ $r->devise }}</td></tr>
        </table>

        <h1>II. Corrections demandées</h1>
        <div class="callout">
            <div class="titre">Points à corriger</div>
            <p class="whitespace-pre-line">{{ $r->motif_decision ?? '—' }}</p>
        </div>

        <h1>III. Marche à suivre</h1>
        <ol class="small">
            <li>Connectez-vous à TB-PAPA-CEEAC : <strong>http://tb-papa.ceeac/expense/requests/{{ $r->id }}</strong></li>
            <li>Modifiez l'expression du besoin en tenant compte des observations</li>
            <li>Resoumettez la demande pour validation hiérarchique</li>
        </ol>

        <div class="signatures mt-3">
            <div class="sig">
                <div class="role">Valideur</div>
                <div class="nom">{{ $r->valideurHierarchique->name ?? '_______________________' }}</div>
            </div>
            <div class="sig">
                <div class="role">Date</div>
                <div class="nom">{{ now()->format('d/m/Y') }}</div>
            </div>
        </div>
    @endif
@endsection
