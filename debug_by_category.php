<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;

$activePmg = Transaction::whereHas('product', fn($q) => $q->where('products_category_id', 2))
    ->where('status', 'Succès')
    ->get();
$activeFcp = Transaction::whereHas('product', fn($q) => $q->where('products_category_id', 1))
    ->where('status', 'Succès')
    ->get();

echo "--- PMG ---\n";
echo "Amount: " . $activePmg->sum('amount') . "\n";
echo "Initial: " . $activePmg->sum('montant_initiale') . "\n";
echo "--- FCP ---\n";
echo "Amount: " . $activeFcp->sum('amount') . "\n";
echo "Initial: " . $activeFcp->sum('montant_initiale') . "\n";
