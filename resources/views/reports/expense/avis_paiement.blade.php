@extends('reports.layouts.institutional')

@section('content')
    @php
        $m = $donnees['mouvement'] ?? null;
        $fmt = fn ($n) => number_format((float) $n, 2, ',', ' ');
        $expense = $m?->parent?->parent?->parent?->expenseRequest ?? null;
    @endphp

    @if(!$m)
        <div class="callout danger"><div class="titre">Paiement introuvable</div></div>
    @else
        <div class="metas">
            <table>
                <tr>
                    <td class="label">N° avis</td>
                    <td><strong>AV-{{ $m->reference }}</strong></td>
                    <td class="label">Date paiement</td>
                    <td>{{ $m->date_mouvement?->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Destinataire</td>
                    <td colspan="3"><strong>{{ $m->supplier?->libelle ?? $m->beneficiaire_nom ?? '—' }}</strong></td>
                </tr>
            </table>
        </div>

        <div class="callout success">
            <div class="titre">Avis de paiement</div>
            La Commission de la CEEAC vous informe qu'un paiement a été effectué en votre faveur.
            Le présent avis fait foi du décaissement.
        </div>

        <h1>I. Référence du paiement</h1>
        <table class="data">
            <tr><th style="width:30%">Référence paiement</th><td class="strong">{{ $m->reference }}</td></tr>
            <tr><th>N° pièce comptable</th><td>{{ $m->numero_piece ?? '—' }}</td></tr>
            <tr><th>Date du paiement</th><td>{{ $m->date_mouvement?->format('d/m/Y') }}</td></tr>
            @if($m->date_valeur)
                <tr><th>Date de valeur</th><td>{{ $m->date_valeur->format('d/m/Y') }}</td></tr>
            @endif
            @if($expense)
                <tr><th>Expression source</th><td>{{ $expense->numero }} — {{ $expense->objet }}</td></tr>
            @endif
        </table>

        <h1>II. Montant payé</h1>
        <table class="data">
            <tr>
                <th style="width:30%">Montant versé</th>
                <td class="num strong" style="font-size:14pt; color:#16a34a;">{{ $fmt($m->montant) }}</td>
            </tr>
            <tr>
                <th>Mode de paiement</th>
                <td><span class="badge info">{{ $m->mode_paiement ?? '—' }}</span></td>
            </tr>
            @if($m->compte_bancaire)
                <tr>
                    <th>Compte bancaire crédité</th>
                    <td>{{ $m->compte_bancaire }}</td>
                </tr>
            @endif
        </table>

        <h1>III. Émetteur</h1>
        <table class="data">
            <tr>
                <th style="width:30%">Comptable assignataire</th>
                <td>{{ $m->comptable?->name ?? '—' }} <span class="small text-muted">{{ $m->comptable?->fonction ?? '' }}</span></td>
            </tr>
            <tr>
                <th>Ordonnateur (sur ordo. source)</th>
                <td>{{ $m->parent?->ordonnateur?->name ?? '—' }}</td>
            </tr>
        </table>

        <div class="callout">
            <div class="titre">Important</div>
            En cas d'anomalie constatée sur ce paiement, veuillez contacter la Direction des Affaires Financières
            de la Commission de la CEEAC sous huit (8) jours ouvrés, en référençant l'avis ci-dessus.
        </div>

        <div class="signatures mt-3">
            <div class="sig">
                <div class="role">Comptable</div>
                <div class="nom">{{ $m->comptable?->name ?? '_______________________' }}</div>
            </div>
            <div class="sig">
                <div class="role">Cachet de la Commission</div>
                <div class="nom"></div>
            </div>
            <div class="sig">
                <div class="role">Date d'émission</div>
                <div class="nom">{{ now()->format('d/m/Y') }}</div>
            </div>
        </div>
    @endif
@endsection
