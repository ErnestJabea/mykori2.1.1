@php
    use Carbon\Carbon;
    Carbon::setLocale('fr');

    // Embed logo as base64 to avoid missing image in PDF
    $logoPath = public_path('images/logo-with-text.png');
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    }

    $totalEntrant = $allMovements->where('sens', 'entrant')->sum('montant');
    $totalSortant = $allMovements->where('sens', 'sortant')->sum(fn($m) => abs($m->montant));
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique de Transactions - {{ $user->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            padding: 25px 30px;
            background: #fff;
        }

        /* ── Header ── */
        .header {
            display: table;
            width: 100%;
            border-bottom: 3px solid #531d09;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .header-left  { display: table-cell; vertical-align: middle; width: 50%; }
        .header-right { display: table-cell; vertical-align: middle; width: 50%; text-align: right; }
        .header-right .client-name { font-size: 14px; font-weight: bold; color: #531d09; }
        .header-right .client-info { font-size: 11px; opacity: .75; margin-top: 2px; }

        .logo img {
            height: 60px;
            display: block;
        }

        /* ── Title section ── */
        .title-section {
            text-align: center;
            margin: 18px 0 22px;
        }
        .title-section .doc-title {
            font-size: 17px;
            font-weight: bold;
            color: #531d09;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .title-section .doc-sub {
            font-size: 11px;
            color: #555;
            margin-top: 4px;
        }

        /* ── Summary boxes ── */
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-box {
            display: table-cell;
            width: 33%;
            padding: 10px 14px;
            border-radius: 6px;
            vertical-align: middle;
        }
        .summary-box + .summary-box { padding-left: 10px; }
        .summary-box .label { font-size: 10px; font-weight: bold; text-transform: uppercase; opacity: .7; }
        .summary-box .value { font-size: 15px; font-weight: bold; margin-top: 2px; }
        .box-green  { background-color: #edfdf5; border-left: 4px solid #22c55e; }
        .box-red    { background-color: #fff1f2; border-left: 4px solid #ef4444; }
        .box-blue   { background-color: #f0f4ff; border-left: 4px solid #531d09; }
        .color-green { color: #16a34a; }
        .color-red   { color: #dc2626; }
        .color-blue  { color: #531d09; }

        /* ── Table ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        thead tr th {
            background-color: #531d09;
            color: #ebb008;
            padding: 8px 10px;
            text-align: left;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: .4px;
        }
        thead tr th.right { text-align: right; }
        thead tr th.center { text-align: center; }

        tbody tr { border-bottom: 1px solid #f0f0f0; }
        tbody tr:nth-child(even) { background-color: #fafafa; }
        tbody tr td { padding: 7px 10px; vertical-align: middle; }
        tbody tr td.right  { text-align: right; }
        tbody tr td.center { text-align: center; }

        .badge-entrant {
            display: inline-block;
            background-color: #dcfce7;
            color: #16a34a;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-sortant {
            display: inline-block;
            background-color: #fee2e2;
            color: #dc2626;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: bold;
        }
        .amount-entrant { color: #16a34a; font-weight: bold; }
        .amount-sortant { color: #dc2626; font-weight: bold; }

        .product-badge {
            display: inline-block;
            background-color: #fdf3e3;
            color: #531d09;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: bold;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="logo">
                @if ($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Kori Asset Management Logo">
                @else
                    <strong style="font-size:16px; color:#531d09;">KORI ASSET MANAGEMENT</strong>
                @endif
            </div>
        </div>
        <div class="header-right">
            <div class="client-name">{{ $user->name }}</div>
            <div class="client-info">{{ $user->email }}</div>
            @if($user->localisation)
                <div class="client-info">{{ $user->localisation }}</div>
            @endif
        </div>
    </div>

    {{-- Title --}}
    <div class="title-section">
        <div class="doc-title">Historique de Transactions</div>
        <div class="doc-sub">Généré le {{ $generated_at }} &nbsp;|&nbsp; Toutes les opérations enregistrées</div>
    </div>

    {{-- Summary --}}
    <div class="summary-row">
        <div class="summary-box box-green">
            <div class="label">Total entrant</div>
            <div class="value color-green">+ {{ number_format($totalEntrant, 0, ' ', ' ') }} XAF</div>
        </div>
        <div class="summary-box box-red">
            <div class="label">Total sortant</div>
            <div class="value color-red">- {{ number_format($totalSortant, 0, ' ', ' ') }} XAF</div>
        </div>
        <div class="summary-box box-blue">
            <div class="label">Opérations</div>
            <div class="value color-blue">{{ $allMovements->count() }}</div>
        </div>
    </div>

    {{-- Table --}}
    <table>
        <thead>
            <tr>
                <th>Date Op.</th>
                <th>Date Sous.</th>
                <th>Opération</th>
                <th>Produit</th>
                <th>Référence</th>
                <th class="center">Sens</th>
                <th class="right">Montant (XAF)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($allMovements as $mvt)
                @php
                    $isEntrant = $mvt->sens === 'entrant';
                    $isFees = str_contains(strtoupper($mvt->libelle), 'FRAIS');
                @endphp
                <tr {!! $isFees ? 'style="background-color: #fafafa;"' : '' !!}>
                    <td>{{ \Carbon\Carbon::parse($mvt->date)->format('d/m/Y') }}</td>
                    <td style="opacity: 0.7;">{{ isset($mvt->date_souscription) ? \Carbon\Carbon::parse($mvt->date_souscription)->format('d/m/Y') : '-' }}</td>
                    <td style="{{ $isFees ? 'font-style: italic; opacity: 0.8;' : 'font-weight: bold;' }}">
                        {{ str_replace('Achat', 'Souscription', $mvt->libelle) }}
                    </td>
                    <td>
                        <span class="product-badge">{{ $mvt->produit ?? 'N/A' }}</span>
                    </td>
                    <td style="font-family: monospace; font-size:8.5px; opacity: .8;">
                        {{ $mvt->ref ?? '-' }}
                    </td>
                    <td class="center">
                        @if ($isEntrant)
                            <span class="badge-entrant">↓ Entrant</span>
                        @else
                            <span class="badge-sortant">↑ Sortant</span>
                        @endif
                    </td>
                    <td class="right {{ $isEntrant ? 'amount-entrant' : 'amount-sortant' }}" style="font-size: 11px;">
                        {{ $isEntrant ? '+' : '-' }}{{ number_format(abs($mvt->montant), 0, ' ', ' ') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding: 20px; opacity: .5;">
                        Aucune opération trouvée
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="footer">
        Kori Asset Management &mdash; Rue 1.131 DIKOUME BELL, BP: 1245 BALI-DOUALA<br>
        info@koriassetmanagement.com &nbsp;|&nbsp; www.koriassetmanagement.com
    </div>

</body>
</html>
