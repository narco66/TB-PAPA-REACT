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
                    <td class="label">Date du rejet</td>
                    <td>{{ $r->valide_at?->format('d/m/Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Destinataire</td>
                    <td colspan="3">{{ $r->demandeur->name ?? '—' }} — {{ $r->demandeur->fonction ?? '' }}</td>
                </tr>
            </table>
        </div>

        <div class="callout danger">
            <div class="titre">Décision : Rejet</div>
            La demande d'engagement budgétaire référencée ci-dessous est <strong>rejetée</strong> par
            le valideur hiérarchique. Aucune suite ne sera donnée à cette expression du besoin.
        </div>

        <h1>I. Demande concernée</h1>
        <table class="data">
            <tr><th style="width:25%">Référence</th><td><strong>{{ $r->numero }}</strong></td></tr>
            <tr><th>Type d'engagement</th><td>{{ $labels[$r->type_engagement] ?? $r->type_engagement }}</td></tr>
            <tr><th>Objet</th><td>{{ $r->objet }}</td></tr>
            <tr><th>Montant estimé</th><td class="num">{{ $fmt($r->montant_estime) }} {{ $r->devise }}</td></tr>
            <tr><th>Département</th><td>{{ $r->departement?->libelle ?? '—' }}</td></tr>
        </table>

        <h1>II. Motif du rejet</h1>
        <div class="callout warning">
            <div class="titre">Motif détaillé</div>
            <p class="whitespace-pre-line">{{ $r->motif_decision ?? '—' }}</p>
        </div>

        <h1>III. Suite à donner</h1>
        <p>Conformément aux procédures internes, vous pouvez :</p>
        <ul class="small">
            <li>Initier une nouvelle expression du besoin tenant compte des observations formulées ;</li>
            <li>Solliciter un entretien avec le valideur pour explication complémentaire ;</li>
            <li>Faire appel auprès de l'autorité hiérarchique supérieure si vous contestez la décision.</li>
        </ul>

        <div class="signatures mt-3">
            <div class="sig">
                <div class="role">Valideur</div>
                <div class="nom">{{ $r->valideurHierarchique->name ?? '_______________________' }}</div>
            </div>
            <div class="sig">
                <div class="role">Pris connaissance par le demandeur</div>
                <div class="nom">_______________________</div>
            </div>
            <div class="sig">
                <div class="role">Date</div>
                <div class="nom">{{ $r->valide_at?->format('d/m/Y') ?? '—' }}</div>
            </div>
        </div>
    @endif
@endsection
