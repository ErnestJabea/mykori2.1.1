<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$productId = 10;
$totalParts = DB::table('fcp_movements')
    ->where('product_id', $productId)
    ->sum('nb_parts_change');

$currentVl = DB::table('asset_values')
    ->where('product_id', $productId)
    ->orderBy('date_vl', 'desc')
    ->value('vl');

echo "GLOBAL FCP KORI SERENITE AUDIT\n";
echo "------------------------------\n";
echo "Total Parts (All Clients): " . number_format($totalParts, 10, '.', '') . "\n";
echo "Current VL: $currentVl\n";
echo "Total Valuation: " . number_format($totalParts * $currentVl, 2, ',', ' ') . " XAF\n";
