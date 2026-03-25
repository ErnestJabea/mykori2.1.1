<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;
use App\Http\Controllers\ProductController;

$pc = new ProductController();
$t = Transaction::find(74); // MG1 trans from my previous log
if (!$t) {
    echo "Transaction 74 not found. Trying another.\n";
    $t = Transaction::where('amount', 50000000)->where('status', 'Succès')->first();
}

if ($t) {
    echo "Found Trans: {$t->id} | Amount: {$t->amount} | Validation: {$t->date_validation}\n";
    $valo = $pc->calculatePMGValorization($t, '2026-03-23');
    echo "VALO RESULT: $valo\n";
    
    $principalInitial = (float)($t->montant_initiale ?? $t->amount);
    echo "Initial: $principalInitial\n";
    echo "Gain: " . ($valo - $principalInitial) . "\n";
} else {
    echo "No transaction found with 50M.\n";
}
