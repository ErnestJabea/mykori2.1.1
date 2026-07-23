<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Version calculee du releve</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #2b160f;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            border-bottom: 3px solid #ebb008;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }
        .subtitle {
            color: #6b2a12;
            margin-top: 4px;
        }
        .meta {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .meta td {
            padding: 6px 8px;
            border: 1px solid #d8c5bc;
        }
        .meta td:first-child {
            width: 32%;
            font-weight: bold;
            background: #f8f1dd;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 18px;
        }
        table.data th {
            background: #531d09;
            color: #fff;
            padding: 8px;
            border: 1px solid #531d09;
            text-align: left;
        }
        table.data td {
            padding: 8px;
            border: 1px solid #d8c5bc;
        }
        .section-title {
            margin-top: 18px;
            font-size: 15px;
            font-weight: bold;
            color: #531d09;
        }
        .hash {
            font-size: 9px;
            word-break: break-all;
            color: #444;
        }
        .right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Version calculee du releve</p>
        <p class="subtitle">Document technique genere apres validation de correction</p>
    </div>

    <table class="meta">
        <tr>
            <td>Client</td>
            <td>{{ $client->name }} - {{ $client->email }}</td>
        </tr>
        <tr>
            <td>Periode</td>
            <td>{{ $version->period_name }}</td>
        </tr>
        <tr>
            <td>Date de releve</td>
            <td>{{ optional($version->statement_date)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>Version</td>
            <td>v{{ $version->version_number }} - {{ $version->status }}</td>
        </tr>
        <tr>
            <td>Hash payload SHA-256</td>
            <td class="hash">{{ $version->payload_sha256_hash }}</td>
        </tr>
        <tr>
            <td>Hash PDF SHA-256</td>
            <td>Stocke en base apres generation du document.</td>
        </tr>
    </table>

    <div class="section-title">Synthese</div>
    <table class="data">
        <thead>
            <tr>
                <th>Rubrique</th>
                <th class="right">Montant XAF</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Valorisation PMG</td>
                <td class="right">{{ number_format($payload['totals']['pmg_valuation'] ?? 0, 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <td>Valorisation FCP</td>
                <td class="right">{{ number_format($payload['totals']['fcp_valuation'] ?? 0, 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <td><strong>Valorisation globale</strong></td>
                <td class="right"><strong>{{ number_format($payload['totals']['global_valuation'] ?? 0, 2, ',', ' ') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">PMG</div>
    <table class="data">
        <thead>
            <tr>
                <th>Produit</th>
                <th>Transaction</th>
                <th class="right">Capital</th>
                <th class="right">Taux</th>
                <th class="right">Valorisation</th>
                <th class="right">Gain</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($payload['pmg'] ?? []) as $item)
                <tr>
                    <td>{{ $item['product_title'] ?? '-' }}</td>
                    <td>#{{ $item['transaction_id'] ?? '-' }}</td>
                    <td class="right">{{ number_format($item['amount'] ?? 0, 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format($item['rate'] ?? 0, 2, ',', ' ') }}%</td>
                    <td class="right">{{ number_format($item['valuation'] ?? 0, 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format($item['gain'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Aucun PMG dans cette version.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">FCP</div>
    <table class="data">
        <thead>
            <tr>
                <th>Produit</th>
                <th class="right">Parts</th>
                <th class="right">VL</th>
                <th class="right">Valorisation</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($payload['fcp'] ?? []) as $item)
                <tr>
                    <td>{{ $item['product_title'] ?? '-' }}</td>
                    <td class="right">{{ number_format($item['parts'] ?? 0, 10, ',', ' ') }}</td>
                    <td class="right">{{ number_format($item['vl'] ?? 0, 6, ',', ' ') }}</td>
                    <td class="right">{{ number_format($item['valuation'] ?? 0, 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Aucun FCP dans cette version.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
