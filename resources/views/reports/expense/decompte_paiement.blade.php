@extends('reports.layouts.institutional')

@section('content')
    @php
        $m = $donnees['mouvement'] ?? null;
        $d = $donnees['detail'] ?? null;
        $fmt = fn ($n) => number_format((float) $n, 2, ',', ' ');
        $netAPayer = $d?->montant_net_a_payer ?? $m?->montant ?? 0;
    @endphp

    @if(!$m)
        <div class="callout danger"><div class="titre">Liquidation introuvable</div></div>
    @else
        <div class="metas">
            <table>
                <tr>
                    <td class="label">Décompte n°</td>
                    <td><strong>DEC-{{ $m->reference }}</strong></td>
                    <td class="label">Date</td>
                    <td>{{ $m->date_mouvement?->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Bénéficiaire</td>
                    <td colspan="3"><strong>{{ $m->supplier?->libelle ?? $m->beneficiaire_nom ?? '—' }}</strong>
                        @if($m->supplier?->nif) — NIF {{ $m->supplier->nif }} @endif
                    </td>
                </tr>
                @if($m->parent?->expenseRequest)
                    <tr>
                        <td class="label">Expression source</td>
                        <td colspan="3"><strong>{{ $m->parent->expenseRequest->numero }}</strong> — {{ $m->parent->expenseRequest->objet }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <h1>Décompte de paiement</h1>

        <table class="data">
            <thead>
                <tr><th>Élément</th><th class="right">Montant</th></tr>
            </thead>
            <tbody>
                <tr><td class="strong">Montant total liquidé (brut)</td><td class="num strong">{{ $fmt($m->montant) }}</td></tr>

                @if($d)
                    @if((float) $d->avance_deduite > 0)
                        <tr><td>Avance précédemment versée à déduire</td><td class="num text-warning">− {{ $fmt($d->avance_deduite) }}</td></tr>
                    @endif
                    @if((float) $d->retenue_garantie > 0)
                        <tr><td>Retenue de garantie (caution)</td><td class="num text-warning">− {{ $fmt($d->retenue_garantie) }}</td></tr>
                    @endif
                    @if((float) $d->retenue_fiscale > 0)
                        <tr><td>Retenue fiscale (IRPP, TVA, autres impôts)</td><td class="num text-warning">− {{ $fmt($d->retenue_fiscale) }}</td></tr>
                    @endif
                    @if((float) $d->autres_retenues > 0)
                        <tr><td>Autres retenues</td><td class="num text-warning">− {{ $fmt($d->autres_retenues) }}</td></tr>
                    @endif
                    @if((float) $d->penalites_retard > 0)
                        <tr><td>Pénalités de retard de livraison</td><td class="num text-danger">− {{ $fmt($d->penalites_retard) }}</td></tr>
                    @endif
                    @if((float) $d->autres_penalites > 0)
                        <tr><td>Autres pénalités contractuelles</td><td class="num text-danger">− {{ $fmt($d->autres_penalites) }}</td></tr>
                    @endif
                @endif

                <tr style="background:#dcfce7;">
                    <td class="strong" style="font-size:11pt;">MONTANT NET À PAYER</td>
                    <td class="num strong" style="font-size:14pt; color:#16a34a;">{{ $fmt($netAPayer) }}</td>
                </tr>
            </tbody>
        </table>

        <h2>En toutes lettres</h2>
        <p class="strong">{{ $fmt($netAPayer) }} {{ $m->parent?->ligne?->devise ?? 'XAF' }}</p>

        @if($d?->justificatifs)
            <h2>Justificatifs</h2>
            <p class="small">{{ $d->justificatifs }}</p>
        @endif

        <div class="signatures mt-3">
            <div class="sig">
                <div class="role">Liquidateur</div>
                <div class="nom">{{ $m->validePar?->name ?? '_______________________' }}</div>
            </div>
            <div class="sig">
                <div class="role">Bénéficiaire (pris connaissance)</div>
                <div class="nom">_______________________</div>
            </div>
            <div class="sig">
                <div class="role">Date</div>
                <div class="nom">{{ $m->date_mouvement?->format('d/m/Y') }}</div>
            </div>
        </div>
    @endif
@endsection
