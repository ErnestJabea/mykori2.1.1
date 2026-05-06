<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$userId = 66;

// 1. Get all FCP products for this user
$movements = DB::table('fcp_movements')
    ->where('user_id', $userId)
    ->select('product_id', DB::raw('SUM(nb_parts_change) as total_parts'))
    ->groupBy('product_id')
    ->get();

$valuationTotal = 0;
echo "VALUATION FOR KORI ASSET MANAGEMENT (ID: $userId)\n";
echo "--------------------------------------------------\n";

foreach ($movements as $m) {
    if ($m->total_parts <= 0.000001) continue;

    $product = DB::table('products')->where('id', $m->product_id)->first();
    $currentVl = DB::table('asset_values')
        ->where('product_id', $m->product_id)
        ->orderBy('date_vl', 'desc')
        ->value('vl');
    
    $valo = $m->total_parts * $currentVl;
    $valuationTotal += $valo;

    echo "Product: {$product->title}\n";
    echo "  Total Parts: {$m->total_parts}\n";
    echo "  Current VL: {$currentVl}\n";
    echo "  Valuation: " . number_format($valo, 2, ',', ' ') . " XAF\n";
}

echo "--------------------------------------------------\n";
echo "TOTAL FCP VALUATION: " . number_format($valuationTotal, 2, ',', ' ') . " XAF\n";

// 2. PMG Cash (using financial_movements joined with transactions)
$pmgCash = DB::table('financial_movements')
    ->join('transactions', 'financial_movements.transaction_id', '=', 'transactions.id')
    ->where('transactions.user_id', $userId)
    ->sum('financial_movements.amount');

echo "TOTAL PMG CASH: " . number_format($pmgCash, 2, ',', ' ') . " XAF\n";
echo "--------------------------------------------------\n";
echo "GLOBAL VALUATION: " . number_format($valuationTotal + $pmgCash, 2, ',', ' ') . " XAF\n";
