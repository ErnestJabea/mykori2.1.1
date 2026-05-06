<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$m = DB::table('fcp_movements')->where('id', 40734)->first();
if($m) {
    echo "Transaction ID: " . ($m->transaction_id ?? "NULL") . "\n";
    echo "Supp ID: " . ($m->transaction_supplementaire_id ?? "NULL") . "\n";
}
