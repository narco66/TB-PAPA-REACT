@extends('reports.layouts.institutional')

@section('content')
    @php
        $m = $donnees['mouvement'] ?? null;
        $fmt = fn ($n) => number_format((float) $n, 2, ',', ' ');
    @endphp

    @if(!$m)
        <div class="callout danger"><div class="titre">Engagement introuvable</div></div>
    @else
        <div class="metas">
            <table>
                <tr>
                    <td class="label">Référence</td>
                    <td><strong>{{ $m->reference }}</strong></td>
                    <td class="label">Statut</td>
                    <td><span class="badge {{ $m->statut_mouvement === 'valide' ? 'success' : ($m->statut_mouvement === 'rejete' ? 'danger' : 'info') }}">{{ $m->statut_mouvement }}</span></td>
                </tr>
                <tr>
                    <td class="label">Date engagement</td>
                    <td>{{ $m->date_mouvement?->format('d/m/Y') }}</td>
                    <td class="label">N° de pièce</td>
                    <td>{{ $m->numero_piece ?? '—' }}</td>
                </tr>
                @if($m->expenseRequest)
                    <tr>
                        <td class="label">Expression source</td>
                        <td colspan="3"><strong>{{ $m->expenseRequest->numero }}</strong> — {{ $m->expenseRequest->objet }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <h1>I. Montant engagé</h1>
        <table class="data">
            <tr>
                <th style="width:25%">Montant total engagé</th>
                <td class="num strong" style="font-size:14pt;">{{ $fmt($m->montant) }}</td>
                <th>Ligne budgétaire racine</th>
                <td><strong>{{ $m->ligne?->budget_ligne_code }}</strong> — {{ $m->ligne?->libelle }}</td>
            </tr>
            @if($m->type_engagement)
                <tr><th>Type d'engagement</th><td colspan="3">{{ $m->type_engagement }}</td></tr>
            @endif
            @if($m->motif)
                <tr><th>Motif</th><td colspan="3" class="small">{{ $m->motif }}</td></tr>
            @endif
        </table>

        @if($m->commitmentLines && $m->commitmentLines->isNotEmpty())
            <h1>II. Imputations budgétaires</h1>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:18%">Code ligne</th>
                        <th>Libellé</th>
                        <th style="width:15%">Source</th>
                        <th style="width:12%" class="right">Part CEEAC</th>
                        <th style="width:12%" class="right">Part PTF</th>
                        <th style="width:15%" class="right">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @php($totalImp = 0)
                    @foreach($m->commitmentLines as $cl)
                        @php($totalImp += (float) $cl->montant)
                        <tr>
                            <td class="strong">{{ $cl->ligne?->budget_ligne_code ?? '—' }}</td>
                            <td class="small">{{ $cl->libelle }}</td>
                            <td class="small">{{ $cl->source?->code ?? '—' }}</td>
                            <td class="num small">{{ $fmt($cl->montant_ceeac) }}</td>
                            <td class="num small">{{ $fmt($cl->montant_ptf) }}</td>
                            <td class="num">{{ $fmt($cl->montant) }}</td>
                        </tr>
                    @endforeach
                    <tr style="background:#eff6ff;">
                        <td colspan="5" class="strong">TOTAL IMPUTÉ</td>
                        <td class="num strong">{{ $fmt($totalImp) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        <h1>III. Bénéficiaire / Fournisseur</h1>
        <table class="data">
            <tr>
                <th style="width:25%">Nom</th>
                <td>{{ $m->supplier->libelle ?? $m->beneficiaire_nom ?? '—' }}</td>
            </tr>
            <tr>
                <th>Référence</th>
                <td>{{ $m->supplier->code ?? $m->beneficiaire_reference ?? '—' }}</td>
            </tr>
            @if($m->supplier)
                @if($m->supplier->nif)
                    <tr><th>NIF</th><td>{{ $m->supplier->nif }}</td></tr>
                @endif
                @if($m->supplier->rccm)
                    <tr><th>RCCM</th><td>{{ $m->supplier->rccm }}</td></tr>
                @endif
            @endif
        </table>

        <h1>IV. Gouvernance (séparation COSO)</h1>
        <table class="data">
            <tr>
                <th style="width:25%">Ordonnateur</th>
                <td>{{ $m->ordonnateur->name ?? '—' }} <span class="small text-muted">{{ $m->ordonnateur->fonction ?? '' }}</span></td>
            </tr>
            <tr>
                <th>Comptable (paiement)</th>
                <td>{{ $m->comptable->name ?? '—' }} <span class="small text-muted">{{ $m->comptable->fonction ?? '' }}</span></td>
            </tr>
            <tr>
                <th>Saisi par</th>
                <td>{{ $m->saisiPar->name ?? '—' }} le {{ $m->created_at?->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <th>Validé par</th>
                <td>{{ $m->validePar->name ?? '—' }} le {{ $m->valide_at?->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
        </table>

        <div class="signatures mt-3">
            <div class="sig">
                <div class="role">Ordonnateur</div>
                <div class="nom">{{ $m->ordonnateur->name ?? '_______________________' }}</div>
            </div>
            <div class="sig">
                <div class="role">Contrôle financier</div>
                <div class="nom">_______________________</div>
            </div>
            <div class="sig">
                <div class="role">Date</div>
                <div class="nom">{{ $m->date_mouvement?->format('d/m/Y') }}</div>
            </div>
        </div>

        <div class="callout mt-3 small">
            <div class="titre">Anti-dépassement budgétaire vérifié</div>
            L'engagement a été contrôlé contre le disponible budgétaire de la ligne. La séparation des fonctions ordonnateur/comptable est appliquée conformément à COSO ERM 2017. Le mouvement est enregistré dans la chaîne IPSAS : engagement → liquidation → ordonnancement → paiement.
        </div>
    @endif
@endsection
