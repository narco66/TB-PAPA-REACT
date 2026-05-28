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
                <td class="label">Référence</td>
                <td>COBIT 2019 · CDC Section 4 (RACI)</td>
            </tr>
        </table>
    </div>

    <h1>I. Définition des rôles RACI</h1>

    <table class="data">
        <thead>
            <tr>
                <th style="width:8%">Rôle</th>
                <th style="width:20%">Définition</th>
                <th>Application TB-PAPA-CEEAC</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="strong text-primary">R - Responsible</td>
                <td>Responsable de l'exécution opérationnelle</td>
                <td>Directeur technique / Direction porteuse + Point focal opérationnel</td>
            </tr>
            <tr>
                <td class="strong text-primary">A - Accountable</td>
                <td>Approbateur final, rend compte des résultats</td>
                <td>Commissaire (Chef de Département) — un seul A par axe</td>
            </tr>
            <tr>
                <td class="strong text-primary">C - Consulted</td>
                <td>Consulté pour avis technique / sectoriel</td>
                <td>Secrétariat Général, Directions d'appui (DPPB, DSI, DCMR...)</td>
            </tr>
            <tr>
                <td class="strong text-primary">I - Informed</td>
                <td>Informé du résultat, sans pouvoir de décision</td>
                <td>Présidence, Vice-Présidence, Audit interne, PTF</td>
            </tr>
        </tbody>
    </table>

    <h1>II. Matrice RACI par axe stratégique</h1>

    <table class="data">
        <thead>
            <tr>
                <th style="width:10%">Axe</th>
                <th>Libellé</th>
                <th style="width:15%">R — Responsable</th>
                <th style="width:15%">A — Accountable</th>
                <th style="width:15%">C — Consulté</th>
                <th style="width:15%">I — Informé</th>
            </tr>
        </thead>
        <tbody>
            @forelse($axes as $axe)
                <tr>
                    <td class="strong">{{ $axe->code }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($axe->libelle, 80) }}</td>
                    <td class="small">{{ $axe->responsable->name ?? '—' }}</td>
                    <td class="small strong">{{ $axe->departement?->commissaire?->name ?? '—' }}</td>
                    <td class="small">SG · DPPB</td>
                    <td class="small">Présidence · Audit</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">Aucun axe défini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h1>III. Matrice RACI détaillée — Produits</h1>

    @foreach($axes as $axe)
        @if($axe->produits->isNotEmpty())
            <h2>{{ $axe->code }} — {{ $axe->libelle }}</h2>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:10%">Produit</th>
                        <th>Libellé</th>
                        <th style="width:18%">R — Direction porteuse</th>
                        <th style="width:18%">A — Directeur</th>
                        <th style="width:18%">C — Commissaire</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($axe->produits as $produit)
                        <tr>
                            <td class="strong">{{ $produit->code }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($produit->libelle, 60) }}</td>
                            <td class="small">{{ $produit->direction ? $produit->direction->code : '—' }}</td>
                            <td class="small strong">{{ $produit->direction?->directeur?->name ?? $produit->responsable?->name ?? '—' }}</td>
                            <td class="small">{{ $axe->departement?->commissaire?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-2"></div>
        @endif
    @endforeach

    <h1>IV. Hiérarchie institutionnelle CEEAC (rappel)</h1>
    <table class="data">
        <thead>
            <tr>
                <th style="width:8%">Niveau</th>
                <th>Rôle institutionnel</th>
                <th>Responsabilité principale</th>
            </tr>
        </thead>
        <tbody>
            <tr><td class="strong">1</td><td>Président de la Commission</td><td>Validation finale, vision stratégique</td></tr>
            <tr><td class="strong">2</td><td>Vice-Président</td><td>Coordination présidentielle, validation</td></tr>
            <tr><td class="strong">3</td><td>Commissaire (Chef de Département)</td><td>Pilotage sectoriel — Accountable des axes</td></tr>
            <tr><td class="strong">4</td><td>Secrétaire Général</td><td>Coordination administrative (non politique)</td></tr>
            <tr><td class="strong">5</td><td>Directeur technique / d'appui</td><td>Exécution — Responsible des produits</td></tr>
            <tr><td class="strong">6</td><td>Chef de service</td><td>Encadrement opérationnel</td></tr>
            <tr><td class="strong">7</td><td>Point focal</td><td>Exécution des tâches</td></tr>
        </tbody>
    </table>

    <div class="mt-3 small text-muted">
        Conforme à la Section 4 (RACI) du Cahier des Charges Fonctionnel TB-PAPA-CEEAC et au standard COBIT 2019.
    </div>
@endsection
