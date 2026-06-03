<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Alertes Anniversaires PMG</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .header {
            background-color: #531d09;
            color: #ffffff;
            padding: 24px;
            text-align: center;
            border-bottom: 4px solid #ebb009;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .content {
            padding: 30px;
        }
        .intro {
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 13px;
        }
        th {
            background-color: #ebb009;
            color: #531d09;
            font-weight: bold;
            text-align: left;
            padding: 12px 10px;
            border: 1px solid #e2e8f0;
        }
        td {
            padding: 12px 10px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .bold {
            font-weight: bold;
        }
        .footer {
            background-color: #f1f5f9;
            padding: 20px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Alertes Anniversaires PMG</h1>
        </div>
        <div class="content">
            <p class="intro">
                Bonjour,<br><br>
                Voici la liste des mandats PMG dont la date anniversaire aura lieu dans exactement <strong>une semaine</strong> (le {{ $targetDateFormatted }}).
                Veuillez trouver ci-dessous le détail des transactions concernées pour préparation de la capitalisation automatique.
            </p>

            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Mandat</th>
                        <th>Type</th>
                        <th class="text-right">Capital Nominal</th>
                        <th class="text-right">Valorisation Actuelle</th>
                        <th>Taux</th>
                        <th>Date de Valeur</th>
                        <th>Date d'Échéance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $t)
                        <tr>
                            <td class="bold">{{ $t['client_name'] }}</td>
                            <td>{{ $t['product_title'] }}<br><small style="color: #64748b;">Ref: {{ $t['reference'] }}</small></td>
                            <td>
                                <span style="font-size: 10px; font-weight: bold; padding: 2px 6px; border-radius: 4px; background: {{ $t['is_supplementaire'] ? '#fef3c7; color: #d97706;' : '#dbeafe; color: #2563eb;' }}">
                                    {{ $t['is_supplementaire'] ? 'Suppl.' : 'Principal' }}
                                </span>
                            </td>
                            <td class="text-right bold">{{ number_format($t['capital_nominal'], 0, ',', ' ') }} XAF</td>
                            <td class="text-right bold" style="color: #531d09;">{{ number_format($t['valorisation_actuelle'], 0, ',', ' ') }} XAF</td>
                            <td>{{ $t['taux'] }} %</td>
                            <td>{{ $t['date_valeur'] }}</td>
                            <td>{{ $t['date_echeance'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p style="font-size: 13px; color: #64748b; font-style: italic;">
                * La valorisation actuelle prend en compte le capital nominal et l'ensemble des intérêts courus calculés jusqu'à ce jour.
            </p>
        </div>
        <div class="footer">
            Kori Asset Management &copy; {{ date('Y') }} - Système de capitalisation automatique PMG
        </div>
    </div>
</body>
</html>
