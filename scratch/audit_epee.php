<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('name', 'like', '%EPEE%')->where('name', 'like', '%Carole%')->first();
if($user) {
    echo "User: " . $user->name . " (ID: {$user->id})\n";
    
    // Main Transactions in April
    $trans = App\Models\Transaction::where('user_id', $user->id)
        ->whereYear('date_validation', 2026)
        ->whereMonth('date_validation', 4)
        ->get();
    echo "Main April: " . $trans->count() . "\n";
    
    // Supp Transactions in April
    $supps = DB::table('transaction_supplementaires')
        ->where('user_id', $user->id)
        ->whereYear('date_validation', 2026)
        ->whereMonth('date_validation', 4)
        ->get();
    echo "Supp April: " . $supps->count() . "\n";
    foreach($supps as $s) {
        echo " - [SUPP] ID: {$s->id} | Amount: {$s->amount} | Status: {$s->status} | Date Val: {$s->date_validation}\n";
    }

    // Movements in April
    $fcpMovs = DB::table('fcp_movements')
        ->where('user_id', $user->id)
        ->whereYear('date_operation', 2026)
        ->whereMonth('date_operation', 4)
        ->get();
    echo "FCP Movements April: " . $fcpMovs->count() . "\n";

} else {
    echo "User not found\n";
}
