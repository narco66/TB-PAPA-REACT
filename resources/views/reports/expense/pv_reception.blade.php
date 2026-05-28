@extends('reports.layouts.institutional')

@section('content')
    @php
        $r = $donnees['reception'] ?? null;
        $fmt = fn ($n) => number_format((float) $n, 2, ',', ' ');
    @endphp

    @if(!$r)
        <div class="callout danger"><div class="titre">Réception introuvable</div></div>
    @else
        <div class="metas">
            <table>
                <tr>
                    <td class="label">Référence</td>
                    <td><strong>{{ $r->reference }}</strong></td>
                    <td class="label">Statut</td>
                    <td><span class="badge {{ $r->statut === 'valide' || $r->statut === 'cloture' ? 'success' : ($r->statut === 'rejete' ? 'danger' : 'info') }}">{{ $r->statut }}</span></td>
                </tr>
                <tr>
                    <td class="label">Date</td>
                    <td>{{ $r->date_reception->format('d/m/Y') }}</td>
                    <td class="label">Type</td>
                    <td><span class="badge info">{{ $r->type_reception }}</span></td>
                </tr>
                <tr>
                    <td class="label">Engagement</td>
                    <td><strong>{{ $r->mouvement->reference ?? '—' }}</strong></td>
                    <td class="label">Nature</td>
                    <td>{{ $r->nature }}</td>
                </tr>
            </table>
        </div>

        <h1>I. Objet de la réception</h1>
        <table class="data">
            @if($r->mouvement?->expenseRequest)
                <tr>
                    <th style="width:25%">Expression du besoin</th>
                    <td><strong>{{ $r->mouvement->expenseRequest->numero }}</strong> — {{ $r->mouvement->expenseRequest->objet }}</td>
                </tr>
            @endif
            <tr>
                <th>Nature</th>
                <td><span class="badge info">{{ $r->nature }}</span></td>
            </tr>
            <tr>
                <th>Type de réception</th>
                <td>{{ $r->type_reception }}</td>
            </tr>
            @if($r->quantite_recue)
                <tr>
                    <th>Quantité reçue</th>
                    <td class="num">{{ $fmt($r->quantite_recue) }} {{ $r->unite_mesure }}</td>
                </tr>
            @endif
            <tr>
                <th>Montant reçu</th>
                <td class="num strong" style="font-size:13pt;">{{ $fmt($r->montant_recu) }}</td>
            </tr>
        </table>

        <h1>II. Conformité</h1>
        <table class="data">
            <tr>
                <th style="width:25%">Conformité globale</th>
                <td>
                    <span class="badge {{ $r->conformite === 'conforme' ? 'success' : ($r->conformite === 'non_conforme' ? 'danger' : 'warning') }}">
                        {{ str_replace('_', ' ', $r->conformite) }}
                    </span>
                </td>
            </tr>
            @if($r->reserves)
                <tr>
                    <th>Réserves</th>
                    <td class="small">{{ $r->reserves }}</td>
                </tr>
            @endif
            @if($r->observations)
                <tr>
                    <th>Observations</th>
                    <td class="small">{{ $r->observations }}</td>
                </tr>
            @endif
        </table>

        @if($r->mouvement?->supplier)
            <h1>III. Fournisseur</h1>
            <table class="data">
                <tr>
                    <th style="width:25%">Code</th>
                    <td><strong>{{ $r->mouvement->supplier->code }}</strong></td>
                </tr>
                <tr>
                    <th>Libellé</th>
                    <td>{{ $r->mouvement->supplier->libelle }}</td>
                </tr>
                @if($r->mouvement->supplier->nif)
                    <tr><th>NIF</th><td>{{ $r->mouvement->supplier->nif }}</td></tr>
                @endif
                @if($r->mouvement->supplier->adresse)
                    <tr><th>Adresse</th><td class="small">{{ $r->mouvement->supplier->adresse }}</td></tr>
                @endif
            </table>
        @endif

        <h1>IV. Commission de réception</h1>
        <table class="data">
            <tr>
                <th style="width:25%">Président</th>
                <td>{{ $r->presidentCommission->name ?? '—' }} <span class="small text-muted">{{ $r->presidentCommission->fonction ?? '' }}</span></td>
            </tr>
            <tr>
                <th>Membre 1</th>
                <td>{{ $r->membre1->name ?? '—' }} <span class="small text-muted">{{ $r->membre1->fonction ?? '' }}</span></td>
            </tr>
            <tr>
                <th>Membre 2</th>
                <td>{{ $r->membre2->name ?? '—' }} <span class="small text-muted">{{ $r->membre2->fonction ?? '' }}</span></td>
            </tr>
        </table>

        <h1>V. Validation institutionnelle</h1>
        <table class="data">
            <tr>
                <th style="width:25%">Saisi par</th>
                <td>{{ $r->saisiPar->name ?? '—' }} le {{ $r->created_at?->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <th>Validé par</th>
                <td>{{ $r->validePar->name ?? '—' }} <span class="small text-muted">{{ $r->validePar->fonction ?? '' }}</span></td>
            </tr>
            <tr>
                <th>Date validation</th>
                <td>{{ $r->valide_at?->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
        </table>

        <div class="signatures mt-3">
            <div class="sig">
                <div class="role">Président commission</div>
                <div class="nom">{{ $r->presidentCommission->name ?? '_______________________' }}</div>
            </div>
            <div class="sig">
                <div class="role">Membre 1</div>
                <div class="nom">{{ $r->membre1->name ?? '_______________________' }}</div>
            </div>
            <div class="sig">
                <div class="role">Membre 2</div>
                <div class="nom">{{ $r->membre2->name ?? '_______________________' }}</div>
            </div>
        </div>

        <div class="callout mt-3 small">
            <div class="titre">Conformité institutionnelle</div>
            Procès-verbal établi conformément au RGCP et au système OHADA applicable en Afrique centrale.
            La commission de réception est composée d'au moins 3 membres pour garantir la séparation des fonctions (COSO ERM 2017).
            La réception conforme conditionne la liquidation et le paiement.
        </div>
    @endif
@endsection
