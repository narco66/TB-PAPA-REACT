<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $rapport['titre'] }} — CEEAC</title>
    <style>
        @page {
            margin: 110px 36px 80px 36px;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5pt;
            color: #1f2937;
            line-height: 1.4;
        }
        /* Header institutionnel */
        header {
            position: fixed;
            top: -90px;
            left: 0;
            right: 0;
            height: 80px;
            border-bottom: 3px solid #1e5cb3;
            padding-bottom: 8px;
        }
        header .marque {
            display: table;
            width: 100%;
        }
        header .logo-cell, header .titre-cell, header .meta-cell {
            display: table-cell;
            vertical-align: middle;
        }
        header .logo-cell {
            width: 60px;
        }
        header .logo {
            width: 56px;
            height: 56px;
            background: #1e5cb3;
            color: white;
            text-align: center;
            line-height: 56px;
            font-size: 18pt;
            font-weight: bold;
            border-radius: 6px;
        }
        header .titre-cell {
            padding-left: 12px;
        }
        header .nom-institution {
            font-size: 9pt;
            color: #1e5cb3;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        header .nom-systeme {
            font-size: 7.5pt;
            color: #6b7280;
            margin-top: 2px;
        }
        header .meta-cell {
            text-align: right;
            width: 200px;
            font-size: 7.5pt;
            color: #6b7280;
        }
        header .meta-cell strong {
            color: #1e5cb3;
        }

        /* Footer institutionnel */
        footer {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            height: 50px;
            border-top: 2px solid #e5e7eb;
            padding-top: 8px;
            font-size: 7pt;
            color: #6b7280;
        }
        footer .ligne {
            display: table;
            width: 100%;
        }
        footer .gauche, footer .centre, footer .droite {
            display: table-cell;
            width: 33%;
        }
        footer .centre {
            text-align: center;
            font-weight: bold;
            color: #1e5cb3;
        }
        footer .droite {
            text-align: right;
        }
        footer .code-verif {
            font-family: monospace;
            font-size: 6.5pt;
        }
        footer .page-num:after {
            content: counter(page) " / " counter(pages);
        }

        /* Titre du document */
        .doc-titre {
            font-size: 18pt;
            color: #1e5cb3;
            font-weight: bold;
            margin-bottom: 4px;
            padding-bottom: 8px;
            border-bottom: 2px solid #1e5cb3;
        }
        .doc-sous-titre {
            font-size: 10pt;
            color: #6b7280;
            margin-bottom: 16px;
        }
        .doc-categorie {
            display: inline-block;
            background: #1e5cb3;
            color: white;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Métadonnées */
        .metas {
            background: #f9fafb;
            border-left: 4px solid #1e5cb3;
            padding: 10px 14px;
            margin: 16px 0;
            font-size: 8.5pt;
        }
        .metas table {
            width: 100%;
            border-collapse: collapse;
        }
        .metas td {
            padding: 2px 6px;
            vertical-align: top;
        }
        .metas td.label {
            font-weight: bold;
            color: #6b7280;
            width: 130px;
            text-transform: uppercase;
            font-size: 7.5pt;
        }

        /* Sections */
        h1 {
            font-size: 13pt;
            color: #1e5cb3;
            margin: 18px 0 8px 0;
            padding-bottom: 4px;
            border-bottom: 1px solid #cbd5e1;
        }
        h2 {
            font-size: 11pt;
            color: #1e5cb3;
            margin: 14px 0 6px 0;
        }
        h3 {
            font-size: 10pt;
            color: #374151;
            margin: 10px 0 4px 0;
        }

        /* Tableaux */
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 8.5pt;
        }
        table.data th {
            background: #1e5cb3;
            color: white;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #1e5cb3;
            text-transform: uppercase;
            font-size: 7.5pt;
            letter-spacing: 0.3px;
        }
        table.data td {
            padding: 5px 8px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }
        table.data tr:nth-child(even) td {
            background: #f9fafb;
        }
        table.data .right { text-align: right; }
        table.data .center { text-align: center; }
        table.data .num {
            font-family: monospace;
            text-align: right;
            font-size: 8pt;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge.success { background: #dcfce7; color: #166534; }
        .badge.warning { background: #fef3c7; color: #92400e; }
        .badge.danger { background: #fee2e2; color: #991b1b; }
        .badge.info { background: #dbeafe; color: #1e40af; }
        .badge.secondary { background: #e5e7eb; color: #374151; }

        /* KPI cards */
        .kpi-grid {
            display: table;
            width: 100%;
            border-spacing: 8px;
            margin: 12px -8px;
        }
        .kpi {
            display: table-cell;
            background: #f9fafb;
            border-left: 3px solid #1e5cb3;
            padding: 10px;
            width: 25%;
        }
        .kpi .label {
            font-size: 7pt;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: bold;
        }
        .kpi .value {
            font-size: 16pt;
            color: #1e5cb3;
            font-weight: bold;
            margin-top: 4px;
        }
        .kpi .sub {
            font-size: 7.5pt;
            color: #6b7280;
            margin-top: 2px;
        }

        /* Barre de progression */
        .progress {
            background: #e5e7eb;
            border-radius: 2px;
            height: 8px;
            position: relative;
            overflow: hidden;
        }
        .progress .fill {
            background: #1e5cb3;
            height: 100%;
        }
        .progress.success .fill { background: #16a34a; }
        .progress.warning .fill { background: #f59e0b; }
        .progress.danger .fill { background: #dc2626; }

        /* Filigrane */
        .watermark {
            position: fixed;
            top: 40%;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 90pt;
            color: rgba(30, 92, 179, 0.05);
            font-weight: bold;
            transform: rotate(-25deg);
            z-index: -1;
            text-transform: uppercase;
        }

        /* QR Code dans le footer */
        .qr-block {
            text-align: center;
            margin-top: 20px;
            padding: 12px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        .qr-block img {
            width: 80px;
            height: 80px;
        }
        .qr-block .code {
            font-family: monospace;
            font-size: 8pt;
            color: #1e5cb3;
            margin-top: 4px;
        }

        /* Utilities */
        .text-muted { color: #6b7280; }
        .text-primary { color: #1e5cb3; }
        .text-success { color: #16a34a; }
        .text-warning { color: #f59e0b; }
        .text-danger { color: #dc2626; }
        .small { font-size: 7.5pt; }
        .strong { font-weight: bold; }
        .mt-1 { margin-top: 4px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 16px; }
        .mt-4 { margin-top: 24px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 16px; }
        .page-break { page-break-after: always; }

        /* Encadrés */
        .callout {
            border-left: 4px solid #1e5cb3;
            background: #eff6ff;
            padding: 10px 14px;
            margin: 10px 0;
            font-size: 8.5pt;
        }
        .callout.success { border-left-color: #16a34a; background: #f0fdf4; }
        .callout.warning { border-left-color: #f59e0b; background: #fffbeb; }
        .callout.danger { border-left-color: #dc2626; background: #fef2f2; }
        .callout .titre {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.5pt;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        /* Cover page */
        .cover {
            min-height: 700px;
            text-align: center;
            padding-top: 80px;
        }
        .cover .cover-logo {
            width: 110px;
            height: 110px;
            margin: 0 auto 24px auto;
        }
        .cover .cover-titre {
            font-size: 26pt;
            color: #1e5cb3;
            font-weight: bold;
            line-height: 1.2;
            margin-bottom: 8px;
        }
        .cover .cover-sous {
            font-size: 13pt;
            color: #6b7280;
            margin-bottom: 28px;
        }
        .cover .cover-meta {
            display: inline-block;
            text-align: left;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 16px 24px;
            margin: 20px auto;
            font-size: 9pt;
        }
        .cover .cover-meta strong { color: #1e5cb3; }
        .cover .classification {
            display: inline-block;
            background: #dc2626;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 6px 16px;
            font-size: 9pt;
            margin-top: 20px;
        }
        .cover .classification.normal {
            background: #1e5cb3;
        }

        /* Signature blocks */
        .signatures {
            display: table;
            width: 100%;
            margin-top: 30px;
            border-spacing: 12px;
        }
        .signatures .sig {
            display: table-cell;
            border-top: 1px solid #1e5cb3;
            padding-top: 8px;
            text-align: center;
            font-size: 8pt;
            width: 33%;
        }
        .signatures .sig .role {
            font-weight: bold;
            color: #1e5cb3;
            text-transform: uppercase;
            font-size: 7.5pt;
            letter-spacing: 0.5px;
        }
        .signatures .sig .nom {
            margin-top: 30px;
            font-size: 9pt;
        }

        /* Stat row inline */
        .stat-row {
            display: table;
            width: 100%;
            margin: 8px 0;
            border-spacing: 6px;
        }
        .stat-row .item {
            display: table-cell;
            background: #f9fafb;
            padding: 8px 10px;
            border-left: 2px solid #1e5cb3;
            font-size: 8.5pt;
        }
        .stat-row .item .l {
            font-size: 7pt;
            color: #6b7280;
            text-transform: uppercase;
        }
        .stat-row .item .v {
            font-size: 12pt;
            font-weight: bold;
            color: #1f2937;
        }

        /* Trafic light */
        .traffic {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            vertical-align: middle;
            margin-right: 4px;
        }
        .traffic.success { background: #16a34a; }
        .traffic.warning { background: #f59e0b; }
        .traffic.danger { background: #dc2626; }
    </style>
</head>
<body>
    <header>
        <div class="marque">
            <div class="logo-cell">
                @if(!empty($logo_ceeac))
                    <img src="{{ $logo_ceeac }}" alt="CEEAC" width="56" height="56" style="background:#fff;border:1px solid #e5e7eb;">
                @else
                    <div class="logo">TB</div>
                @endif
            </div>
            <div class="titre-cell">
                <div class="nom-institution">{{ $institution['abreviation'] }} — {{ $institution['nom'] }}</div>
                <div class="nom-systeme">{{ $institution['systeme'] }} · Document institutionnel</div>
            </div>
            <div class="meta-cell">
                <strong>{{ \App\Reports\Report::CATEGORIES[$rapport['categorie']] ?? $rapport['categorie'] }}</strong><br>
                Émis le {{ $genere_le->format('d/m/Y') }} à {{ $genere_le->format('H:i') }}<br>
                Par : {{ $genere_par }}
            </div>
        </div>
    </header>

    <footer>
        <div class="ligne">
            <div class="gauche">
                Code de vérification :<br>
                <span class="code-verif">{{ $code_verification }}</span>
            </div>
            <div class="centre">
                {{ $institution['systeme'] }}<br>
                Document confidentiel — Usage institutionnel
            </div>
            <div class="droite">
                Page <span class="page-num"></span><br>
                © {{ now()->year }} {{ $institution['abreviation'] }}
            </div>
        </div>
    </footer>

    @if(!empty($watermark))
        <div class="watermark">{{ $watermark }}</div>
    @endif

    <main>
        <div class="doc-categorie">{{ \App\Reports\Report::CATEGORIES[$rapport['categorie']] ?? $rapport['categorie'] }}</div>
        <div class="doc-titre">{{ $rapport['titre'] }}</div>
        @if($rapport['description'])
            <div class="doc-sous-titre">{{ $rapport['description'] }}</div>
        @endif

        @yield('content')

        <div class="qr-block">
            <img src="data:image/svg+xml;base64,{{ $qr_svg_base64 }}" alt="QR de vérification">
            <div class="code">{{ $code_verification }}</div>
            <div class="small text-muted">Vérifier l'authenticité de ce document à l'adresse : <br>{{ $url_verification }}</div>
        </div>
    </main>
</body>
</html>
