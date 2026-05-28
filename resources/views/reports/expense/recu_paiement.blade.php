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
                    <td class="label">Quittance n°</td>
                    <td><strong>QUI-{{ $m->reference }}</strong></td>
                    <td class="label">Date</td>
                    <td>{{ $m->date_mouvement?->format('d/m/Y') }}</td>
                </tr>
            </table>
        </div>

        <h1 style="text-align:center; font-size:18pt;">REÇU DE PAIEMENT (QUITTANCE)</h1>

        <div style="text-align:center; margin: 24px 0; font-size: 11pt;">
            <p>Reçu de la <strong>Commission de la Communauté Économique des États de l'Afrique Centrale (CEEAC)</strong></p>
            <p>la somme de</p>
            <p style="font-size: 18pt; color: #1e5cb3; font-weight: bold; margin: 12px 0;">{{ $fmt($m->montant) }} XAF</p>
            <p>en règlement de :</p>
            <p style="font-style: italic;">
                <strong>{{ $expense?->objet ?? $m->motif ?? 'Prestation/livraison conformément à l\'engagement' }}</strong>
            </p>
        </div>

        <h2>Identification du règlement</h2>
        <table class="data">
            <tr><th style="width:30%">Référence interne</th><td class="strong">{{ $m->reference }}</td></tr>
            <tr><th>N° pièce comptable</th><td>{{ $m->numero_piece ?? '—' }}</td></tr>
            <tr><th>Mode de paiement</th><td>{{ $m->mode_paiement ?? '—' }}</td></tr>
            <tr><th>Date de paiement</th><td>{{ $m->date_mouvement?->format('d/m/Y') }}</td></tr>
            @if($expense)
                <tr><th>Expression source</th><td>{{ $expense->numero }}</td></tr>
            @endif
        </table>

        <h2>Bénéficiaire</h2>
        <table class="data">
            <tr><th style="width:30%">Nom / Libellé</th><td><strong>{{ $m->supplier?->libelle ?? $m->beneficiaire_nom ?? '—' }}</strong></td></tr>
            @if($m->supplier?->nif)
                <tr><th>NIF</th><td>{{ $m->supplier->nif }}</td></tr>
            @endif
            @if($m->supplier?->rccm)
                <tr><th>RCCM</th><td>{{ $m->supplier->rccm }}</td></tr>
            @endif
        </table>

        <div class="callout success mt-3">
            <div class="titre">Acquit du bénéficiaire</div>
            Le bénéficiaire désigné ci-dessus reconnaît avoir reçu la somme indiquée, et donne quittance
            entière et définitive à la Commission de la CEEAC pour le règlement de la prestation visée.
        </div>

        <div class="signatures mt-3">
            <div class="sig">
                <div class="role">Comptable de la Commission</div>
                <div class="nom">{{ $m->comptable?->name ?? '_______________________' }}</div>
            </div>
            <div class="sig">
                <div class="role">Bénéficiaire (Signature + Cachet)</div>
                <div class="nom"></div>
            </div>
            <div class="sig">
                <div class="role">Date</div>
                <div class="nom">{{ $m->date_mouvement?->format('d/m/Y') }}</div>
            </div>
        </div>

        <div class="callout mt-3 small">
            <div class="titre">Document officiel</div>
            Quittance émise par TB-PAPA-CEEAC, conforme au RGCP et OHADA.
            Hash SHA-256 + code de vérification garantissent l'authenticité du document.
        </div>
    @endif
@endsection
