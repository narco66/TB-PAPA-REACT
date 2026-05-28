@extends('reports.layouts.institutional')

@section('content')
    @php
        $request = $donnees['request'] ?? null;
        $typesLabel = $donnees['types_engagement'] ?? [];
        $fmt = fn ($n) => number_format((float) $n, 2, ',', ' ');
    @endphp

    @if(!$request)
        <div class="callout danger">
            <div class="titre">Expression introuvable</div>
            Sélectionnez une expression du besoin pour générer la fiche.
        </div>
    @else
        <div class="metas">
            <table>
                <tr>
                    <td class="label">Référence</td>
                    <td><strong>{{ $request->numero }}</strong></td>
                    <td class="label">Statut</td>
                    <td><span class="badge {{ in_array($request->statut, ['valide','engage']) ? 'success' : ($request->statut === 'rejete' ? 'danger' : 'secondary') }}">{{ $request->statut }}</span></td>
                </tr>
                <tr>
                    <td class="label">Exercice</td>
                    <td>{{ $request->exercice->annee ?? '—' }}</td>
                    <td class="label">Type</td>
                    <td>{{ $typesLabel[$request->type_engagement] ?? $request->type_engagement }}</td>
                </tr>
                <tr>
                    <td class="label">Demandeur</td>
                    <td>{{ $request->demandeur->name ?? '—' }} <span class="small text-muted">{{ $request->demandeur->fonction ?? '' }}</span></td>
                    <td class="label">Priorité</td>
                    <td>{{ ['1 - Urgente', '2 - Haute', '3 - Normale', '4 - Basse'][$request->priorite - 1] ?? $request->priorite }}</td>
                </tr>
            </table>
        </div>

        <h1>I. Objet et justification</h1>
        <table class="data">
            <tr><th style="width:20%">Objet</th><td><strong>{{ $request->objet }}</strong></td></tr>
            <tr><th>Justification</th><td>{{ $request->justification }}</td></tr>
            @if($request->description_detaillee)
                <tr><th>Description détaillée</th><td class="small">{{ $request->description_detaillee }}</td></tr>
            @endif
        </table>

        <h1>II. Rattachement institutionnel</h1>
        <table class="data">
            <tr>
                <th style="width:25%">Département</th>
                <td>{{ $request->departement ? $request->departement->code . ' — ' . $request->departement->libelle : '—' }}</td>
            </tr>
            <tr>
                <th>Direction</th>
                <td>{{ $request->direction ? $request->direction->code . ' — ' . $request->direction->libelle : '—' }}</td>
            </tr>
            <tr>
                <th>Activité RBM</th>
                <td>{{ $request->activite ? $request->activite->code . ' — ' . $request->activite->libelle : '—' }}</td>
            </tr>
            <tr>
                <th>Tâche RBM</th>
                <td>{{ $request->tache ? $request->tache->code . ' — ' . $request->tache->libelle : '—' }}</td>
            </tr>
        </table>

        <h1>III. Montants estimatifs</h1>
        <table class="data">
            <tr>
                <th style="width:25%">Montant total estimé</th>
                <td class="num strong">{{ $fmt($request->montant_estime) }} {{ $request->devise }}</td>
            </tr>
            <tr>
                <th>Part CEEAC-EM</th>
                <td class="num">{{ $fmt($request->montant_estime_ceeac) }} {{ $request->devise }}</td>
            </tr>
            <tr>
                <th>Part PTF</th>
                <td class="num">{{ $fmt($request->montant_estime_ptf) }} {{ $request->devise }}</td>
            </tr>
            <tr>
                <th>Source de financement</th>
                <td>{{ $request->sourceFinancement ? $request->sourceFinancement->code . ' — ' . $request->sourceFinancement->libelle : '—' }}</td>
            </tr>
        </table>

        @if($request->supplierPressenti)
            <h1>IV. Fournisseur pressenti</h1>
            <table class="data">
                <tr><th style="width:25%">Code</th><td><strong>{{ $request->supplierPressenti->code }}</strong></td></tr>
                <tr><th>Libellé</th><td>{{ $request->supplierPressenti->libelle }}</td></tr>
                <tr><th>Type</th><td>{{ $request->supplierPressenti->type }}</td></tr>
                @if($request->supplierPressenti->nif)
                    <tr><th>NIF</th><td>{{ $request->supplierPressenti->nif }}</td></tr>
                @endif
                @if($request->supplierPressenti->rccm)
                    <tr><th>RCCM</th><td>{{ $request->supplierPressenti->rccm }}</td></tr>
                @endif
            </table>
        @endif

        <h1>V. Calendrier prévisionnel</h1>
        <table class="data">
            <tr>
                <th style="width:25%">Date besoin prévu</th>
                <td>{{ $request->date_besoin_prevu ? $request->date_besoin_prevu->format('d/m/Y') : '—' }}</td>
            </tr>
            <tr>
                <th>Date livraison souhaitée</th>
                <td>{{ $request->date_livraison_souhaitee ? $request->date_livraison_souhaitee->format('d/m/Y') : '—' }}</td>
            </tr>
        </table>

        <h1>VI. Workflow et validation</h1>
        <table class="data">
            <tr>
                <th style="width:25%">Statut actuel</th>
                <td><span class="badge {{ in_array($request->statut, ['valide','engage']) ? 'success' : ($request->statut === 'rejete' ? 'danger' : 'info') }}">{{ $request->statut }}</span></td>
            </tr>
            <tr>
                <th>Valideur hiérarchique</th>
                <td>{{ $request->valideurHierarchique->name ?? '—' }} <span class="small text-muted">{{ $request->valideurHierarchique->fonction ?? '' }}</span></td>
            </tr>
            <tr>
                <th>Date de validation</th>
                <td>{{ $request->valide_at ? $request->valide_at->format('d/m/Y H:i') : '—' }}</td>
            </tr>
            @if($request->motif_decision)
                <tr><th>Motif de décision</th><td class="small">{{ $request->motif_decision }}</td></tr>
            @endif
            @if($request->engagement)
                <tr>
                    <th>Engagement créé</th>
                    <td><strong>{{ $request->engagement->reference }}</strong> — {{ $fmt($request->engagement->montant) }} {{ $request->devise }} le {{ $request->engagement->date_mouvement?->format('d/m/Y') }}</td>
                </tr>
            @endif
        </table>

        <div class="signatures mt-3">
            <div class="sig">
                <div class="role">Demandeur</div>
                <div class="nom">{{ $request->demandeur->name ?? '_______________________' }}</div>
            </div>
            <div class="sig">
                <div class="role">Valideur hiérarchique</div>
                <div class="nom">{{ $request->valideurHierarchique->name ?? '_______________________' }}</div>
            </div>
            <div class="sig">
                <div class="role">Date</div>
                <div class="nom">{{ $request->valide_at?->format('d/m/Y') ?? '___________' }}</div>
            </div>
        </div>

        <div class="callout mt-3 small">
            <div class="titre">Conformité</div>
            Document conforme au Règlement Général de Comptabilité Publique (RGCP), au système OHADA applicable en Afrique centrale, et aux principes COSO ERM de contrôle interne (séparation des fonctions). Hash SHA-256 garantissant l'intégrité du document.
        </div>
    @endif
@endsection
