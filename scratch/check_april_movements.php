<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$movs = DB::table('fcp_movements')
    ->whereYear('date_operation', 2026)
    ->whereMonth('date_operation', 4)
    ->get();

echo "FCP Movements in April 2026: " . $movs->count() . "\n";
foreach($movs as $m) {
    $u = App\Models\User::find($m->user_id);
    echo " - User: " . ($u->name ?? "ID ".$m->user_id) . " | Date: {$m->date_operation} | Amount: {$m->amount_xaf}\n";
}

$pmgMovs = DB::table('financial_movements')
    ->whereYear('date_operation', 2026)
    ->whereMonth('date_operation', 4)
    ->get();

echo "PMG Movements in April 2026: " . $pmgMovs->count() . "\n";
foreach($pmgMovs as $m) {
    // Need to find user via transaction
    $t = DB::table('transactions')->where('id', $m->transaction_id)->first();
    if(!$t) $t = DB::table('transaction_supplementaires')->where('id', $m->transaction_id)->first();
    
    $u = $t ? App\Models\User::find($t->user_id) : null;
    echo " - User: " . ($u->name ?? "Unknown") . " | Date: {$m->date_operation} | Amount: {$m->amount}\n";
}
