@extends('reports.layouts.institutional')

@section('content')
    @php $produit = $donnees['produit']; $stats = $donnees['stats']; @endphp

    <div class="metas">
        <table>
            <tr>
                <td class="label">Produit</td>
                <td><strong>{{ $produit->code }} — {{ $produit->libelle }}</strong></td>
                <td class="label">PAPA</td>
                <td>{{ $produit->axe->papa->annee }}</td>
            </tr>
            <tr>
                <td class="label">Axe parent</td>
                <td>{{ $produit->axe->code }} — {{ $produit->axe->libelle }}</td>
                <td class="label">Statut</td>
                <td><span class="badge {{ $produit->statut === 'valide' ? 'success' : 'secondary' }}">{{ $produit->statut }}</span></td>
            </tr>
            <tr>
                <td class="label">Département</td>
                <td>{{ $produit->axe->departement ? $produit->axe->departement->code . ' — ' . $produit->axe->departement->libelle : '—' }}</td>
                <td class="label">Direction porteuse</td>
                <td>{{ $produit->direction ? $produit->direction->code . ' (' . $produit->direction->type . ')' : '—' }}</td>
            </tr>
            <tr>
                <td class="label">Responsable</td>
                <td>{{ $produit->responsable->name ?? '—' }}</td>
                <td class="label">Période</td>
                <td>{{ optional($produit->date_debut)->format('d/m/Y') ?? '—' }} → {{ optional($produit->date_fin)->format('d/m/Y') ?? '—' }}</td>
            </tr>
        </table>
    </div>

    @if($produit->description)
        <h1>Description</h1>
        <p>{{ $produit->description }}</p>
    @endif

    <h1>Indicateurs clés</h1>
    <div class="kpi-grid">
        <div class="kpi">
            <div class="label">Sous-Produits</div>
            <div class="value">{{ $stats['nb_sous_produits'] }}</div>
        </div>
        <div class="kpi">
            <div class="label">Activités totales</div>
            <div class="value">{{ $stats['nb_activites'] }}</div>
            <div class="sub">{{ $stats['activites_realisees'] }} réalisées</div>
        </div>
        <div class="kpi">
            <div class="label">En retard</div>
            <div class="value">{{ $stats['activites_retard'] }}</div>
            <div class="sub">Activités</div>
        </div>
        <div class="kpi">
            <div class="label">Taux d'exécution</div>
            <div class="value">{{ number_format($produit->taux_execution, 1) }}%</div>
            <div class="sub">Poids : {{ number_format($produit->poids, 0) }}</div>
        </div>
    </div>

    <h1>Avancement</h1>
    <table class="data">
        <tr>
            <th style="width:30%">Taux d'exécution</th>
            <td>
                <div class="progress {{ $produit->taux_execution >= 75 ? 'success' : ($produit->taux_execution >= 40 ? 'warning' : 'danger') }}">
                    <div class="fill" style="width: {{ min(100, $produit->taux_execution) }}%"></div>
                </div>
                <div class="small mt-1 strong">{{ number_format($produit->taux_execution, 2) }}%</div>
            </td>
        </tr>
    </table>

    <h1>Sous-Produits rattachés</h1>
    @forelse($produit->sousProduits as $sp)
        <h2>{{ $sp->code }} — {{ $sp->libelle }}</h2>
        <table class="data">
            <tr>
                <th style="width:20%">Statut</th>
                <td><span class="badge secondary">{{ $sp->statut }}</span></td>
                <th style="width:15%">Poids</th>
                <td class="num">{{ number_format($sp->poids, 0) }}</td>
                <th style="width:15%">Avancement</th>
                <td>
                    <div class="progress {{ $sp->taux_execution >= 75 ? 'success' : ($sp->taux_execution >= 40 ? 'warning' : 'danger') }}">
                        <div class="fill" style="width: {{ min(100, $sp->taux_execution) }}%"></div>
                    </div>
                    <div class="small">{{ number_format($sp->taux_execution, 1) }}%</div>
                </td>
            </tr>
        </table>

        @if($sp->activites->isNotEmpty())
            <table class="data mt-2">
                <thead>
                    <tr>
                        <th style="width:14%">Code</th>
                        <th>Activité</th>
                        <th style="width:10%">Statut</th>
                        <th style="width:18%">Avancement</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sp->activites as $act)
                        <tr>
                            <td class="font-mono small">{{ $act->code }}</td>
                            <td class="small">{{ \Illuminate\Support\Str::limit($act->libelle, 80) }}</td>
                            <td><span class="badge secondary">{{ $act->statut }}</span></td>
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
    @empty
        <p class="text-muted small">Aucun sous-produit défini.</p>
    @endforelse

    <div class="mt-3 small text-muted">
        Fiche produit destinée au Directeur en charge, au Commissaire de tutelle et au Secrétariat Général.
    </div>
@endsection
