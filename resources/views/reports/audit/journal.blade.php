@extends('reports.layouts.institutional')

@section('content')
    @php
        $logs = $donnees['logs'];
        $stats = $donnees['stats'];
        $filtres = $donnees['filtres'];
        $libellesEvents = ['created' => 'Création', 'updated' => 'Modification', 'deleted' => 'Suppression', 'restored' => 'Restauration'];
    @endphp

    <div class="metas">
        <table>
            <tr>
                <td class="label">Période</td>
                <td>
                    {{ $filtres['date_debut'] ?? 'Origine' }} → {{ $filtres['date_fin'] ?? now()->format('Y-m-d') }}
                </td>
                <td class="label">Référentiel</td>
                <td>ISO 27001 · COSO IT</td>
            </tr>
            <tr>
                <td class="label">Confidentialité</td>
                <td colspan="3"><span class="badge danger">RESTREINT</span> Document destiné aux organes de contrôle</td>
            </tr>
        </table>
    </div>

    <h1>I. Statistiques de l'extrait</h1>

    <div class="kpi-grid">
        <div class="kpi">
            <div class="label">Total événements</div>
            <div class="value">{{ $stats['total'] }}</div>
        </div>
        <div class="kpi">
            <div class="label">Créations</div>
            <div class="value">{{ $stats['created'] }}</div>
        </div>
        <div class="kpi">
            <div class="label">Modifications</div>
            <div class="value">{{ $stats['updated'] }}</div>
        </div>
        <div class="kpi">
            <div class="label">Suppressions</div>
            <div class="value">{{ $stats['deleted'] }}</div>
        </div>
    </div>

    <p class="small text-muted">
        <strong>{{ $stats['auteurs_uniques'] }}</strong> auteur(s) unique(s) identifié(s) sur cette période.
    </p>

    <h1>II. Détail des événements ({{ $logs->count() }} entrées)</h1>

    @if($logs->isEmpty())
        <p class="text-muted">Aucun événement à signaler sur la période sélectionnée.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width:10%">Date/Heure</th>
                    <th style="width:13%">Acteur</th>
                    <th style="width:8%">Matricule</th>
                    <th style="width:8%">Événement</th>
                    <th style="width:10%">Objet</th>
                    <th style="width:6%">ID</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td class="small">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="small">{{ $log->causer->name ?? 'Système' }}</td>
                        <td class="small">{{ $log->causer->matricule ?? '—' }}</td>
                        <td>
                            @if($log->event)
                                <span class="badge {{ $log->event === 'created' ? 'success' : ($log->event === 'deleted' ? 'danger' : 'info') }}">
                                    {{ $libellesEvents[$log->event] ?? $log->event }}
                                </span>
                            @endif
                        </td>
                        <td class="small">{{ $log->subject_type ? class_basename($log->subject_type) : '—' }}</td>
                        <td class="small">#{{ $log->subject_id ?? '—' }}</td>
                        <td class="small">{{ \Illuminate\Support\Str::limit($log->description, 80) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h1>III. Garantie d'intégrité</h1>
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
            <td><code>{{ $code_verification }}</code></td>
        </tr>
        <tr>
            <th>Conformité</th>
            <td>ISO 27001 (sécurité de l'information) · COSO ERM · COBIT 2019</td>
        </tr>
        <tr>
            <th>Caractère immuable</th>
            <td>Les entrées du journal d'audit ne peuvent être ni modifiées ni supprimées dans le système source.</td>
        </tr>
    </table>

    <div class="mt-3 small text-muted">
        Cet extrait constitue une preuve documentaire opposable. Sa modification après émission est juridiquement nulle.
        Toute communication doit en préserver l'intégrité (code de vérification + QR Code).
    </div>
@endsection
