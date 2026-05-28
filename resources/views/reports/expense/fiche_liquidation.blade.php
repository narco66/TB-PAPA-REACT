@extends('reports.layouts.institutional')

@section('content')
    @php
        $m = $donnees['mouvement'] ?? null;
        $d = $donnees['detail'] ?? null;
        $fmt = fn ($n) => number_format((float) $n, 2, ',', ' ');
    @endphp

    @if(!$m)
        <div class="callout danger"><div class="titre">Liquidation introuvable</div></div>
    @else
        <div class="metas">
            <table>
                <tr>
                    <td class="label">Référence</td>
                    <td><strong>{{ $m->reference }}</strong></td>
                    <td class="label">Date</td>
                    <td>{{ $m->date_mouvement?->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Engagement source</td>
                    <td><strong>{{ $m->parent?->reference ?? '—' }}</strong> (montant {{ $fmt($m->parent?->montant ?? 0) }})</td>
                    <td class="label">Bénéficiaire</td>
                    <td>{{ $m->supplier?->libelle ?? $m->beneficiaire_nom ?? '—' }}</td>
                </tr>
            </table>
        </div>

        <h1>I. Liquidation brute</h1>
        <table class="data">
            <tr>
                <th style="width:30%">Montant liquidé (brut)</th>
                <td class="num strong" style="font-size:13pt;">{{ $fmt($m->montant) }}</td>
            </tr>
            <tr>
                <th>Ligne budgétaire</th>
                <td>{{ $m->ligne?->budget_ligne_code }} — {{ $m->ligne?->libelle }}</td>
            </tr>
            @if($m->motif)
                <tr><th>Motif / Description</th><td class="small">{{ $m->motif }}</td></tr>
            @endif
        </table>

        @if($d)
            <h1>II. Détail des retenues et pénalités</h1>
            <table class="data">
                <thead>
                    <tr><th>Élément</th><th class="right">Montant</th></tr>
                </thead>
                <tbody>
                    <tr><td>Montant brut</td><td class="num">{{ $fmt($d->montant_brut) }}</td></tr>
                    @if((float) $d->avance_deduite > 0)
                        <tr><td>− Avance déduite</td><td class="num text-warning">{{ $fmt($d->avance_deduite) }}</td></tr>
                    @endif
                    @if((float) $d->retenue_garantie > 0)
                        <tr><td>− Retenue de garantie</td><td class="num text-warning">{{ $fmt($d->retenue_garantie) }}</td></tr>
                    @endif
                    @if((float) $d->retenue_fiscale > 0)
                        <tr><td>− Retenue fiscale (IRPP, TVA…)</td><td class="num text-warning">{{ $fmt($d->retenue_fiscale) }}</td></tr>
                    @endif
                    @if((float) $d->autres_retenues > 0)
                        <tr><td>− Autres retenues</td><td class="num text-warning">{{ $fmt($d->autres_retenues) }}</td></tr>
                    @endif
                    @if((float) $d->penalites_retard > 0)
                        <tr><td>− Pénalités de retard</td><td class="num text-danger">{{ $fmt($d->penalites_retard) }}</td></tr>
                    @endif
                    @if((float) $d->autres_penalites > 0)
                        <tr><td>− Autres pénalités</td><td class="num text-danger">{{ $fmt($d->autres_penalites) }}</td></tr>
                    @endif
                    <tr style="background:#dcfce7;">
                        <td class="strong">= Montant net à payer</td>
                        <td class="num strong" style="font-size:13pt; color:#16a34a;">{{ $fmt($d->montant_net_a_payer) }}</td>
                    </tr>
                </tbody>
            </table>

            @if($d->justificatifs)
                <h2>Justificatifs</h2>
                <p class="small">{{ $d->justificatifs }}</p>
            @endif
        @else
            <div class="callout small">
                Aucun détail de liquidation enregistré (retenues, pénalités, avances). Le montant net à payer correspond au montant brut.
            </div>
        @endif

        <div class="signatures mt-3">
            <div class="sig">
                <div class="role">Liquidateur</div>
                <div class="nom">{{ $d?->liquidePar?->name ?? $m->validePar?->name ?? '_______________________' }}</div>
            </div>
            <div class="sig">
                <div class="role">Ordonnateur</div>
                <div class="nom">{{ $m->ordonnateur?->name ?? '_______________________' }}</div>
            </div>
            <div class="sig">
                <div class="role">Date</div>
                <div class="nom">{{ $m->date_mouvement?->format('d/m/Y') }}</div>
            </div>
        </div>

        <div class="callout mt-3 small">
            <div class="titre">Conformité</div>
            Liquidation conforme au RGCP : service fait constaté, plafond ≤ engagement initial, retenues et pénalités
            justifiées. Document généré par TB-PAPA-CEEAC, hash SHA-256 garantissant l'intégrité.
        </div>
    @endif
@endsection
