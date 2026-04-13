<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

$user_id = 61;
$trans = DB::table('transactions')->where('user_id', $user_id)->get();

echo "--- Full Audit for Client ID: $user_id ---\n\n";

foreach ($trans as $t) {
    echo "Transaction ID: {$t->id} | Amount: " . number_format($t->amount) . " XAF | Date: {$t->date_validation}\n";
    $mvts = DB::table('financial_movements')->where('transaction_id', $t->id)->get();
    foreach ($mvts as $m) {
        echo "  -> [" . strtoupper($m->type) . "] Amount: " . number_format($m->amount) . " XAF | Date: {$m->date_operation}\n";
    }
}
echo "\n--- End of Audit ---\n";
