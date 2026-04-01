<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;

$trans = Transaction::where('amount', '>=', 240000)->where('amount', '<=', 260000)->get();

foreach ($trans as $t) {
    echo "ID: {$t->id} | Ref: {$t->ref} | Amount: {$t->amount} | Fees: {$t->fees} | Parts: {$t->nb_part} | VL: {$t->vl_buy} | Date: {$t->date_validation} | ProductID: {$t->product_id} | UserID: {$t->user_id}\n";
    $m = DB::table('fcp_movements')->where('transaction_id', $t->id)->first();
    if ($m) {
        echo "   Movement: Type: {$m->type} | AmountXAF: {$m->amount_xaf} | VL: {$m->vl_applied} | Parts: {$m->nb_parts_change}\n";
    }
}
