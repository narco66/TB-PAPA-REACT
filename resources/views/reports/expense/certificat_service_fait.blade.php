@extends('reports.layouts.institutional')

@section('content')
    @php
        $c = $donnees['certificat'] ?? null;
        $fmt = fn ($n) => number_format((float) $n, 2, ',', ' ');
    @endphp

    @if(!$c)
        <div class="callout danger"><div class="titre">Certificat introuvable</div></div>
    @else
        <div class="metas">
            <table>
                <tr>
                    <td class="label">Référence</td>
                    <td><strong>{{ $c->reference }}</strong></td>
                    <td class="label">Statut</td>
                    <td><span class="badge {{ $c->statut === 'valide' ? 'success' : ($c->statut === 'rejete' ? 'danger' : 'info') }}">{{ $c->statut }}</span></td>
                </tr>
                <tr>
                    <td class="label">Date constatation</td>
                    <td>{{ $c->date_constatation->format('d/m/Y') }}</td>
                    <td class="label">Engagement source</td>
                    <td><strong>{{ $c->mouvement->reference ?? '—' }}</strong></td>
                </tr>
                @if($c->mouvement?->expenseRequest)
                    <tr>
                        <td class="label">Expression du besoin</td>
                        <td colspan="3"><strong>{{ $c->mouvement->expenseRequest->numero }}</strong> — {{ $c->mouvement->expenseRequest->objet }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <h1>I. Description de la prestation</h1>
        <table class="data">
            <tr>
                <th style="width:25%">Description</th>
                <td>{{ $c->description }}</td>
            </tr>
            <tr>
                <th>Montant constaté</th>
                <td class="num strong" style="font-size:13pt;">{{ $fmt($c->montant_constate) }}</td>
            </tr>
            <tr>
                <th>Montant engagé initial</th>
                <td class="num">{{ $fmt($c->mouvement->montant ?? 0) }}</td>
            </tr>
        </table>

        <h1>II. Conformité de l'exécution</h1>
        <table class="data">
            <tr>
                <th style="width:30%">Conformité qualitative</th>
                <td>
                    <span class="badge {{ $c->conformite_qualitative === 'conforme' ? 'success' : ($c->conformite_qualitative === 'non_conforme' ? 'danger' : 'warning') }}">
                        {{ str_replace('_', ' ', $c->conformite_qualitative) }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Conformité quantitative</th>
                <td>
                    <span class="badge {{ $c->conformite_quantitative === 'conforme' ? 'success' : ($c->conformite_quantitative === 'non_conforme' ? 'danger' : 'warning') }}">
                        {{ str_replace('_', ' ', $c->conformite_quantitative) }}
                    </span>
                </td>
            </tr>
            @if($c->observations)
                <tr>
                    <th>Observations</th>
                    <td class="small">{{ $c->observations }}</td>
                </tr>
            @endif
        </table>

        @if($c->mouvement?->supplier)
            <h1>III. Fournisseur</h1>
            <table class="data">
                <tr>
                    <th style="width:25%">Code</th>
                    <td><strong>{{ $c->mouvement->supplier->code }}</strong></td>
                </tr>
                <tr>
                    <th>Libellé</th>
                    <td>{{ $c->mouvement->supplier->libelle }}</td>
                </tr>
                @if($c->mouvement->supplier->nif)
                    <tr><th>NIF</th><td>{{ $c->mouvement->supplier->nif }}</td></tr>
                @endif
            </table>
        @endif

        <h1>IV. Constatation et validation</h1>
        <table class="data">
            <tr>
                <th style="width:25%">Constaté par</th>
                <td>{{ $c->constatePar->name ?? '—' }} <span class="small text-muted">{{ $c->constatePar->fonction ?? '' }}</span></td>
            </tr>
            <tr>
                <th>Date de saisie</th>
                <td>{{ $c->created_at?->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <th>Validé par</th>
                <td>{{ $c->validePar->name ?? '—' }} <span class="small text-muted">{{ $c->validePar->fonction ?? '' }}</span></td>
            </tr>
            <tr>
                <th>Date validation</th>
                <td>{{ $c->valide_at?->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
            @if($c->motif_rejet)
                <tr>
                    <th>Motif de rejet</th>
                    <td class="text-danger small">{{ $c->motif_rejet }}</td>
                </tr>
            @endif
        </table>

        <div class="signatures mt-3">
            <div class="sig">
                <div class="role">Constateur</div>
                <div class="nom">{{ $c->constatePar->name ?? '_______________________' }}</div>
            </div>
            <div class="sig">
                <div class="role">Validateur</div>
                <div class="nom">{{ $c->validePar->name ?? '_______________________' }}</div>
            </div>
            <div class="sig">
                <div class="role">Date</div>
                <div class="nom">{{ $c->valide_at?->format('d/m/Y') ?? '___________' }}</div>
            </div>
        </div>

        <div class="callout mt-3 small">
            <div class="titre">Conformité</div>
            Document attestant la réalité de la prestation conformément au Règlement Général de Comptabilité Publique (RGCP).
            Le service fait est constaté préalablement à toute liquidation, conformément aux principes de séparation des fonctions (COSO ERM 2017).
            Hash SHA-256 garantissant l'intégrité du document.
        </div>
    @endif
@endsection
