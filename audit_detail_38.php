<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ProductController;

$pc = new ProductController();
$user_id = 38;
$current_date = now()->toDateString(); // 01/04/2026

echo "=== DETAILED PORTFOLIO AUDIT - CLIENT ID: $user_id ===\n\n";

$trans = DB::table('transactions')->where('user_id', $user_id)->where('status', 'Succès')->get();

foreach ($trans as $t) {
    $p = DB::table('products')->where('id', $t->product_id)->first();
    $p_type = ($p->products_category_id == 2) ? 'PMG' : 'FCP';
    
    if ($p_type == 'PMG') {
        $valo = $pc->calculatePMGValorization($t, $current_date);
        $gain = $valo - (float)$t->amount;
        
        echo "[PMG] Produit: " . $p->title . "\n";
        echo "      Date Souscription : " . $t->date_validation . "\n";
        echo "      Montant Investi  : " . number_format($t->amount) . " XAF\n";
        echo "      Taux d'intérêt   : " . $t->vl_buy . "%\n";
        echo "      Intérêts générés : " . number_format($gain) . " XAF\n";
        echo "      Valeur Actuelle  : " . number_format($valo) . " XAF\n\n";
        
    } else {
        $parts = DB::table('fcp_movements')->where('user_id', $user_id)->where('product_id', $t->product_id)->sum('nb_parts_change');
        $lastVl = DB::table('fcp_vl_history')->where('product_id', $t->product_id)->orderBy('date_vl', 'desc')->value('vl');
        $currentValo = $parts * $lastVl;
        $gain = $currentValo - (float)$t->amount;

        echo "[FCP] Produit: " . $p->title . "\n";
        echo "      Date d'achat     : " . $t->date_validation . "\n";
        echo "      Nombre de parts  : " . number_format($parts, 2) . "\n";
        echo "      VL actuelle      : " . number_format($lastVl) . " XAF\n";
        echo "      Montant Investi  : " . number_format($t->amount) . " XAF\n";
        echo "      Plus-value (Gains): " . number_format($gain) . " XAF\n";
        echo "      Valeur Actuelle  : " . number_format($currentValo) . " XAF\n\n";
    }
}
echo "========================================================\n";
