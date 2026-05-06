<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$userId = 36;
$productId = 10;
$targetDate = '2026-04-30';

// 1. Get total parts as of target date
$totalParts = DB::table('fcp_movements')
    ->where('user_id', $userId)
    ->where('product_id', $productId)
    ->whereDate('date_operation', '<=', $targetDate)
    ->sum('nb_parts_change');

// 2. Get latest VL as of target date
$vlRecord = DB::table('asset_values')
    ->where('product_id', $productId)
    ->whereDate('date_vl', '<=', $targetDate)
    ->orderBy('date_vl', 'desc')
    ->first();

echo "FCP VALUATION AS OF $targetDate FOR FOMETHE MOMO Patrick\n";
echo "-------------------------------------------------------------\n";

if ($vlRecord) {
    $vl = (float)$vlRecord->vl;
    $valo = (float)$totalParts * $vl;

    echo "Product: FCP KORI SERENITE\n";
    echo "  Total Parts at $targetDate: " . number_format($totalParts, 6, '.', '') . "\n";
    echo "  VL Applied (from {$vlRecord->date_vl}): " . $vlRecord->vl . "\n";
    echo "  Valuation: " . number_format($valo, 2, ',', ' ') . " XAF\n";
} else {
    echo "No VL found for product $productId on or before $targetDate\n";
}
