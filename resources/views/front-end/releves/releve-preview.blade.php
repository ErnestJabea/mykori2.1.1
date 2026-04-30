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
    <title>Relevé de Compte KORI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: sans-serif;
            /*background-color: #f5f5f5;*/
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
            margin-top: 0px;
            padding: 0;
            font-size: 10px;
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

        .produit-nouveau {
            color: #ff8c00;
            font-size: 9px;
            font-style: italic;
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
            position: relative;
        }

        .content-client-product-wrapper::after {
            clear: both;
        }

        .product-item {
            width: 24%;
            border: 1px solid #531d09;
            margin-bottom: 10px;
            margin-right: 9px;
            float: left;
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

        .content-client-product-wrapper {
            width: 100%;
        }

        .content-client-product-wrapper::after {
            content: "";
            display: block;
            clear: both;
        }

        /* ── Disclaimer ── */
        .disclaimer {
            font-size: 9.5px;
            color: #6b6b6b;
            line-height: 1.6;
            padding: 12px 14px;
            background: #f9f7f3;
            border-left: 3px solid #ebb008;
            border-radius: 0 4px 4px 0;
            margin-bottom: 24px;
        }

        /* ── Footer ── */
        .footer {
            border-top: 1px solid #e0d9cf;
            padding-top: 14px;
            text-align: center;
            font-size: 9px;
            color: #9c8c6e;
            line-height: 1.8;
        }

        .footer strong {
            color: #531d09;
        }

        .t-placements th {
            background: #531d09;
            color: #fff;
            padding: 9px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 10px;
            letter-spacing: .04em;
        }


        .t-placements {
            margin-bottom: 20px;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #e8e0d0;
        }

        .t-placements thead th {
            font-family: inherit;
            font-size: 13px !important;
        }

        .t-placements td {
            font-size: 12px;
            padding: 5px;
        }

        .table-container {
            border-radius: 6px;
            border: 1px solid rgb(51 51 51 / 63%);
        }
    </style>
</head>

<body>
    <div class="page-container">
        <!-- En-tête avec informations de l'entreprise -->
        <div class="content-wrap flex justify-between items-start">
            <div class="logo">
                @if ($logoBase64)
                    <img width="200" src="{{ $logoBase64 }}" alt="Kori Asset Management" />
                @else
                    <strong style="font-size:18px;color:#531d09;">KORI ASSET MANAGEMENT</strong>
                @endif
            </div>

            <div class="company-info">
                <div>
                    @if ($client->genre == 0)
                        M.
                    @elseif ($client->genre == 1)
                        Mme
                    @else
                    @endif {{ $client->name }}
                </div>
                <div>{{ $client->localisation }}</div>
                <div>{{ $client->bp }}</div>
                <div>{{ $client->email }}</div>
            </div>
        </div>
        <!-- Titre principal -->
        <div class="title-section">
            <div class="title-compte">Relevé de compte mensuel - Gestion Privée</div>
            <div class="title-period">{{ ucfirst($periode) }}</div>
        </div>
        <div class="content-client-product-wrapper">
            @php
                $total_placement = 0;
                $total_gain_mois = 0;
                $total_gains = 0;
            @endphp
            @foreach ($produits as $p)
                @php
                    $total_placement += $p->capital;
                    $total_gain_mois += $p->gain_mensuel;
                    $total_gains += $p->gain_total;
                @endphp
            @endforeach
        </div>

        <div class="t-placements">
            <table>
                <thead>
                    <tr>
                        <th>Placement</th>
                        <th class="center">Date de valeur</th>
                        <th class="center">Date d'échéance</th>
                        <th class="right">Montant (XAF)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($produits as $p)
                        <tr>
                            <td class="nom-cell">{{ $p->nom }}</td>
                            <td class="center">{{ $p->souscription }}</td>
                            <td class="center">{{ $p->date_echeance }}</td>
                            <td class="right">{{ number_format($p->capital, 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th colspan="4">
                            Total Placements : XAF {{ number_format($total_placement, 0, ' ', ' ') }} |
                            Total gains : XAF {{ number_format($total_gains, 0, ' ', ' ') }}
                        </th>
                        <th colspan="2" class="text-center">Rendement du mois (XAF)</th>
                        <th class="text-center">Valeur Totale</th>
                    </tr>
                    <tr>
                        <th>Intitulé</th>
                        <th>Produit</th>
                        <th class="text-center">Montant placement</th>
                        <th class="text-center">Taux net/an</th>
                        <th class="text-center">Gains</th>
                        <th class="text-center">Pertes</th>
                        <th class="text-center vide"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="total-row">
                        <td colspan="4">Valorisation du portefeuille au {{ $date_releve_precedent }}</td>
                        <td></td>
                        <td></td>
                        <td class="text-right">{{ number_format($valorisation_precedente, 0, ' ', ' ') }}</td>
                    </tr>

                    @foreach ($produits as $p)
                        <tr>
                            <td>Mandat de Gestion</td>
                            <td>{{ $p->nom }}</td>
                            <td class="text-center">{{ number_format($p->capital, 0, ' ', ' ') }}</td>
                            <td class="text-center">{{ $p->taux }}%</td>
                            <td class="text-right">
                                <span>
                                    @if ($p->produit_jeune == 1)
                                        {{-- Si souscrit en Janvier, le gain est calculé depuis la souscription --}}
                                        + {{ number_format($p->gain_mensuel, 0, ' ', ' ') }}
                                    @else
                                        {{-- Gain spécifique généré durant le mois de Janvier --}}
                                        + {{ number_format($p->gain_mensuel, 0, ' ', ' ') }}
                                    @endif
                                </span>
                            </td>
                             <td class="text-right">
                                @if(isset($p->perte_mensuelle) && $p->perte_mensuelle > 0)
                                    {{ number_format($p->perte_mensuelle, 0, ' ', ' ') }}
                                @else
                                    0
                                @endif
                             </td>
                            <td class="text-right"></td>
                        </tr>
                    @endforeach

                    <tr class="total-row">
                        <td colspan="4">Valorisation du portefeuille au {{ $date_releve }}</td>
                        <td></td>
                        <td></td>
                        <td class="text-right" style=" font-size: 1.1em;">
                            {{ number_format($valorisation_courante, 0, ' ', ' ') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Clause de réclamation -->
        <div class="disclaimer">
            Sauf réclamation de votre part dans un délai de 30 jours, à compter de sa date d'envoi,
            nous considérons ce relevé comme approuvé. <br>
            <small>MG : Mandat de Gestion</small>
        </div>

        <div class="footer">
            Kori Asset Management - Rue 1.131 DIKOUME BELL, BP: 1245 BALI-DOUALA<br>
            info@koriassetmanagement.com | www.koriassetmanagement.com
        </div>
</body>

</html>
