<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;

$bigOnes = Transaction::where('status', 'Succès')->where('amount', '>', 100000000)->get();
foreach ($bigOnes as $t) {
    echo "ID: {$t->id} | Title: {$t->title} | Am: {$t->amount} | Init: {$t->montant_initiale}\n";
}
