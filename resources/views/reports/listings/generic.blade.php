@extends('reports.layouts.institutional')

@section('content')
    @php
        $columns = $donnees['columns'] ?? [];
        $rows = $donnees['rows'] ?? collect();
        $count = $donnees['count'] ?? 0;
        $filtres = $donnees['filtres_appliques'] ?? [];

        $statutColors = [
            'valide' => 'success', 'cloture' => 'success', 'realisee' => 'success', 'verifiee' => 'success', 'Actif' => 'success', 'mise_en_oeuvre' => 'success', 'approuve' => 'success', 'resolue' => 'success',
            'en_cours' => 'info', 'soumis' => 'info', 'planifiee' => 'info', 'en_validation' => 'info', 'projet_rapport' => 'info', 'lettre_emise' => 'info', 'execute' => 'info',
            'brouillon' => 'secondary', 'projet' => 'secondary', 'Inactif' => 'secondary', 'archive' => 'secondary', 'ouverte' => 'secondary',
            'rejete' => 'danger', 'rejetee' => 'danger', 'annulee' => 'danger', 'critique' => 'danger', 'urgente' => 'danger', 'suspendue' => 'danger', 'echec' => 'danger',
            'attention' => 'warning', 'moyenne' => 'warning', 'eleve' => 'warning', 'haute' => 'warning', 'majeur' => 'warning', 'a_corriger' => 'warning',
            'info' => 'info', 'basse' => 'info', 'mineur' => 'info', 'faible' => 'info',
        ];
        $colorOf = fn ($v) => $statutColors[strtolower((string) $v)] ?? 'secondary';
    @endphp

    <div class="metas">
        <table>
            <tr>
                <td class="label">Type de liste</td>
                <td><strong>{{ $donnees['titreListe'] ?? $rapport['titre'] }}</strong></td>
                <td class="label">Volume</td>
                <td><strong>{{ $count }}</strong> ligne{{ $count > 1 ? 's' : '' }}</td>
            </tr>
            @if(!empty($filtres))
                <tr>
                    <td class="label">Filtres appliqués</td>
                    <td colspan="3" class="small">
                        @foreach($filtres as $k => $v)
                            @if($v !== null && $v !== '' && $v !== false)
                                <span class="badge info">{{ $k }} = {{ is_scalar($v) ? $v : json_encode($v) }}</span>
                            @endif
                        @endforeach
                    </td>
                </tr>
            @endif
        </table>
    </div>

    @if($count === 0)
        <div class="callout">
            <div class="titre">Liste vide</div>
            Aucune ligne ne correspond aux filtres appliqués. Modifiez les critères de recherche ou consultez la page source dans l'application.
        </div>
    @else
        <table class="data">
            <thead>
                <tr>
                    @foreach($columns as $col)
                        <th @if(!empty($col['width'])) style="width:{{ $col['width'] }}" @endif
                            @if(($col['align'] ?? '') === 'right') class="right"
                            @elseif(($col['align'] ?? '') === 'center') class="center"
                            @endif>{{ $col['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        @foreach($columns as $col)
                            @php
                                $key = $col['key'];
                                $kind = $col['kind'] ?? 'text';
                                $val = $row[$key] ?? '—';
                                $align = $col['align'] ?? null;
                            @endphp
                            <td @if($align === 'right') class="right"
                                @elseif($align === 'center') class="center"
                                @elseif($kind === 'num') class="num"
                                @endif>
                                @switch($kind)
                                    @case('badge')
                                        @if($val !== null && $val !== '—')
                                            <span class="badge {{ $colorOf($val) }}">{{ $val }}</span>
                                        @else —
                                        @endif
                                        @break
                                    @case('progress')
                                        @php($pct = (float) $val)
                                        <div class="progress {{ $pct >= 75 ? 'success' : ($pct >= 40 ? 'warning' : 'danger') }}">
                                            <div class="fill" style="width: {{ min(100, $pct) }}%"></div>
                                        </div>
                                        <div class="small">{{ number_format($pct, 1) }}%</div>
                                        @break
                                    @case('num')
                                        {{ is_numeric($val) ? number_format((float) $val, 0, ',', ' ') : $val }}
                                        @break
                                    @case('date')
                                        {{ $val instanceof \DateTimeInterface ? $val->format('d/m/Y') : $val }}
                                        @break
                                    @default
                                        {{ $val }}
                                @endswitch
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="small text-muted mt-3">
            Liste limitée par garde-fou applicatif. Pour une vue exhaustive, consultez la page correspondante dans TB-PAPA-CEEAC.
            Document généré le {{ $genere_le->format('d/m/Y à H:i') }} par {{ $genere_par }}.
        </p>
    @endif
@endsection
