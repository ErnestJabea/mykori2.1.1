<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$client = \App\Models\User::where('name', 'like', '%vroua%')->first();
if (!$client) { echo "Client introuvable.\n"; exit; }
$transactions = \App\Models\Transaction::where('user_id', $client->id)->whereHas('product', function($q){ $q->where('products_category_id', 2); })->get();
$dateValoN = \Carbon\Carbon::parse('2026-04-30');
$dateValoN1 = \Carbon\Carbon::parse('2026-03-31');

// Simulation of the ListeClientReleveController logic to get the correct $dateN
$merged = $transactions; // ignoring supplemental for simplicity if none exist
$maxExpiryInMonth = $merged->where('date_echeance', '<=', $dateValoN->toDateString())
                           ->where('date_echeance', '>=', $dateValoN->copy()->startOfMonth()->toDateString())
                           ->max('date_echeance');
$anyActivePastMonth = $merged->where('date_echeance', '>', $dateValoN->toDateString())->isNotEmpty();

if (!$anyActivePastMonth && $maxExpiryInMonth) {
    $dateValoN = \Carbon\Carbon::parse($maxExpiryInMonth);
}

$controller = app(\App\Http\Controllers\ProductController::class);
echo "=== ETAT DE VALORISATION ===\n";
echo "Client: " . $client->name . "\n";
echo "Date N-1 (Mars): " . $dateValoN1->format('d/m/Y') . "\n";
echo "Date N (Avril capé): " . $dateValoN->format('d/m/Y') . "\n\n";

$totalCapital = 0;
$totalValoN = 0;
$totalValoN1 = 0;
$totalGainMensuel = 0;

foreach($transactions as $t) {
    $vN = $controller->calculatePMGValorization($t, $dateValoN);
    $vN1 = $controller->calculatePMGValorization($t, $dateValoN1);
    $capital = floatval($t->amount);
    
    // Gain Calculation specific to ProductController logic
    $mvtCap = \Illuminate\Support\Facades\DB::table('financial_movements')
            ->where('transaction_id', $t->id)
            ->where('type', 'capitalisation_interets')
            ->whereBetween('date_operation', [$dateValoN1->copy()->addDay()->toDateString(), $dateValoN->toDateString()])
            ->first();

    $gainMensuel = 0;
    if ($mvtCap) {
        $dateCap = \Carbon\Carbon::parse($mvtCap->date_operation);
        $joursAvant = $dateValoN1->diffInDays($dateCap->copy()->subDay()) + 1;
        $joursApres = $dateCap->diffInDays($dateValoN) + 1;
        $gainA = ($mvtCap->capital_before * ($t->vl_buy/100) * $joursAvant) / 360;
        $gainB = ($mvtCap->capital_after * ($t->vl_buy/100) * $joursApres) / 360;
        $gainMensuel = ($gainA + $gainB);
    } else {
        if (\Carbon\Carbon::parse($t->date_validation)->gt($dateValoN1)) {
            $prec = \Illuminate\Support\Facades\DB::table('financial_movements')->where('transaction_id', $t->id)->where('type', 'precompte_interets')->value('amount') ?? 0;
            $gainMensuel = ($vN - ($capital - (float)$prec));
        } else {
            $gainMensuel = ($vN - $vN1);
        }
    }
    
    $totalCapital += $capital;
    $totalValoN += $vN;
    $totalValoN1 += $vN1;
    $totalGainMensuel += $gainMensuel;

    echo "Produit: " . $t->product->title . "\n";
    echo "  - Montant souscrit : " . number_format($capital, 0, ',', ' ') . " XAF\n";
    echo "  - Taux net/an      : " . $t->vl_buy . "%\n";
    echo "  - Date Echeance    : " . \Carbon\Carbon::parse($t->date_echeance)->format('d/m/Y') . "\n";
    echo "  - Valo N-1 (Mars)  : " . number_format($vN1, 0, ',', ' ') . " XAF\n";
    echo "  - Gain (Avril)     : " . number_format($gainMensuel, 0, ',', ' ') . " XAF\n";
    echo "  - Valo N (Avril)   : " . number_format($vN, 0, ',', ' ') . " XAF\n\n";
}

echo "--- RECAPITULATIF ---\n";
echo "Total Placements: " . number_format($totalCapital, 0, ',', ' ') . " XAF\n";
echo "Total Gains Reçu (Mois Cible): " . number_format($totalGainMensuel, 0, ',', ' ') . " XAF\n";
echo "Valorisation au " . $dateValoN1->format('d/m/Y') . " : " . number_format($totalValoN1, 0, ',', ' ') . " XAF\n";
echo "Valorisation au " . $dateValoN->format('d/m/Y') . " : " . number_format($totalValoN, 0, ',', ' ') . " XAF\n";
