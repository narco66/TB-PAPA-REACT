@extends('reports.layouts.institutional')

@section('content')
    @php $a = $donnees['activite']; @endphp

    <div class="metas">
        <table>
            <tr>
                <td class="label">Activité</td>
                <td><strong>{{ $a->code }} — {{ $a->libelle }}</strong></td>
                <td class="label">PAPA</td>
                <td>{{ $a->sousProduit->produit->axe->papa->annee }}</td>
            </tr>
            <tr>
                <td class="label">Hiérarchie RBM</td>
                <td colspan="3">
                    {{ $a->sousProduit->produit->axe->code }} →
                    {{ $a->sousProduit->produit->code }} →
                    {{ $a->sousProduit->code }} →
                    <strong>{{ $a->code }}</strong>
                </td>
            </tr>
            <tr>
                <td class="label">Direction</td>
                <td>{{ $a->direction ? $a->direction->code : '—' }}</td>
                <td class="label">Statut</td>
                <td><span class="badge {{ $a->statut === 'realisee' ? 'success' : ($a->statut === 'en_cours' ? 'info' : 'secondary') }}">{{ $a->statut }}</span></td>
            </tr>
            <tr>
                <td class="label">Responsable</td>
                <td>{{ $a->responsable->name ?? '—' }}</td>
                <td class="label">Point focal</td>
                <td>{{ $a->pointFocal->name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Niveau de risque</td>
                <td>
                    <span class="badge {{ $a->niveau_risque === 'critique' ? 'danger' : ($a->niveau_risque === 'eleve' ? 'warning' : 'secondary') }}">
                        {{ $a->niveau_risque }}
                    </span>
                </td>
                <td class="label">Jalon</td>
                <td>{{ $a->est_jalon ? '◆ Oui' : 'Non' }}</td>
            </tr>
        </table>
    </div>

    @if($a->description)
        <h1>Description</h1>
        <p>{{ $a->description }}</p>
    @endif

    <h1>Calendrier d'exécution</h1>
    <table class="data">
        <tr>
            <th style="width:25%">Date début prévue</th>
            <td>{{ optional($a->date_debut)->format('d/m/Y') ?? '—' }}</td>
            <th style="width:25%">Date début réelle</th>
            <td>{{ optional($a->date_debut_reelle)->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <th>Date fin prévue</th>
            <td>{{ optional($a->date_fin)->format('d/m/Y') ?? '—' }}</td>
            <th>Date fin réelle</th>
            <td>{{ optional($a->date_fin_reelle)->format('d/m/Y') ?? '—' }}</td>
        </tr>
    </table>

    <h1>Avancement physique</h1>
    <table class="data">
        <tr>
            <th style="width:30%">Taux d'exécution</th>
            <td>
                <div class="progress {{ $a->taux_execution >= 75 ? 'success' : ($a->taux_execution >= 40 ? 'warning' : 'danger') }}">
                    <div class="fill" style="width: {{ min(100, $a->taux_execution) }}%"></div>
                </div>
                <div class="small mt-1 strong">{{ number_format($a->taux_execution, 2) }}% (poids : {{ number_format($a->poids, 0) }})</div>
            </td>
        </tr>
    </table>

    <h1>Tâches associées ({{ $a->taches->count() }})</h1>
    @if($a->taches->isEmpty())
        <p class="text-muted small">Aucune tâche définie.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width:12%">Code</th>
                    <th>Libellé</th>
                    <th style="width:14%">Période</th>
                    <th style="width:14%">Assignée à</th>
                    <th style="width:10%">Statut</th>
                    <th style="width:16%">Exécution</th>
                </tr>
            </thead>
            <tbody>
                @foreach($a->taches as $t)
                    <tr>
                        <td class="font-mono small">{{ $t->code }}</td>
                        <td class="small">{{ \Illuminate\Support\Str::limit($t->libelle, 60) }}</td>
                        <td class="small">{{ optional($t->date_debut)->format('d/m/y') }}→{{ optional($t->date_fin)->format('d/m/y') }}</td>
                        <td class="small">{{ $t->assigneA->name ?? '—' }}</td>
                        <td><span class="badge secondary">{{ $t->statut }}</span></td>
                        <td>
                            <div class="progress {{ $t->taux_execution >= 75 ? 'success' : ($t->taux_execution >= 40 ? 'warning' : 'danger') }}">
                                <div class="fill" style="width: {{ min(100, $t->taux_execution) }}%"></div>
                            </div>
                            <div class="small">{{ number_format($t->taux_execution, 1) }}%</div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="mt-3 small text-muted">
        Fiche destinée au Point focal, Responsable et Directeur porteur.
    </div>
@endsection
