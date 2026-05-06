<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$productId = 10;

// 1. Get all users who have movements in this product
$users = DB::table('fcp_movements')
    ->where('product_id', $productId)
    ->distinct()
    ->pluck('user_id');

echo "RECALCULATING PARTS FOR PRODUCT $productId\n";
echo "------------------------------------------\n";

foreach ($users as $userId) {
    $userName = DB::table('users')->where('id', $userId)->value('name');
    echo "Processing User: $userName (ID: $userId)...\n";
    
    // Get all movements for this user and product, ordered by date
    $movements = DB::table('fcp_movements')
        ->where('user_id', $userId)
        ->where('product_id', $productId)
        ->orderBy('date_operation', 'asc')
        ->orderBy('id', 'asc')
        ->get();
    
    $runningTotal = 0;
    
    foreach ($movements as $m) {
        // Find the VL that SHOULD apply (latest VL <= date_operation)
        $vlRecord = DB::table('asset_values')
            ->where('product_id', $productId)
            ->whereDate('date_vl', '<=', $m->date_operation)
            ->orderBy('date_vl', 'desc')
            ->first();
        
        if ($vlRecord) {
            $newVl = (float)$vlRecord->vl;
            $newPartsChange = 0;
            
            if ($newVl > 0) {
                // If it's a rachat (negative amount), we should keep the same sign
                $newPartsChange = (float)$m->amount_xaf / $newVl;
            }
            
            $runningTotal += $newPartsChange;
            
            // Update the movement
            DB::table('fcp_movements')
                ->where('id', $m->id)
                ->update([
                    'vl_applied' => $newVl,
                    'nb_parts_change' => $newPartsChange,
                    'nb_parts_total' => $runningTotal
                ]);
        }
    }
    echo "  Final Parts: " . number_format($runningTotal, 10, '.', '') . "\n";
}

echo "------------------------------------------\n";
echo "RECALCULATION COMPLETE.\n";
