@extends('reports.layouts.institutional')

@section('content')
    @php
        $m = $donnees['mouvement'] ?? null;
        $fmt = fn ($n) => number_format((float) $n, 2, ',', ' ');
    @endphp

    @if(!$m)
        <div class="callout danger"><div class="titre">Ordonnancement introuvable</div></div>
    @else
        <div class="metas">
            <table>
                <tr>
                    <td class="label">N° ordonnance</td>
                    <td><strong>{{ $m->reference }}</strong></td>
                    <td class="label">Date</td>
                    <td>{{ $m->date_mouvement?->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="label">N° pièce</td>
                    <td>{{ $m->numero_piece ?? '—' }}</td>
                    <td class="label">Statut</td>
                    <td><span class="badge {{ $m->statut_mouvement === 'valide' ? 'success' : 'secondary' }}">{{ $m->statut_mouvement }}</span></td>
                </tr>
                @if($m->parent?->parent?->expenseRequest)
                    <tr>
                        <td class="label">Expression source</td>
                        <td colspan="3"><strong>{{ $m->parent->parent->expenseRequest->numero }}</strong> — {{ $m->parent->parent->expenseRequest->objet }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <h1>Ordonnance de paiement</h1>

        <p>Il est ordonné au Comptable désigné de payer au bénéficiaire ci-après désigné la somme indiquée,
        sur la base de la liquidation référencée.</p>

        <h2>I. Bénéficiaire</h2>
        <table class="data">
            <tr><th style="width:25%">Nom / Libellé</th><td><strong>{{ $m->supplier?->libelle ?? $m->beneficiaire_nom ?? '—' }}</strong></td></tr>
            @if($m->supplier)
                @if($m->supplier->nif)<tr><th>NIF</th><td>{{ $m->supplier->nif }}</td></tr>@endif
                @if($m->supplier->compte_bancaire)
                    <tr><th>Compte bancaire</th><td>{{ $m->supplier->compte_bancaire }}</td></tr>
                @endif
                @if($m->supplier->banque)
                    <tr><th>Banque</th><td>{{ $m->supplier->banque }}</td></tr>
                @endif
            @endif
        </table>

        <h2>II. Montant à payer</h2>
        <table class="data">
            <tr>
                <th style="width:30%">Montant ordonnancé</th>
                <td class="num strong" style="font-size:14pt; color:#1e5cb3;">{{ $fmt($m->montant) }}</td>
            </tr>
            <tr>
                <th>Liquidation source</th>
                <td>{{ $m->parent?->reference ?? '—' }}</td>
            </tr>
            <tr>
                <th>Imputation budgétaire</th>
                <td>{{ $m->ligne?->budget_ligne_code }} — {{ $m->ligne?->libelle }}</td>
            </tr>
            @if($m->motif)
                <tr><th>Motif</th><td class="small">{{ $m->motif }}</td></tr>
            @endif
        </table>

        <h2>III. Séparation COSO ERM</h2>
        <table class="data">
            <tr>
                <th style="width:30%">Ordonnateur (émetteur)</th>
                <td>{{ $m->ordonnateur?->name ?? '—' }} <span class="small text-muted">{{ $m->ordonnateur?->fonction ?? '' }}</span></td>
            </tr>
            <tr>
                <th>Comptable assignataire</th>
                <td>{{ $m->comptable?->name ?? 'À désigner' }}</td>
            </tr>
        </table>

        <div class="signatures mt-3">
            <div class="sig">
                <div class="role">Ordonnateur</div>
                <div class="nom">{{ $m->ordonnateur?->name ?? '_______________________' }}</div>
            </div>
            <div class="sig">
                <div class="role">Reçu par le Comptable</div>
                <div class="nom">_______________________</div>
            </div>
            <div class="sig">
                <div class="role">Date</div>
                <div class="nom">{{ $m->date_mouvement?->format('d/m/Y') }}</div>
            </div>
        </div>

        <div class="callout mt-3 small">
            <div class="titre">Document IPSAS 24</div>
            Ordonnance de paiement conforme à la nomenclature IPSAS 24 — comptabilité budgétaire publique.
            La séparation ordonnateur ≠ comptable garantit le contrôle interne (COSO ERM 2017).
        </div>
    @endif
@endsection
