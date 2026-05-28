@extends('reports.layouts.institutional')

@section('content')
    @php
        $items = $donnees['items'] ?? collect();
        $total = $donnees['total'] ?? 0;
        $periode = $donnees['periode'] ?? [];
        $fmt = fn ($n) => number_format((float) $n, 2, ',', ' ');
    @endphp

    <div class="metas">
        <table>
            <tr>
                <td class="label">Période</td>
                <td>
                    @if(($periode['debut'] ?? null) && ($periode['fin'] ?? null))
                        Du {{ \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y') }}
                        au {{ \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y') }}
                    @else
                        Toutes périodes confondues
                    @endif
                </td>
                <td class="label">Volume</td>
                <td><strong>{{ $items->count() }}</strong> ordonnancement(s)</td>
            </tr>
        </table>
    </div>

    <h1>Bordereau d'ordonnancement</h1>

    @if($items->isEmpty())
        <div class="callout"><div class="titre">Aucun ordonnancement sur la période sélectionnée</div></div>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Date</th>
                    <th>N° pièce</th>
                    <th>Liquidation source</th>
                    <th>Bénéficiaire</th>
                    <th>Ordonnateur</th>
                    <th class="right">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $m)
                    <tr>
                        <td class="strong">{{ $m->reference }}</td>
                        <td class="small">{{ $m->date_mouvement?->format('d/m/Y') }}</td>
                        <td class="small">{{ $m->numero_piece ?? '—' }}</td>
                        <td class="small">{{ $m->parent?->reference ?? '—' }}</td>
                        <td class="small">{{ $m->supplier?->libelle ?? $m->beneficiaire_nom ?? '—' }}</td>
                        <td class="small">{{ $m->ordonnateur?->name ?? '—' }}</td>
                        <td class="num">{{ $fmt($m->montant) }}</td>
                    </tr>
                @endforeach
                <tr style="background:#eff6ff;">
                    <td colspan="6" class="strong">TOTAL DU BORDEREAU</td>
                    <td class="num strong" style="font-size:12pt;">{{ $fmt($total) }}</td>
                </tr>
            </tbody>
        </table>

        <p class="small mt-3"><strong>Soit la somme de :</strong> {{ $fmt($total) }} XAF</p>
    @endif

    <div class="signatures mt-3">
        <div class="sig">
            <div class="role">Ordonnateur</div>
            <div class="nom">_______________________</div>
        </div>
        <div class="sig">
            <div class="role">Contrôle financier (visa)</div>
            <div class="nom">_______________________</div>
        </div>
        <div class="sig">
            <div class="role">Reçu par Comptabilité</div>
            <div class="nom">_______________________</div>
        </div>
    </div>

    <div class="callout mt-3 small">
        <div class="titre">Transmission</div>
        Le présent bordereau est transmis à la Comptabilité / Trésorerie pour exécution des paiements
        conformément aux ordonnances qui l'accompagnent. Cycle IPSAS 1 & 24.
    </div>
@endsection
