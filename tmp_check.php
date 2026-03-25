<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;

$ts = Transaction::all();
foreach($ts as $t) {
    if ($t->product && $t->product->products_category_id == 1 && $t->status == 'Succès') {
        echo "User ID: " . $t->user_id . " | Product: " . $t->product->title . "\n";
    }
}
echo "Done.\n";
