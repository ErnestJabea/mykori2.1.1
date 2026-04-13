@php
use Carbon\Carbon;
Carbon::setLocale('fr');
$periode = Carbon::now()->translatedFormat('F Y');
$logoPath = public_path('images/logo-with-text.png');
$logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : '';
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique de Compte - {{ $transaction->ref }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; padding: 20px; font-size: 12px; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #531d09; padding-bottom: 10px; }
        .logo img { width: 150px; }
        .company-info { text-align: right; float: right; }
        .title-section { text-align: center; margin: 30px 0; clear: both; }
        .title-compte { font-size: 22px; font-weight: bold; color: #531d09; }
        
        .client-card { 
            background-color: rgba(235, 176, 8, 0.2); 
            padding: 15px; 
            border-left: 5px solid #531d09; 
            margin-bottom: 20px; 
        }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background-color: #ebb008; color: #531d09; padding: 10px; border: 1px solid #531d09; text-transform: uppercase; }
        td { padding: 8px; border: 1px solid #ddd; }
        
        .text-right { text-align: right; }
        .positive { color: green; font-weight: bold; }
        .negative { color: red; font-weight: bold; }
        .initial-row { background-color: #fff9eb; font-weight: bold; }

        .summary-table { margin-top: 20px; width: 50%; float: right; }
        .summary-table td { border: none; padding: 5px; }
        .footer { margin-top: 50px; text-align: center; font-size: 10px; border-top: 1px solid #ccc; padding-top: 10px; clear: both; }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="KORI Logo" style="width:150px;">
            @else
                <strong style="font-size:16px; color:#531d09;">KORI ASSET MANAGEMENT</strong>
            @endif
        </div>
        <div class="company-info">
            <strong>{{ $transaction->user->name }}</strong><br>
            {{ $transaction->user->localisation ?? 'N/A' }}<br>
            {{ $transaction->user->email }}
        </div>
    </div>

    <div class="title-section">
        <div class="title-compte">RELEVÉ D'HISTORIQUE DE PORTEFEUILLE</div>
        <div>Période au {{ date('d/m/Y') }}</div>
    </div>

    <div class="client-card">
        <strong>Référence :</strong> {{ $transaction->ref }}<br>
        <strong>Produit :</strong> {{ $transaction->product->title }}<br>
        <strong>Type :</strong> {{ $transaction->product->products_category_id == 2 ? 'Mandat de Gestion (PMG)' : 'FCP' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Opération</th>
                <th class="text-right">Flux (XAF)</th>
                <th class="text-right">Capital (XAF)</th>
                <th class="text-right">Taux / VL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movements as $m)
            <tr class="{{ $m->type == 'souscription_initiale' ? 'initial-row' : '' }}">
                <td>{{ Carbon::parse($m->date_operation)->format('d/m/Y') }}</td>
                <td>
                    @if($m->type == 'souscription_initiale')
                        INVESTISSEMENT INITIAL
                    @elseif($m->type == 'capitalisation_interets')
                        CAPITALISATION ANNUELLE
                    @elseif($m->type == 'rachat_partiel')
                        RACHAT PARTIEL
                    @else
                        {{ strtoupper(str_replace('_', ' ', $m->type)) }}
                    @endif
                </td>
                @php
                    $isNegative = in_array($m->type, ['rachat_partiel', 'rachat_total', 'precompte_interets', 'paiement_interets', 'remboursement']);
                @endphp
                <td class="text-right {{ $isNegative ? 'negative' : 'positive' }}">
                    {{ $isNegative ? '-' : '+' }}{{ number_format(abs($m->amount), 0, ',', ' ') }}
                </td>
                <td class="text-right">
                    {{ number_format($m->capital_after, 0, ',', ' ') }}
                </td>
                <td class="text-right">
                    {{ $m->interest_rate_at_moment ?? $transaction->vl_buy }} {{ $transaction->product->products_category_id == 2 ? '%' : '' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td><strong>Total Investi :</strong></td>
            <td class="text-right">{{ number_format($movements->whereIn('type', ['souscription_initiale', 'versement_complementaire'])->sum('amount'), 0, ',', ' ') }} FCFA</td>
        </tr>
        <tr>
            <td><strong>Total Retraits :</strong></td>
            <td class="text-right negative">{{ number_format(abs($movements->whereIn('type', ['rachat_partiel', 'rachat_total'])->sum('amount')), 0, ',', ' ') }} FCFA</td>
        </tr>
        <tr style="border-top: 2px solid #531d09;">
            <td><strong>VALEUR ACTUELLE :</strong></td>
            <td class="text-right"><strong>{{ number_format($movements->last()->capital_after ?? 0, 0, ',', ' ') }} FCFA</strong></td>
        </tr>
    </table>

    <div class="footer">
        Kori Asset Management - Rue 1.131 DIKOUME BELL, BP: 1245 BALI-DOUALA<br>
        info@koriassetmanagement.com | www.koriassetmanagement.com
    </div>

</body>
</html>
