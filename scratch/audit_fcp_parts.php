<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$productId = 10;

// Get all movements for this product
$movements = DB::table('fcp_movements')
    ->where('product_id', $productId)
    ->orderBy('date_operation', 'asc')
    ->get();

echo "AUDIT MOVEMENTS FOR PRODUCT $productId\n";
echo "--------------------------------------\n";

foreach ($movements as $m) {
    // Find the VL in asset_values for this date
    $vl = DB::table('asset_values')
        ->where('product_id', $productId)
        ->whereDate('date_vl', $m->date_operation)
        ->first();
    
    if ($vl) {
        $expectedParts = 0;
        if ((float)$vl->vl > 0) {
            $expectedParts = (float)$m->amount_xaf / (float)$vl->vl;
        }
        
        echo "ID: {$m->id} | Date: {$m->date_operation} | User: {$m->user_id}\n";
        echo "  Amount: {$m->amount_xaf} | VL Applied: {$m->vl_applied} | VL in DB: {$vl->vl}\n";
        echo "  Parts in Mov: {$m->nb_parts_change} | Expected Parts: " . number_format($expectedParts, 10, '.', '') . "\n";
        
        if (abs((float)$m->vl_applied - (float)$vl->vl) > 0.000001) {
            echo "  !!! MISMATCH VL !!!\n";
        }
    } else {
        echo "ID: {$m->id} | Date: {$m->date_operation} | !!! NO VL FOUND IN DB !!!\n";
    }
}
