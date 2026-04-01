<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;

$trans = Transaction::orderBy('created_at', 'desc')->limit(10)->get();

foreach ($trans as $t) {
    echo "ID: {$t->id} | Ref: {$t->ref} | Amount: {$t->amount} | Fees: {$t->fees} | Parts: {$t->nb_part} | VL: {$t->vl_buy} | Date: {$t->date_validation}\n";
}
