<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$t = App\Models\Transaction::whereHas('product', function($q) {
    $q->where('products_category_id', 1);
})->first();

if ($t) {
    echo "Date Echéance: '" . $t->date_echeance . "'\n";
    echo "Value: " . ($t->date_echeance ?? 'NULL') . "\n";
} else {
    echo "No FCP transaction found.\n";
}
