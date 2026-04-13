@php
use Carbon\Carbon;
Carbon::setLocale('fr_FR');
$periode = Carbon::now()->subMonth()->translatedFormat('F Y');

//dd($produits);
@endphp

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relevé de Compte KORI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .page-container {
            max-width: 90%;
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
            padding-top: 0px;
        }

        .company-info div:first-child {
            font-weight: 600;
            font-family: system-ui;
            font-size: 117%;
        }

        .title-section {
            text-align: center;
            margin: 11px 0;
        }

        .title-main {
            font-size: 18px;
            margin-bottom: 0px;
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

        .account-details {
            background-color: rgb(235 176 8 / 46%);
            padding: 20px;
            margin: 30px 0;
            border-left: 4px solid #531d09;
        }

        .account-details div {
            margin-bottom: 0;
            font-size: 15px;
            text-align: left;
            font-weight: 600;
        }

        .account-details strong {
            display: inline-block;
            min-width: auto;
        }

        .table-header {
            font-weight: bold;
            font-size: 12px;
            margin: 30px 0 15px;
        }

        .table-container {
            margin: 20px 0;
        }

        .table-container table {
            border-color: #531d09;
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
            border: 1px solid;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row {
            background-color: #ffffff;
            font-weight: bold;
            color: #531d09;
        }

        .disclaimer {
            margin-top: 40px;
            padding: 15px;
            background-color: #fffbea;
            border-left: 4px solid #f5a623;
            font-size: 13px;
            line-height: 1.6;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #333;
            font-size: 9px;
            text-align: center;
            line-height: 1.8;
        }

        .footer-section {
            display: inline-block;
            margin: 0 15px;
        }

        @media print {
            body {
                background-color: white;
                padding: 0;
            }

            .page-container {
                box-shadow: none;
                max-width: 100%;
            }
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

        .content-wrap .logo img {
            width: 100%;
            display: block;
        }

        .content-wrap .logo {
            width: 130px;
        }

        .content-client-product-wrapper {
            display: flex;
            flex-wrap: wrap;
        }

        .product-item {
            width: 24%;
            border: 1px solid #531d09;
            margin-bottom: 10px;
            margin-right: 9px;
        }

        .product-item:nth-child(4n+4) {
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

        .details-product strong {
            padding-bottom: 5px;
            display: block;
        }

        .details-product div {
            padding-bottom: 8px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <p>Bonjour @if($client -> genre == 0) M. @else Mme @endif {{ $client->name }} {{ $customer->name }},</p>

        <p>
        Veuillez trouver en pièce jointe votre relevé de compte pour la période
        <strong>{{ $periode }}</strong>.
        </p>

        <p>
        Cordialement,<br>
        <strong>Kori Asset Management</strong>
        </p>

        </body>

</html>
