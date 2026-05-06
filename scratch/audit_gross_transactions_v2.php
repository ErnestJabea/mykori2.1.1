<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$userId = 66;
$productId = 10;

echo "Audit des transactions d'origine pour le client ID 66\n";
echo str_repeat("-", 100) . "\n";
echo sprintf("%-12s | %-12s | %-12s | %-12s | %-12s | %-12s\n", "Date", "Type", "Montant Brut", "Frais", "Montant Net", "VL Appliquée");
echo str_repeat("-", 100) . "\n";

$trans = DB::table('transactions')
    ->where('user_id', $userId)
    ->where('product_id', $productId)
    ->where('status', 'Succès')
    ->get();

foreach ($trans as $t) {
    echo sprintf("%-12s | %-12s | %-12s | %-12s | %-12s | %-12s\n", 
        $t->date_validation, 
        "Achat Init.", 
        number_format($t->amount, 0, '.', ' '), 
        number_format($t->fees, 0, '.', ' '), 
        number_format($t->amount - $t->fees, 0, '.', ' '),
        number_format($t->vl_buy, 2, '.', ' ')
    );
}

$supps = DB::table('transaction_supplementaires')
    ->where('user_id', $userId)
    ->where('product_id', $productId)
    ->where('status', 'Succès')
    ->get();

foreach ($supps as $s) {
    $type = $s->type === 'rachat' ? 'Rachat' : 'Versement';
    echo sprintf("%-12s | %-12s | %-12s | %-12s | %-12s | %-12s\n", 
        $s->date_validation, 
        $type, 
        number_format($s->amount, 0, '.', ' '), 
        number_format($s->fees, 0, '.', ' '), 
        number_format($s->amount - $s->fees, 0, '.', ' '),
        number_format($s->vl_buy, 2, '.', ' ')
    );
}
