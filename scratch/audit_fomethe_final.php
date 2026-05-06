<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$userId = 36;
$productId = 10;

echo "AUDIT COMPLET - FOMETHE MOMO PATRICK\n";
echo "------------------------------------\n";

// Recalcul des parts mouvement par mouvement
$movements = DB::table('fcp_movements')
    ->where('user_id', $userId)
    ->where('product_id', $productId)
    ->orderBy('date_operation', 'asc')
    ->get();

$runningTotal = 0;
$totalInvested = 0;

foreach ($movements as $m) {
    $vlRecord = DB::table('asset_values')
        ->where('product_id', $productId)
        ->whereDate('date_vl', '<=', $m->date_operation)
        ->orderBy('date_vl', 'desc')
        ->first();
    
    $vl = (float)$vlRecord->vl;
    $parts = (float)$m->amount_xaf / $vl;
    $runningTotal += $parts;
    $totalInvested += (float)$m->amount_xaf;

    echo "Date: {$m->date_operation} | Montant: {$m->amount_xaf} | VL: $vl | Parts: $parts | Cumul: $runningTotal\n";
    
    // Update DB
    DB::table('fcp_movements')->where('id', $m->id)->update([
        'vl_applied' => $vl,
        'nb_parts_change' => $parts,
        'nb_parts_total' => $runningTotal
    ]);
}

echo "------------------------------------\n";
echo "TOTAL INVESTI: $totalInvested\n";
echo "TOTAL PARTS: $runningTotal\n";

// Valorisation au 31/03/2026
$vlMars = DB::table('asset_values')
    ->where('product_id', $productId)
    ->whereDate('date_vl', '<=', '2026-03-31')
    ->orderBy('date_vl', 'desc')
    ->value('vl');

$partsMars = DB::table('fcp_movements')
    ->where('user_id', $userId)
    ->where('product_id', $productId)
    ->whereDate('date_operation', '<=', '2026-03-31')
    ->sum('nb_parts_change');

$valoMars = $partsMars * $vlMars;

// Valorisation au 30/04/2026
$vlAvril = DB::table('asset_values')
    ->where('product_id', $productId)
    ->whereDate('date_vl', '<=', '2026-04-30')
    ->orderBy('date_vl', 'desc')
    ->value('vl');

$partsAvril = DB::table('fcp_movements')
    ->where('user_id', $userId)
    ->where('product_id', $productId)
    ->whereDate('date_operation', '<=', '2026-04-30')
    ->sum('nb_parts_change');

$valoAvril = $partsAvril * $vlAvril;

echo "VALO FIN MARS: " . number_format($valoMars, 2) . " (VL: $vlMars, Parts: $partsMars)\n";
echo "VALO FIN AVRIL: " . number_format($valoAvril, 2) . " (VL: $vlAvril, Parts: $partsAvril)\n";
echo "GAIN MENSUEL THEORIQUE: " . number_format($valoAvril - $valoMars, 2) . "\n";
