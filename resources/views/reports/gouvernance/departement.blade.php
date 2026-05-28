@extends('reports.layouts.institutional')

@section('content')
    @php $d = $donnees['departement']; $stats = $donnees['stats']; @endphp

    <div class="metas">
        <table>
            <tr>
                <td class="label">Département</td>
                <td><strong>{{ $d->code }}</strong></td>
                <td class="label">Libellé</td>
                <td>{{ $d->libelle }}</td>
            </tr>
            <tr>
                <td class="label">État</td>
                <td><span class="badge {{ $d->actif ? 'success' : 'secondary' }}">{{ $d->actif ? 'Actif' : 'Inactif' }}</span></td>
                <td class="label">Ordre</td>
                <td class="num">{{ $d->ordre }}</td>
            </tr>
        </table>
    </div>

    @if($d->description)
        <h1>Mandat sectoriel</h1>
        <p>{{ $d->description }}</p>
    @endif

    <h1>Volumétrie</h1>
    <div class="kpi-grid">
        <div class="kpi">
            <div class="label">Directions</div>
            <div class="value">{{ $stats['nb_directions'] }}</div>
            <div class="sub">{{ $stats['nb_directions_techniques'] }} techn. · {{ $stats['nb_directions_appui'] }} appui</div>
        </div>
        <div class="kpi">
            <div class="label">Axes RBM portés</div>
            <div class="value">{{ $stats['nb_axes'] }}</div>
        </div>
        <div class="kpi">
            <div class="label">Exécution moyenne</div>
            <div class="value">{{ number_format($stats['taux_execution_moyen'], 1) }}%</div>
            <div class="sub">Moyenne des axes</div>
        </div>
        <div class="kpi">
            <div class="label">Commissaire</div>
            <div class="value">{{ $d->commissaire ? '1' : '0' }}</div>
            <div class="sub">{{ $d->commissaire ? 'Désigné' : 'Non désigné' }}</div>
        </div>
    </div>

    <h1>Commissaire en charge</h1>
    @if($d->commissaire)
        <table class="data">
            <tr>
                <th style="width:25%">Nom</th>
                <td>{{ $d->commissaire->name }}</td>
            </tr>
            <tr>
                <th>Fonction</th>
                <td>{{ $d->commissaire->fonction ?? '—' }}</td>
            </tr>
            <tr>
                <th>Email institutionnel</th>
                <td>{{ $d->commissaire->email }}</td>
            </tr>
            <tr>
                <th>Matricule</th>
                <td class="font-mono">{{ $d->commissaire->matricule ?? '—' }}</td>
            </tr>
        </table>
    @else
        <p class="text-muted small">Aucun commissaire désigné pour ce département.</p>
    @endif

    <h1>Directions rattachées ({{ $stats['nb_directions'] }})</h1>
    @if($d->directions->isEmpty())
        <p class="text-muted small">Aucune direction rattachée.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width:10%">Code</th>
                    <th>Libellé</th>
                    <th style="width:14%">Type</th>
                    <th style="width:18%">Directeur</th>
                    <th style="width:8%">État</th>
                </tr>
            </thead>
            <tbody>
                @foreach($d->directions as $dir)
                    <tr>
                        <td class="font-mono small">{{ $dir->code }}</td>
                        <td class="small">{{ $dir->libelle }}</td>
                        <td>
                            <span class="badge {{ $dir->type === 'technique' ? 'info' : 'secondary' }}">
                                {{ $dir->type === 'technique' ? 'Technique' : 'Appui & soutien' }}
                            </span>
                        </td>
                        <td class="small">{{ $dir->directeur->name ?? '—' }}</td>
                        <td><span class="badge {{ $dir->actif ? 'success' : 'secondary' }}">{{ $dir->actif ? 'Actif' : 'Inactif' }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h1>Axes stratégiques portés ({{ $stats['nb_axes'] }})</h1>
    @if($d->axes->isEmpty())
        <p class="text-muted small">Aucun axe défini pour ce département.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width:10%">Code</th>
                    <th>Libellé</th>
                    <th style="width:10%">PAPA</th>
                    <th style="width:8%">Statut</th>
                    <th style="width:18%">Avancement</th>
                </tr>
            </thead>
            <tbody>
                @foreach($d->axes as $axe)
                    <tr>
                        <td class="font-mono small strong">{{ $axe->code }}</td>
                        <td class="small">{{ \Illuminate\Support\Str::limit($axe->libelle, 80) }}</td>
                        <td class="small">{{ $axe->papa->annee }}</td>
                        <td><span class="badge secondary">{{ $axe->statut }}</span></td>
                        <td>
                            <div class="progress {{ $axe->taux_execution >= 75 ? 'success' : ($axe->taux_execution >= 40 ? 'warning' : 'danger') }}">
                                <div class="fill" style="width: {{ min(100, $axe->taux_execution) }}%"></div>
                            </div>
                            <div class="small">{{ number_format($axe->taux_execution, 1) }}%</div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="mt-3 small text-muted">
        Fiche destinée au Commissaire en charge, à la Présidence et au Secrétariat Général.
    </div>
@endsection
