@extends('reports.layouts.institutional')

@section('content')
    @php
        $papa = $donnees['papa'];
        $axes = $donnees['axes'];
    @endphp

    <div class="metas">
        <table>
            <tr>
                <td class="label">PAPA</td>
                <td><strong>{{ $papa->annee }}</strong> — {{ $papa->libelle }}</td>
                <td class="label">Chaîne RBM officielle CEEAC</td>
                <td>Axe → Produit → Sous-Produit → Activité → Tâche</td>
            </tr>
        </table>
    </div>

    <h1>Matrice consolidée de la chaîne RBM/GAR</h1>

    <p class="small text-muted">
        Cette matrice présente, pour chaque axe du PAPA {{ $papa->annee }}, la décomposition hiérarchique complète des
        Produits, Sous-Produits et Activités avec leurs taux d'exécution consolidés bottom-up.
    </p>

    @foreach($axes as $axe)
        @php $nbActivites = $axe->produits->sum(fn($p) => $p->sousProduits->sum(fn($sp) => $sp->activites->count())); @endphp

        <h2 style="background: #1e5cb3; color: white; padding: 6px 10px; margin-top: 18px;">
            {{ $axe->code }} — {{ $axe->libelle }}
            <span style="float: right; font-size: 10pt;">
                Avancement : {{ number_format($axe->taux_execution, 1) }}%  ·  Poids : {{ number_format($axe->poids, 0) }}
            </span>
        </h2>

        @if($axe->produits->isEmpty())
            <p class="small text-muted">Aucun produit défini.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:8%">Code</th>
                        <th>Libellé</th>
                        <th style="width:8%" class="right">Poids</th>
                        <th style="width:15%">Avancement</th>
                        <th style="width:8%" class="right">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($axe->produits as $produit)
                        <tr style="background: #dbeafe;">
                            <td class="strong">{{ $produit->code }}</td>
                            <td class="strong">{{ $produit->libelle }}</td>
                            <td class="num">{{ number_format($produit->poids, 0) }}</td>
                            <td>
                                <div class="progress {{ $produit->taux_execution >= 75 ? 'success' : ($produit->taux_execution >= 40 ? 'warning' : 'danger') }}">
                                    <div class="fill" style="width: {{ min(100, $produit->taux_execution) }}%"></div>
                                </div>
                                <div class="small">{{ number_format($produit->taux_execution, 1) }}%</div>
                            </td>
                            <td><span class="badge secondary">{{ $produit->statut }}</span></td>
                        </tr>

                        @foreach($produit->sousProduits as $sp)
                            <tr style="background: #fef3c7;">
                                <td class="strong" style="padding-left: 18px;">{{ $sp->code }}</td>
                                <td style="padding-left: 18px;"><em>{{ $sp->libelle }}</em></td>
                                <td class="num">{{ number_format($sp->poids, 0) }}</td>
                                <td>
                                    <div class="progress {{ $sp->taux_execution >= 75 ? 'success' : ($sp->taux_execution >= 40 ? 'warning' : 'danger') }}">
                                        <div class="fill" style="width: {{ min(100, $sp->taux_execution) }}%"></div>
                                    </div>
                                    <div class="small">{{ number_format($sp->taux_execution, 1) }}%</div>
                                </td>
                                <td class="small">{{ $sp->statut }}</td>
                            </tr>

                            @foreach($sp->activites as $act)
                                <tr>
                                    <td style="padding-left: 36px;">{{ $act->code }}</td>
                                    <td style="padding-left: 36px;" class="small">{{ $act->libelle }}</td>
                                    <td class="num small">{{ number_format($act->poids, 0) }}</td>
                                    <td>
                                        <div class="progress {{ $act->taux_execution >= 75 ? 'success' : ($act->taux_execution >= 40 ? 'warning' : 'danger') }}">
                                            <div class="fill" style="width: {{ min(100, $act->taux_execution) }}%"></div>
                                        </div>
                                        <div class="small">{{ number_format($act->taux_execution, 1) }}%</div>
                                    </td>
                                    <td class="small">
                                        @if($act->est_jalon) ◆ @endif
                                        {{ $act->statut }}
                                    </td>
                                </tr>
                                @foreach($act->taches as $tache)
                                    <tr style="background: #f3f4f6;">
                                        <td style="padding-left: 54px;" class="small text-muted">{{ $tache->code }}</td>
                                        <td style="padding-left: 54px;" class="small text-muted">{{ $tache->libelle }}</td>
                                        <td class="num small">{{ number_format($tache->poids, 0) }}</td>
                                        <td>
                                            <div class="progress" style="height: 5px;">
                                                <div class="fill" style="width: {{ min(100, $tache->taux_execution) }}%"></div>
                                            </div>
                                            <div class="small">{{ number_format($tache->taux_execution, 1) }}%</div>
                                        </td>
                                        <td class="small">{{ $tache->statut }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <h1 class="mt-3">Légende</h1>
    <table class="data">
        <tr>
            <td style="background: #dbeafe; width:15%" class="strong">Niveau Produit</td>
            <td style="background: #fef3c7; width:15%" class="strong"><em>Niveau Sous-Produit</em></td>
            <td style="width:15%">Niveau Activité</td>
            <td style="background: #f3f4f6;" class="small text-muted">Niveau Tâche</td>
        </tr>
    </table>

    <div class="mt-3 small text-muted">
        Les taux d'exécution sont consolidés bottom-up (Tâche → Activité → Sous-Produit → Produit → Axe) selon la moyenne pondérée par le poids des enfants.
        Les codes suivent la nomenclature officielle de la chaîne RBM/GAR de la Commission de la CEEAC.
    </div>
@endsection
