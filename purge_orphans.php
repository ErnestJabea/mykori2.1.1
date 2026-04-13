<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// 1. Suppression des orphelins dans fcp_movements
$orphansFcp = DB::table('fcp_movements')
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
            ->from('transactions')
            ->whereColumn('transactions.id', 'fcp_movements.transaction_id');
    })->pluck('id');

if ($orphansFcp->count() > 0) {
    echo "Deleting " . $orphansFcp->count() . " orphan FCP movements...\n";
    DB::table('fcp_movements')->whereIn('id', $orphansFcp)->delete();
}

// 2. Suppression des orphelins dans financial_movements
$orphansPmg = DB::table('financial_movements')
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
            ->from('transactions')
            ->whereColumn('transactions.id', 'financial_movements.transaction_id');
    })->pluck('id');

if ($orphansPmg->count() > 0) {
    echo "Deleting " . $orphansPmg->count() . " orphan PMG movements...\n";
    DB::table('financial_movements')->whereIn('id', $orphansPmg)->delete();
}

echo "Sync complete.\n";
