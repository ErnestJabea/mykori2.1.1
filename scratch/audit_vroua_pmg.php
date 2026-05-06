<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$userId = 44;
$pmgMovements = DB::table('financial_movements')
    ->leftJoin('transactions', 'financial_movements.transaction_id', '=', 'transactions.id')
    ->leftJoin('transaction_supplementaires', 'financial_movements.transaction_id', '=', 'transaction_supplementaires.id')
    ->where(function($q) use ($userId) {
        $q->where('transactions.user_id', $userId)
          ->orWhere('transaction_supplementaires.user_id', $userId);
    })
    ->get();

echo "PMG Movements for Vroua Sarah: " . $pmgMovements->count() . "\n";
foreach($pmgMovements as $m) {
    echo " - ID: {$m->id} | Type: {$m->type} | Amount: {$m->amount}\n";
}
