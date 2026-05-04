@php
    use Carbon\Carbon;
    Carbon::setLocale('fr');
    // On utilise la variable $periode passée par le contrôleur
    $logoPath = public_path('images/logo-with-text.png');
    $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
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
                @if ($logoBase64)
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

        @foreach ($produits as $p)
            <div style="margin-bottom: 40px;">
                <h3 style="color: #531d09; margin-bottom: 10px; border-bottom: 2px solid #ebb008; padding-bottom: 5px;">
                    {{ data_get($p, 'nom') }}</h3>

                <!-- TABLEAU 1: DETAIL SYNTHETIQUE -->
                <div class="table-container">
                    <p
                        style="font-size: 11px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; color: #666;">
                        I. Détail Synthétique</p>
                    <table>
                        <thead>
                            <tr>
                                <th class="text-center">Date de Valorisation</th>
                                <th class="text-center">VL à date (XAF)</th>
                                <th class="text-right">Capital Total Investi</th>
                                <th class="text-center">Nombre de Parts Cumulé</th>
                                <th class="text-right">Valorisation Portefeuille</th>
                                <th class="text-right">Plus-Value</th>
                                <th class="text-right">Gain Mensuel</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">{{ $date_releve }}</td>
                                <td class="text-center">{{ number_format(data_get($p, 'vl_n', 0), 2, ',', ' ') }}</td>
                                 <td class="text-right">XAF
                                     {{ number_format(data_get($p, 'cumul_investi', 0), 0, ' ', ' ') }}</td>
                                 <td class="text-center">{{ number_format(data_get($p, 'parts_n', 0), 2, ',', ' ') }}
                                </td>
                                <td class="text-right" style="font-weight: bold;">XAF
                                    {{ number_format(data_get($p, 'valo_n', 0), 0, ' ', ' ') }}</td>
                                <td class="text-right"
                                    style="color: {{ data_get($p, 'plus_value', 0) >= 0 ? 'green' : 'red' }}; font-weight: bold;">
                                    XAF {{ number_format(data_get($p, 'plus_value', 0), 0, ' ', ' ') }}
                                </td>
                                <td class="text-right"
                                    style="color: {{ data_get($p, 'gain_mensuel', 0) >= 0 ? 'green' : 'red' }}; font-weight: bold;">
                                    XAF {{ number_format(data_get($p, 'gain_mensuel', 0), 0, ' ', ' ') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- TABLEAU 2: DETAIL DES TRANSACTIONS -->
                <div class="table-container" style="margin-top: 10px;">
                    <p
                        style="font-size: 11px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; color: #666;">
                        II. Détails des Transactions (Parts)</p>
                    <table>
                        <thead>
                            <tr>
                                <th>Désignation</th>
                                <th class="text-center">Nb Parts (Mois précédent)</th>
                                <th class="text-right">Montant brut investi</th>
                                <th class="text-right">Frais de souscription</th>
                                <th class="text-center">Nb Parts (Mois)</th>
                                <th class="text-center">Nb Parts (Rachetées)</th>
                                <th class="text-center">Nombre de Parts Actuel</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Mouvements de {{ $periode }}</td>
                                <td class="text-center">{{ number_format(data_get($p, 'parts_n1', 0), 2, ',', ' ') }}
                                </td>
                                <td class="text-right" style="color: green; font-weight: bold;">XAF
                                    {{ number_format(data_get($p, 'montant_souscrit', 0), 0, ' ', ' ') }}
                                </td>
                                <td class="text-right" style="color: #666;">XAF
                                    {{ number_format(data_get($p, 'frais_souscription', 0), 0, ' ', ' ') }}</td>
                                <td class="text-center" style="color: green;">+
                                    {{ number_format(data_get($p, 'parts_souscrites', 0), 2, ',', ' ') }}</td>
                                <td class="text-center" style="color: red;">-
                                    {{ number_format(data_get($p, 'parts_rachetees', 0), 2, ',', ' ') }}</td>
                                <td class="text-center" style="font-weight: bold; background-color: #fcfcfc;">
                                    {{ number_format(data_get($p, 'parts_n', 0), 2, ',', ' ') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        <div class="table-container" style="margin-top: 30px;">
            <table style="border: 2px solid #531d09;">
                <tr class="total-row">
                    <td style="padding: 15px; font-size: 14px;">VALORISATION AU {{ $date_releve }}</td>
                    <td class="text-right" style="padding: 15px; font-size: 18px;">XAF
                        {{ number_format($valorisation_courante, 0, ' ', ' ') }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            Kori Asset Management - Rue 1.131 DIKOUME BELL, BP: 1245 BALI-DOUALA<br>
            info@koriassetmanagement.com | www.koriassetmanagement.com
        </div>
    </div>
</body>

</html>
