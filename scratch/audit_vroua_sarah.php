<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('name', 'like', '%Vroua%')->where('name', 'like', '%Sarah%')->first();
if($user) {
    echo "ID: " . $user->id . "\n";
    echo "Name: " . $user->name . "\n";
    
    // Check FCP movements
    $fcpMovements = DB::table('fcp_movements')->where('user_id', $user->id)->orderBy('date_operation', 'desc')->get();
    echo "FCP Movements: " . $fcpMovements->count() . "\n";
    foreach($fcpMovements as $m) {
        echo " - Date: {$m->date_operation} | Type: {$m->type} | Amount: {$m->amount_xaf} | Parts: {$m->nb_parts_change}\n";
    }
    
    // Check Transactions
    $transactions = App\Models\Transaction::where('user_id', $user->id)->where('status', 'Succès')->get();
    echo "Transactions (Main): " . $transactions->count() . "\n";
    
    $supps = App\Models\TransactionSupplementaire::where('user_id', $user->id)->where('status', 'Succès')->get();
    echo "Transactions (Supp): " . $supps->count() . "\n";
    foreach($supps as $s) {
        echo " - [SUPP] Date: {$s->date_validation} | Amount: {$s->amount} | Ref: {$s->ref}\n";
    }

} else {
    echo "User not found\n";
}
