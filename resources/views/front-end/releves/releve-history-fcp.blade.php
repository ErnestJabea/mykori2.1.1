@php
    use Carbon\Carbon;
    Carbon::setLocale('fr');
    $logoPath   = public_path('images/logo-with-text.png');
    $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : '';

    // 1. Récupération des données de performance (calculées sur le dernier vendredi)
    $perf = app(\App\Services\InvestmentService::class)->getFcpPerformance($transaction);
    
    // 2. Récupération de l'historique complet filtré par CLIENT et PRODUIT spécifique
    $history = DB::table('fcp_movements')
        ->where('user_id', $transaction->user_id)
        ->where('product_id', $transaction->product_id)
        ->orderBy('date_operation', 'asc')
        ->get();

    // 3. Calcul du cumul des investissements (FCFA) pour ce produit précis
    $totalInvesti = $history->whereIn('type', ['souscription', 'versement_complementaire'])->sum('amount_xaf');
    $totalRetraits = abs($history->whereIn('type', ['rachat', 'rachat_total'])->sum('amount_xaf'));
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relevé FCP - {{ $transaction->product->title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; padding: 30px; color: #333; line-height: 1.4; font-size: 11px; }
        
        /* En-tête KORI Style */
        .header { border-bottom: 3px solid #531d09; padding-bottom: 10px; margin-bottom: 20px; }
        .logo img { width: 150px; }
        .company-info { float: right; text-align: right; color: #531d09; }

        .title-section { text-align: center; margin: 20px 0; }
        .title-main { font-size: 18px; font-weight: bold; color: #531d09; text-transform: uppercase; border-bottom: 1px solid #ebb008; display: inline-block; padding-bottom: 5px; }
        
        /* Infos Client & Produit */
        .details-container { width: 100%; margin-bottom: 20px; clear: both; }
        .info-box { width: 48%; display: inline-block; vertical-align: top; background: #fdf5e6; padding: 15px; border-left: 5px solid #ebb008; }
        
        /* Tableau de bord des parts */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background-color: #ebb008; color: #531d09; padding: 10px; border: 1px solid #531d09; text-transform: uppercase; font-size: 10px; }
        td { padding: 8px; border: 1px solid #ddd; }
        
        .text-right { text-align: right; }
        .positive { color: #28a745; font-weight: bold; }
        .negative { color: #dc3545; font-weight: bold; }
        .bold { font-weight: bold; }

        /* Résumé de valorisation */
        .summary-wrapper { width: 100%; margin-top: 25px; }
        .summary-table { width: 45%; float: right; border: none; }
        .summary-table td { border: none; padding: 4px; font-size: 12px; }
        .total-box { background-color: #531d09; color: #ffffff; padding: 12px; margin-top: 10px; font-size: 15px; font-weight: bold; text-align: right; clear: both; }

        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #777; border-top: 1px solid #ccc; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="KORI Logo" style="width:150px;">
            @else
                <strong style="font-size:16px;color:#531d09;">KORI ASSET MANAGEMENT</strong>
            @endif
        </div>
        <div class="company-info">
            <strong>KORI ASSET MANAGEMENT</strong><br>
            Date d'édition : {{ date('d/m/Y H:i') }}
        </div>
    </div>

    <div class="title-section">
        <div class="title-main">Relevé d'Historique FCP</div>
    </div>

    <div class="details-container">
        <div class="info-box">
            <strong style="color:#531d09">TITULAIRE DU COMPTE</strong><br>
            Nom : {{ strtoupper($transaction->user->name) }}<br>
            Email : {{ $transaction->user->email }}<br>
            Client ID : KORI-{{ str_pad($transaction->user->id, 5, '0', STR_PAD_LEFT) }}
        </div>
        <div class="info-box" style="margin-left: 3%;">
            <strong style="color:#531d09">DÉTAILS DU PRODUIT</strong><br>
            Fonds : {{ $transaction->product->title }}<br>
            Nombre de parts : {{ number_format($perf['nb_parts'], 4, ',', ' ') }}<br>
            Dernière VL : {{ number_format($perf['current_vl'], 2, ',', ' ') }} XAF ({{ Carbon::parse($perf['date_vl'])->format('d/m/Y') }})
        </div>
    </div>

    

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Désignation</th>
                <th class="text-right">VL Appliquée</th>
                <th class="text-right">Parts (+/-)</th>
                <th class="text-right">Solde Parts</th>
                <th class="text-right">Montant (XAF)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($history as $m)
            <tr>
                <td>{{ Carbon::parse($m->date_operation)->format('d/m/Y') }}</td>
                <td>{{ strtoupper(str_replace('_', ' ', $m->type)) }}</td>
                <td class="text-right">{{ number_format($m->vl_applied, 2, ',', ' ') }}</td>
                <td class="text-right {{ $m->nb_parts_change >= 0 ? 'positive' : 'negative' }}">
                    {{ $m->nb_parts_change >= 0 ? '+' : '' }}{{ number_format($m->nb_parts_change, 4, ',', ' ') }}
                </td>
                <td class="text-right bold">{{ number_format($m->nb_parts_total, 4, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($m->amount_xaf, 0, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-wrapper">
        <table class="summary-table">
            <tr>
                <td>Cumul des investissements :</td>
                <td class="text-right">{{ number_format($totalInvesti, 0, ',', ' ') }} XAF</td>
            </tr>
            <tr>
                <td>Cumul des retraits :</td>
                <td class="text-right negative">- {{ number_format($totalRetraits, 0, ',', ' ') }} XAF</td>
            </tr>
            <tr>
                <td>Plus-value latente :</td>
                <td class="text-right {{ $perf['plus_value'] >= 0 ? 'positive' : 'negative' }}">
                    {{ $perf['plus_value'] >= 0 ? '+' : '' }}{{ number_format($perf['plus_value'], 0, ',', ' ') }} XAF
                </td>
            </tr>
        </table>
        
        <div class="total-box">
            VALEUR ACTUELLE DU PORTEFEUILLE : {{ number_format($perf['valuation_actuelle'], 0, ',', ' ') }} XAF
        </div>
    </div>

    <div class="footer">
        Kori Asset Management - Rue 1.131 DIKOUME BELL, BP: 1245 BALI-DOUALA<br>
        Agrément COSUMAF SGA/2021-01 | Les performances passées ne préjugent pas des performances futures.
    </div>

</body>
</html>