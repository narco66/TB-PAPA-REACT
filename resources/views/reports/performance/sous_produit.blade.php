@extends('reports.layouts.institutional')

@section('content')
    @php $sp = $donnees['sous_produit']; @endphp

    <div class="metas">
        <table>
            <tr>
                <td class="label">Sous-Produit</td>
                <td><strong>{{ $sp->code }} — {{ $sp->libelle }}</strong></td>
                <td class="label">PAPA</td>
                <td>{{ $sp->produit->axe->papa->annee }}</td>
            </tr>
            <tr>
                <td class="label">Axe</td>
                <td>{{ $sp->produit->axe->code }}</td>
                <td class="label">Produit</td>
                <td>{{ $sp->produit->code }} — {{ $sp->produit->libelle }}</td>
            </tr>
            <tr>
                <td class="label">Direction</td>
                <td>{{ $sp->direction ? $sp->direction->code : '—' }}</td>
                <td class="label">Responsable</td>
                <td>{{ $sp->responsable->name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Statut</td>
                <td><span class="badge {{ $sp->statut === 'valide' ? 'success' : 'secondary' }}">{{ $sp->statut }}</span></td>
                <td class="label">Période</td>
                <td>{{ optional($sp->date_debut)->format('d/m/Y') }} → {{ optional($sp->date_fin)->format('d/m/Y') }}</td>
            </tr>
        </table>
    </div>

    @if($sp->description)
        <h1>Description</h1>
        <p>{{ $sp->description }}</p>
    @endif

    <h1>Avancement</h1>
    <div class="kpi-grid">
        <div class="kpi">
            <div class="label">Taux d'exécution</div>
            <div class="value">{{ number_format($sp->taux_execution, 1) }}%</div>
            <div class="sub">Poids : {{ number_format($sp->poids, 0) }}</div>
        </div>
        <div class="kpi">
            <div class="label">Activités</div>
            <div class="value">{{ $sp->activites->count() }}</div>
        </div>
        <div class="kpi">
            <div class="label">Tâches totales</div>
            <div class="value">{{ $sp->activites->sum(fn($a) => $a->taches->count()) }}</div>
        </div>
        <div class="kpi">
            <div class="label">Indicateurs KPI</div>
            <div class="value">{{ $sp->indicateurs->count() }}</div>
        </div>
    </div>

    <h1>Activités planifiées ({{ $sp->activites->count() }})</h1>
    @if($sp->activites->isEmpty())
        <p class="text-muted small">Aucune activité définie.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width:12%">Code</th>
                    <th>Libellé</th>
                    <th style="width:14%">Période</th>
                    <th style="width:10%">Statut</th>
                    <th style="width:8%" class="right">Tâches</th>
                    <th style="width:16%">Avancement</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sp->activites as $act)
                    <tr>
                        <td class="font-mono small">{{ $act->code }}{{ $act->est_jalon ? ' ◆' : '' }}</td>
                        <td class="small">{{ \Illuminate\Support\Str::limit($act->libelle, 70) }}</td>
                        <td class="small">{{ optional($act->date_debut)->format('d/m/y') }}→{{ optional($act->date_fin)->format('d/m/y') }}</td>
                        <td><span class="badge secondary">{{ $act->statut }}</span></td>
                        <td class="num">{{ $act->taches->count() }}</td>
                        <td>
                            <div class="progress {{ $act->taux_execution >= 75 ? 'success' : ($act->taux_execution >= 40 ? 'warning' : 'danger') }}">
                                <div class="fill" style="width: {{ min(100, $act->taux_execution) }}%"></div>
                            </div>
                            <div class="small">{{ number_format($act->taux_execution, 1) }}%</div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($sp->indicateurs->isNotEmpty())
        <h1>Indicateurs KPI rattachés ({{ $sp->indicateurs->count() }})</h1>
        <table class="data">
            <thead>
                <tr>
                    <th style="width:10%">Code</th>
                    <th>Libellé</th>
                    <th style="width:6%">Unité</th>
                    <th style="width:10%" class="right">Baseline</th>
                    <th style="width:10%" class="right">Cible</th>
                    <th style="width:10%" class="right">Actuel</th>
                    <th style="width:14%">Taux</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sp->indicateurs as $i)
                    <tr>
                        <td class="font-mono small">{{ $i->code }}</td>
                        <td class="small">{{ \Illuminate\Support\Str::limit($i->libelle, 60) }}</td>
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
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="mt-3 small text-muted">
        Fiche destinée au Directeur porteur et au Commissaire de tutelle.
    </div>
@endsection
