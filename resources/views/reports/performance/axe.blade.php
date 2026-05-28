@extends('reports.layouts.institutional')

@section('content')
    @php
        $axe = $donnees['axe'];
        $stats = $donnees['stats'];
    @endphp

    <div class="metas">
        <table>
            <tr>
                <td class="label">Axe</td>
                <td><strong>{{ $axe->code }} — {{ $axe->libelle }}</strong></td>
                <td class="label">PAPA</td>
                <td>{{ $axe->papa->annee }} ({{ $axe->papa->libelle }})</td>
            </tr>
            <tr>
                <td class="label">Département</td>
                <td>{{ $axe->departement ? $axe->departement->code . ' — ' . $axe->departement->libelle : '—' }}</td>
                <td class="label">Responsable</td>
                <td>{{ $axe->responsable->name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Période</td>
                <td>{{ optional($axe->date_debut)->format('d/m/Y') }} → {{ optional($axe->date_fin)->format('d/m/Y') }}</td>
                <td class="label">Statut</td>
                <td><span class="badge {{ $axe->statut === 'valide' ? 'success' : 'secondary' }}">{{ $axe->statut }}</span></td>
            </tr>
        </table>
    </div>

    @if($axe->description)
        <h1>1. Description</h1>
        <p>{{ $axe->description }}</p>
    @endif

    <h1>2. Volumétrie</h1>
    <div class="kpi-grid">
        <div class="kpi">
            <div class="label">Produits</div>
            <div class="value">{{ $stats['nb_produits'] }}</div>
        </div>
        <div class="kpi">
            <div class="label">Sous-Produits</div>
            <div class="value">{{ $stats['nb_sous_produits'] }}</div>
        </div>
        <div class="kpi">
            <div class="label">Activités</div>
            <div class="value">{{ $stats['nb_activites'] }}</div>
            <div class="sub">{{ $stats['activites_realisees'] }} réalisées</div>
        </div>
        <div class="kpi">
            <div class="label">En retard</div>
            <div class="value">{{ $stats['activites_retard'] }}</div>
            <div class="sub">Activités à risque</div>
        </div>
    </div>

    <h1>3. Avancement global de l'axe</h1>
    <table class="data">
        <tr>
            <th style="width:20%">Taux d'exécution physique</th>
            <td>
                <div class="progress {{ $axe->taux_execution >= 75 ? 'success' : ($axe->taux_execution >= 40 ? 'warning' : 'danger') }}">
                    <div class="fill" style="width: {{ min(100, $axe->taux_execution) }}%"></div>
                </div>
                <div class="small mt-1 strong">{{ number_format($axe->taux_execution, 2) }}%</div>
            </td>
            <th style="width:15%">Poids dans le PAPA</th>
            <td class="num">{{ number_format($axe->poids, 2) }}</td>
        </tr>
    </table>

    <h1>4. Détail des Produits et Sous-Produits</h1>

    @forelse($axe->produits as $produit)
        <h2>{{ $produit->code }} — {{ $produit->libelle }}</h2>
        <table class="data">
            <tr>
                <th style="width:20%">Statut</th>
                <td><span class="badge {{ $produit->statut === 'valide' ? 'success' : 'secondary' }}">{{ $produit->statut }}</span></td>
                <th style="width:15%">Taux d'exécution</th>
                <td>
                    <div class="progress {{ $produit->taux_execution >= 75 ? 'success' : ($produit->taux_execution >= 40 ? 'warning' : 'danger') }}">
                        <div class="fill" style="width: {{ min(100, $produit->taux_execution) }}%"></div>
                    </div>
                    <div class="small">{{ number_format($produit->taux_execution, 1) }}%</div>
                </td>
            </tr>
        </table>

        @if($produit->sousProduits->isNotEmpty())
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:14%">Code SP</th>
                        <th>Libellé</th>
                        <th style="width:10%">Statut</th>
                        <th style="width:8%" class="right">Activités</th>
                        <th style="width:8%" class="right">KPI</th>
                        <th style="width:18%">Avancement</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($produit->sousProduits as $sp)
                        <tr>
                            <td class="strong">{{ $sp->code }}</td>
                            <td>{{ $sp->libelle }}</td>
                            <td><span class="badge secondary">{{ $sp->statut }}</span></td>
                            <td class="num">{{ $sp->activites->count() }}</td>
                            <td class="num">{{ $sp->indicateurs->count() }}</td>
                            <td>
                                <div class="progress {{ $sp->taux_execution >= 75 ? 'success' : ($sp->taux_execution >= 40 ? 'warning' : 'danger') }}">
                                    <div class="fill" style="width: {{ min(100, $sp->taux_execution) }}%"></div>
                                </div>
                                <div class="small">{{ number_format($sp->taux_execution, 1) }}%</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="mt-2"></div>
    @empty
        <p class="text-muted small">Aucun produit défini pour cet axe.</p>
    @endforelse

    <div class="mt-3 small text-muted">
        Cette fiche est destinée au Commissaire en charge du département concerné, au Cabinet et au Secrétariat Général.
        Elle reflète l'état du système au moment de l'émission.
    </div>
@endsection
