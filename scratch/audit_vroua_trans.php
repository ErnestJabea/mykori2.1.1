<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$userId = 44;
$transactions = App\Models\Transaction::where('user_id', $userId)->with('product')->get();

echo "Detail des transactions pour Vroua Sarah:\n";
foreach($transactions as $t) {
    echo " - ID: {$t->id} | Ref: {$t->ref} | Date Val: {$t->date_validation} | Montant: {$t->amount} | Status: {$t->status} | Produit: " . ($t->product->title ?? 'N/A') . " (Cat: " . ($t->product->products_category_id ?? 'N/A') . ")\n";
}
