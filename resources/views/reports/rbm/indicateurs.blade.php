@extends('reports.layouts.institutional')

@section('content')
    @php $papa = $donnees['papa']; $indicateurs = $donnees['indicateurs']; @endphp

    <div class="metas">
        <table>
            <tr>
                <td class="label">PAPA</td>
                <td><strong>{{ $papa?->annee ?? '—' }}</strong> — {{ $papa?->libelle ?? '' }}</td>
                <td class="label">Nombre d'indicateurs</td>
                <td><strong>{{ $indicateurs->count() }}</strong></td>
            </tr>
        </table>
    </div>

    <h1>Matrice des indicateurs de performance</h1>

    @if($indicateurs->isEmpty())
        <p class="text-muted">Aucun indicateur défini pour ce PAPA.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width:8%">Code</th>
                    <th>Libellé</th>
                    <th style="width:12%">Axe / Produit / SP</th>
                    <th style="width:6%">Type</th>
                    <th style="width:5%">Unité</th>
                    <th style="width:7%" class="right">Baseline</th>
                    <th style="width:7%" class="right">Cible</th>
                    <th style="width:7%" class="right">Actuel</th>
                    <th style="width:15%">Taux</th>
                    <th style="width:7%">Tendance</th>
                    <th style="width:9%">Fréquence</th>
                </tr>
            </thead>
            <tbody>
                @foreach($indicateurs as $i)
                    <tr>
                        <td class="strong">{{ $i->code }}</td>
                        <td class="small">{{ \Illuminate\Support\Str::limit($i->libelle, 60) }}</td>
                        <td class="small">
                            {{ $i->sousProduit?->produit?->axe?->code }} /
                            {{ $i->sousProduit?->produit?->code }} /
                            {{ $i->sousProduit?->code }}
                        </td>
                        <td class="small">{{ $i->type }}</td>
                        <td class="small">{{ $i->unite ?? '—' }}</td>
                        <td class="num">{{ $i->baseline !== null ? number_format($i->baseline, 2) : '—' }}</td>
                        <td class="num">{{ $i->cible !== null ? number_format($i->cible, 2) : '—' }}</td>
                        <td class="num">{{ $i->valeur_actuelle !== null ? number_format($i->valeur_actuelle, 2) : '—' }}</td>
                        <td>
                            <div class="progress {{ $i->taux_realisation >= 75 ? 'success' : ($i->taux_realisation >= 40 ? 'warning' : 'danger') }}">
                                <div class="fill" style="width: {{ min(100, max(0, $i->taux_realisation)) }}%"></div>
                            </div>
                            <div class="small">{{ number_format($i->taux_realisation, 1) }}%</div>
                        </td>
                        <td class="small">
                            @if($i->tendance === 'hausse') ↗ Hausse
                            @elseif($i->tendance === 'baisse') ↘ Baisse
                            @elseif($i->tendance === 'stable') → Stable
                            @else ?
                            @endif
                        </td>
                        <td class="small">{{ $i->frequence_collecte }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="mt-3 small text-muted">
        Chaque indicateur est normalisé selon le standard SMART (Spécifique, Mesurable, Atteignable, Réaliste, Temporellement défini).
        Les valeurs sont historisées via la table valeurs_indicateurs (audit ISO 27001).
    </div>
@endsection
