<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$userId = 66;
$productId = 10;

$movements = DB::table('fcp_movements')
    ->where('user_id', $userId)
    ->where('product_id', $productId)
    ->orderBy('date_operation', 'asc')
    ->get();

echo "Audit complet des parts pour le client ID 66 (Produit ID 10)\n";
echo str_repeat("-", 80) . "\n";
echo sprintf("%-12s | %-15s | %-12s | %-15s | %-15s\n", "Date", "Montant (XAF)", "VL", "Parts calculées", "Parts en Base");
echo str_repeat("-", 80) . "\n";

$totalCalculated = 0;
$totalBase = 0;

foreach ($movements as $m) {
    $amount = (double)$m->amount_xaf;
    $vl = (double)$m->vl_applied;
    
    // Pour les rachats, le montant est négatif ou les parts sont négatives
    // Dans fcp_movements, nb_parts_change est déjà signé.
    
    $calculatedParts = 0;
    if ($vl > 0) {
        $calculatedParts = $amount / $vl;
    }
    
    // Si c'est un rachat, on s'assure du signe (souvent amount_xaf est positif mais type='rachat')
    if ($m->type === 'rachat') {
        $calculatedParts = -abs($calculatedParts);
    }

    $totalCalculated += $calculatedParts;
    $totalBase += (double)$m->nb_parts_change;

    echo sprintf("%-12s | %-15s | %-12s | %-15.10f | %-15.10f\n", 
        $m->date_operation, 
        number_format($amount, 0, '.', ' '), 
        number_format($vl, 2, '.', ' '), 
        $calculatedParts, 
        (double)$m->nb_parts_change
    );
}

echo str_repeat("-", 80) . "\n";
echo sprintf("%-42s | %-15.10f | %-15.10f\n", "TOTAUX CUMULÉS", $totalCalculated, $totalBase);
echo str_repeat("-", 80) . "\n";

$diff = abs($totalCalculated - $totalBase);
echo "Écart total : " . number_format($diff, 10, '.', '') . "\n";
