@extends('reports.layouts.institutional')

@section('content')
    @php $t = $donnees['tache']; @endphp

    <div class="metas">
        <table>
            <tr>
                <td class="label">Tâche</td>
                <td><strong>{{ $t->code }} — {{ $t->libelle }}</strong></td>
                <td class="label">PAPA</td>
                <td>{{ $t->activite->sousProduit->produit->axe->papa->annee }}</td>
            </tr>
            <tr>
                <td class="label">Chaîne RBM complète</td>
                <td colspan="3" class="small">
                    {{ $t->activite->sousProduit->produit->axe->code }} →
                    {{ $t->activite->sousProduit->produit->code }} →
                    {{ $t->activite->sousProduit->code }} →
                    {{ $t->activite->code }} →
                    <strong>{{ $t->code }}</strong>
                </td>
            </tr>
            <tr>
                <td class="label">Statut</td>
                <td><span class="badge {{ $t->statut === 'realisee' ? 'success' : ($t->statut === 'en_cours' ? 'info' : 'secondary') }}">{{ $t->statut }}</span></td>
                <td class="label">Poids</td>
                <td class="num">{{ number_format($t->poids, 0) }}</td>
            </tr>
            <tr>
                <td class="label">Responsable hiérarchique</td>
                <td>{{ $t->responsable->name ?? '—' }}</td>
                <td class="label">Assignée à</td>
                <td>{{ $t->assigneA->name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Date début</td>
                <td>{{ optional($t->date_debut)->format('d/m/Y') ?? '—' }}</td>
                <td class="label">Date fin</td>
                <td>{{ optional($t->date_fin)->format('d/m/Y') ?? '—' }}</td>
            </tr>
        </table>
    </div>

    @if($t->description)
        <h1>Description</h1>
        <p>{{ $t->description }}</p>
    @endif

    <h1>Exécution</h1>
    <table class="data">
        <tr>
            <th style="width:30%">Taux d'exécution</th>
            <td>
                <div class="progress {{ $t->taux_execution >= 75 ? 'success' : ($t->taux_execution >= 40 ? 'warning' : 'danger') }}">
                    <div class="fill" style="width: {{ min(100, $t->taux_execution) }}%"></div>
                </div>
                <div class="small mt-1 strong">{{ number_format($t->taux_execution, 2) }}%</div>
            </td>
        </tr>
    </table>

    <h1>Activité parente</h1>
    <table class="data">
        <tr>
            <th style="width:20%">Code</th>
            <td>{{ $t->activite->code }}</td>
        </tr>
        <tr>
            <th>Libellé</th>
            <td>{{ $t->activite->libelle }}</td>
        </tr>
    </table>

    <div class="mt-3 small text-muted">
        Fiche détaillée pour suivi opérationnel quotidien. Toute modification est tracée dans le journal d'audit.
    </div>
@endsection
