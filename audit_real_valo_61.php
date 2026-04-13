<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ProductController;

$pc = new ProductController();
$user_id = 61;
$current_date = now()->toDateString(); // 01/04/2026

echo "=== REAL VALUATION AUDIT (01/04/2026) - CLIENT ID: $user_id ===\n\n";

$trans = DB::table('transactions')->where('user_id', $user_id)->where('status', 'Succès')->get();

foreach ($trans as $t) {
    $p = DB::table('products')->where('id', $t->product_id)->first();
    $valo = $pc->calculatePMGValorization($t, $current_date);
    $outflows = DB::table('financial_movements')->where('transaction_id', $t->id)->sum('amount');
    
    echo "Produit: " . $p->title . " (Capital initial: " . number_format($t->amount) . " XAF)\n";
    echo "      Date de Valeur   : " . $t->date_validation . "\n";
    echo "      Taux d'intérêt   : " . $t->vl_buy . "%\n";
    echo "      Sorties (Précomptes): " . number_format($outflows) . " XAF\n";
    echo "      VALEUR RÉELLE ACTUELLE : " . number_format($valo) . " XAF\n\n";
}
echo "========================================================\n";
