<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$orphans = DB::table('fcp_movements')
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
            ->from('transactions')
            ->whereColumn('transactions.id', 'fcp_movements.transaction_id');
    })
    ->get();

if ($orphans->count() > 0) {
    echo "Found " . $orphans->count() . " orphan movements:\n";
    foreach ($orphans as $o) {
        echo "ID: {$o->id} | TransID: {$o->transaction_id} | UserID: {$o->user_id} | AmountXAF: {$o->amount_xaf}\n";
    }
} else {
    echo "No orphan movements found in fcp_movements.\n";
}

// Also check financial_movements (for PMG)
$pmgOrphans = DB::table('financial_movements')
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
            ->from('transactions')
            ->whereColumn('transactions.id', 'financial_movements.transaction_id');
    })
    ->get();

if ($pmgOrphans->count() > 0) {
    echo "Found " . $pmgOrphans->count() . " orphan PMG movements:\n";
    foreach ($pmgOrphans as $o) {
        echo "ID: {$o->id} | TransID: {$o->transaction_id} | Type: {$o->type} | Amount: {$o->amount}\n";
    }
} else {
    echo "No orphan movements found in financial_movements.\n";
}
