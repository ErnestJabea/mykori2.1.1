<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(36);
if ($user) {
    echo "Client: " . $user->name . "\n";
    $fcp = App\Models\Transaction::where('user_id', 36)
        ->whereHas('product', function($q) {
            $q->where('products_category_id', 1);
        })
        ->get();
    echo "FCP Transactions: " . $fcp->count() . "\n";
    foreach($fcp as $t) {
        echo " - ID: " . $t->id . " | Status: " . $t->status . " | Ref: " . $t->ref . "\n";
    }
    
    $fcp_supp = App\Models\TransactionSupplementaire::where('user_id', 36)
        ->whereHas('product', function($q) {
            $q->where('products_category_id', 1);
        })
        ->get();
    echo "FCP Transaction Supplémentaires: " . $fcp_supp->count() . "\n";
}
