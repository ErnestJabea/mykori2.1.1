<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ProductController;

$pc = new ProductController();
$user_id = 38;

// On récupère toutes les transactions réussies du client
$trans = DB::table('transactions')
    ->where('user_id', $user_id)
    ->where('status', 'Succès')
    ->get();

$totalValo = 0;
$totalInvest = 0;

echo "--- Portfolio Audit for Client ID: $user_id ---\n";

foreach ($trans as $t) {
    // Détection du type de produit
    $product = DB::table('products')->where('id', $t->product_id)->first();
    $p_type = ($product->products_category_id == 2) ? 'PMG' : 'FCP';
    
    if ($p_type == 'PMG') {
        // Valorisation PMG via le nouveau moteur
        $valo = $pc->calculatePMGValorization($t, now()->toDateString());
    } else {
        // Valorisation FCP (Nombre de parts * Dernière VL)
        $parts = DB::table('fcp_movements')->where('transaction_id', $t->id)->sum('nb_parts_change');
        $lastVl = DB::table('fcp_vl_history')
            ->where('product_id', $t->product_id)
            ->orderBy('date_vl', 'desc')
            ->value('vl') ?? 0;
        $valo = $parts * $lastVl;
    }
    
    $totalValo += $valo;
    $totalInvest += $t->amount;
    
    echo "[$p_type] ID: {$t->id} | Invest: " . number_format($t->amount) . " | Current Valo: " . number_format($valo) . "\n";
}

echo "---------------------------------\n";
echo "Total Investment: " . number_format($totalInvest) . " XAF\n";
echo "Total Portfolio Value: " . number_format($totalValo) . " XAF\n";
echo "Global Gains: " . number_format($totalValo - $totalInvest) . " XAF\n";
