<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('name', 'like', '%FOMETHE%')->first();
if($user) {
    echo "User: " . $user->name . " (ID: {$user->id})\n";
    
    $trans = App\Models\Transaction::where('user_id', $user->id)->where('status', 'Succès')->get();
    echo "Transactions: " . $trans->count() . "\n";
    foreach($trans as $t) {
        echo " - ID: {$t->id} | Ref: {$t->ref} | Date: {$t->date_validation} | Amount: {$t->amount}\n";
    }
    
    $fcp = DB::table('fcp_movements')->where('user_id', $user->id)->get();
    echo "FCP Movements: " . $fcp->count() . "\n";
    foreach($fcp as $m) {
        echo " - Date: {$m->date_operation} | Amount: {$m->amount_xaf}\n";
    }
    
} else {
    echo "User not found\n";
}
