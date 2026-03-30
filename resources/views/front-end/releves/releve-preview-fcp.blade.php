@php
use Carbon\Carbon;
Carbon::setLocale('fr');
// On utilise la variable $periode passée par le contrôleur
$logoPath   = public_path('images/logo-with-text.png');
$logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : '';
@endphp

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relevé de Compte FCP KORI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: sans-serif;
            padding: 0px;
        }

        .page-container {
            max-width: 100%;
            margin: 0 auto;
            background-color: white;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .company-info {
            text-align: right;
            font-size: 13px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .company-info div:first-child {
            font-weight: 600;
            font-size: 117%;
        }

        .title-section {
            text-align: center;
            margin: 11px 0;
        }

        .title-compte {
            font-size: 27px;
            font-weight: bold;
            margin-bottom: 0px;
        }

        .title-period {
            font-size: 16px;
            margin-bottom: 20px;
        }

        .table-container {
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th {
            background-color: #ebb008;
            padding: 12px 8px;
            text-align: left;
            border: 1px solid #531d09;
            font-weight: bold;
        }

        td {
            padding: 12px 8px;
            border: 1px solid #531d09;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row {
            background-color: #f9f9f9;
            font-weight: bold;
            color: #531d09;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #333;
            font-size: 9px;
            text-align: center;
            line-height: 1.8;
        }

        .flex {
            display: flex;
        }

        .justify-between {
            justify-content: space-between;
        }

        .items-start {
            align-items: flex-start;
        }

        .logo {
            width: 130px;
        }

        .logo img {
            width: 100%;
        }

        .product-item {
            width: 32%;
            border: 1px solid #531d09;
            margin-bottom: 10px;
            margin-right: 9px;
            float: left;
        }

        .product-item:nth-child(3n+3) {
            margin-right: 0;
        }

        .title-product {
            padding: 10px;
            text-align: center;
            background: #531d09;
            color: #fff;
            font-weight: 600;
        }

        .details-product {
            padding: 10px;
        }

        .details-product div {
            padding-bottom: 8px;
            font-size: 13px;
        }

        .clearfix::after {
            content: "";
            display: block;
            clear: both;
        }
    </style>
</head>

<body>
    <div class="page-container">
        <div class="flex justify-between items-start">
            <div class="logo">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Kori Asset Management" />
                @else
                    <strong style="font-size:16px;color:#531d09;">KORI ASSET MANAGEMENT</strong>
                @endif
            </div>
            <div class="company-info">
                <div>{{ $client->name }}</div>
                <div>{{ $client->email }}</div>
            </div>
        </div>

        <div class="title-section">
            <div class="title-compte">Relevé de compte mensuel - FCP</div>
            <div class="title-period">{{ ucfirst($periode) }}</div>
        </div>

        <div class="clearfix">
            @foreach ($produits as $p)
            <div class="product-item">
                <div class="title-product">{{ $p->nom }}</div>
                <div class="details-product">
                    <div><strong>Date de souscription :</strong> {{ \Carbon\Carbon::parse($p->souscription)->format('d/m/Y') }}</div>
                    <div><strong>Nombre de parts :</strong> {{ number_format($p->parts, 2, ',', ' ') }}</div>
                    <div><strong>VL à la souscription :</strong> XAF {{ number_format($p->vl_souscription, 2, ',', ' ') }}</div>
                    <div><strong>VL au {{ $date_releve }} :</strong> XAF {{ number_format($p->vl_n, 2, ',', ' ') }}</div>
                    <div><strong>Valorisation :</strong> XAF {{ number_format($p->valo_n, 0, ' ', ' ') }}</div>
                    <div><strong>Plus-value :</strong> XAF {{ number_format($p->gain_total, 0, ' ', ' ') }}</div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Désignation</th>
                        <th class="text-center">Parts</th>
                        <th class="text-center">VL au {{ $date_releve_precedent }}</th>
                        <th class="text-center">VL au {{ $date_releve }}</th>
                        <th class="text-right">Variation Mensuelle</th>
                        <th class="text-right">Valeur Estimée</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="total-row">
                        <td colspan="5">Valorisation du portefeuille au {{ $date_releve_precedent }}</td>
                        <td class="text-right">XAF {{ number_format($valorisation_precedente, 0, ' ', ' ') }}</td>
                    </tr>

                    @foreach ($produits as $p)
                    <tr>
                        <td>{{ $p->nom }}</td>
                        <td class="text-center">{{ number_format($p->parts, 2, ',', ' ') }}</td>
                        <td class="text-center">{{ number_format($p->vl_n1, 2, ',', ' ') }}</td>
                        <td class="text-center">{{ number_format($p->vl_n, 2, ',', ' ') }}</td>
                        <td class="text-right" style="color: {{ $p->gain_mensuel >= 0 ? 'green' : 'red' }}">
                            {{ $p->gain_mensuel >= 0 ? '+' : '' }}{{ number_format($p->gain_mensuel, 0, ' ', ' ') }}
                        </td>
                        <td class="text-right">XAF {{ number_format($p->valo_n, 0, ' ', ' ') }}</td>
                    </tr>
                    @endforeach

                    <tr class="total-row">
                        <td colspan="5">Valeur liquidative totale au {{ $date_releve }}</td>
                        <td class="text-right" style="font-size: 1.2em;">XAF {{ number_format($valorisation_courante, 0, ' ', ' ') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer">
            Kori Asset Management - Rue 1.131 DIKOUME BELL, BP: 1245 BALI-DOUALA<br>
            info@koriassetmanagement.com | www.koriassetmanagement.com
        </div>
    </div>
</body>

</html>
