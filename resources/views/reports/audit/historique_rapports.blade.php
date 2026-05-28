@extends('reports.layouts.institutional')

@section('content')
    @php
        $rapports = $donnees['rapports'];
        $stats = $donnees['stats'];
        $parCategorie = $donnees['parCategorie'];
        $filtres = $donnees['filtres'] ?? [];

        $formatTaille = function ($o) {
            if ($o < 1024) return $o . ' o';
            if ($o < 1048576) return round($o / 1024, 1) . ' ko';
            return round($o / 1048576, 1) . ' Mo';
        };
    @endphp

    <div class="metas">
        <table>
            <tr>
                <td class="label">Période couverte</td>
                <td>
                    @if($stats['periode']['debut'])
                        Du {{ \Carbon\Carbon::parse($stats['periode']['debut'])->format('d/m/Y H:i') }}
                        au {{ \Carbon\Carbon::parse($stats['periode']['fin'])->format('d/m/Y H:i') }}
                    @else
                        Aucune extraction
                    @endif
                </td>
                <td class="label">Référentiel</td>
                <td>ISO 27001 · Traçabilité documentaire</td>
            </tr>
            <tr>
                <td class="label">Filtres appliqués</td>
                <td colspan="3" class="small">
                    @php
                        $resume = [];
                        if (! empty($filtres['q'])) $resume[] = 'Recherche : « ' . $filtres['q'] . ' »';
                        if (! empty($filtres['categorie'])) $resume[] = 'Catégorie : ' . (\App\Reports\Report::CATEGORIES[$filtres['categorie']] ?? $filtres['categorie']);
                        if (! empty($filtres['date_debut'])) $resume[] = 'À partir du ' . $filtres['date_debut'];
                        if (! empty($filtres['date_fin'])) $resume[] = 'Jusqu\'au ' . $filtres['date_fin'];
                    @endphp
                    {{ empty($resume) ? 'Aucun filtre — extraction complète' : implode(' · ', $resume) }}
                </td>
            </tr>
            <tr>
                <td class="label">Confidentialité</td>
                <td colspan="3"><span class="badge danger">RESTREINT</span> Document destiné aux organes de contrôle</td>
            </tr>
        </table>
    </div>

    <h1>I. Synthèse</h1>

    <div class="kpi-grid">
        <div class="kpi">
            <div class="label">Documents générés</div>
            <div class="value">{{ $stats['total'] }}</div>
        </div>
        <div class="kpi">
            <div class="label">Téléchargements cumulés</div>
            <div class="value">{{ $stats['nb_telechargements'] }}</div>
        </div>
        <div class="kpi">
            <div class="label">Auteurs uniques</div>
            <div class="value">{{ $stats['auteurs_uniques'] }}</div>
        </div>
        <div class="kpi">
            <div class="label">Volume stocké</div>
            <div class="value" style="font-size: 12pt;">{{ $formatTaille($stats['taille_totale_octets']) }}</div>
            <div class="sub">Total fichiers PDF</div>
        </div>
    </div>

    <h1>II. Répartition par catégorie</h1>
    @if($parCategorie->isEmpty())
        <p class="text-muted small">Aucun document.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th>Catégorie</th>
                    <th class="right">Documents</th>
                    <th class="right">% du total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($parCategorie as $cat => $count)
                    <tr>
                        <td class="strong">{{ \App\Reports\Report::CATEGORIES[$cat] ?? $cat }}</td>
                        <td class="num">{{ $count }}</td>
                        <td class="num">{{ $stats['total'] > 0 ? number_format($count / $stats['total'] * 100, 1) : 0 }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h1>III. Détail des documents ({{ $rapports->count() }})</h1>

    @if($rapports->isEmpty())
        <p class="text-muted">Aucun document généré sur la période filtrée.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width:10%">Date/Heure</th>
                    <th>Titre</th>
                    <th style="width:10%">Catégorie</th>
                    <th style="width:13%">Auteur</th>
                    <th style="width:11%">Code vérif.</th>
                    <th style="width:8%" class="right">Taille</th>
                    <th style="width:6%" class="right">Téléch.</th>
                    <th style="width:10%">Hash SHA-256</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rapports as $r)
                    <tr>
                        <td class="small">{{ $r->genere_at?->format('d/m/Y H:i') }}</td>
                        <td class="small">{{ \Illuminate\Support\Str::limit($r->titre, 70) }}</td>
                        <td>
                            <span class="badge secondary">{{ $r->categorie }}</span>
                        </td>
                        <td class="small">
                            {{ $r->genereur->name ?? 'Système' }}
                            @if($r->genereur?->matricule)
                                <br><span class="font-mono small text-muted">{{ $r->genereur->matricule }}</span>
                            @endif
                        </td>
                        <td class="font-mono small">{{ $r->code_verification }}</td>
                        <td class="num small">{{ $formatTaille((int) $r->taille_octets) }}</td>
                        <td class="num">{{ $r->nb_telechargements }}</td>
                        <td class="font-mono small">{{ \Illuminate\Support\Str::limit($r->hash_sha256, 16, '…') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($rapports->count() >= 2000)
        <p class="small text-danger mt-2 strong">
            ⚠ Résultat tronqué à 2000 lignes maximum. Affinez les filtres pour obtenir une extraction complète.
        </p>
    @endif

    <h1>IV. Garantie d'intégrité</h1>
    <table class="data">
        <tr>
            <th style="width:25%">Document horodaté le</th>
            <td>{{ $genere_le->format('d/m/Y H:i:s') }}</td>
        </tr>
        <tr>
            <th>Auteur de l'extraction</th>
            <td>{{ $genere_par }}</td>
        </tr>
        <tr>
            <th>Code de vérification</th>
            <td><code class="font-mono">{{ $code_verification }}</code></td>
        </tr>
        <tr>
            <th>Conformité</th>
            <td>ISO 27001 · Traçabilité documentaire CDC Section 7.3</td>
        </tr>
        <tr>
            <th>Note légale</th>
            <td class="small">
                Chaque document recensé possède un hash SHA-256 unique stocké en base, permettant la vérification a posteriori
                de l'intégrité de tout fichier PDF produit. Le code de vérification est encodé dans le QR Code des documents émis.
            </td>
        </tr>
    </table>

    <div class="mt-3 small text-muted">
        Ce registre constitue un document probant attestant de la production documentaire institutionnelle.
        Sa modification après émission est nulle. Toute communication doit préserver son intégrité (code + QR).
    </div>
@endsection
