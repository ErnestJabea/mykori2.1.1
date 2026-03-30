<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Carbon\Carbon;

$currentDate = Carbon::now();
$customers = User::where('role_id', '2')
    ->with(['transactions' => fn($q) => $q->where('status', 'Succès'), 
            'transactionssupplementaires' => fn($q) => $q->where('status', 'Succès')])
    ->get();

$main_amount = 0;
$supp_amount = 0;

foreach ($customers as $c) {
    foreach ($c->transactions as $t) {
        if (Carbon::parse($t->date_echeance)->gte($currentDate)) {
            $main_amount += (float)$t->amount;
        }
    }
    foreach ($c->transactionssupplementaires as $ts) {
        if (Carbon::parse($ts->date_echeance)->gte($currentDate)) {
            $supp_amount += (float)$ts->amount;
        }
    }
}

echo "Main Transactions Sum: $main_amount\n";
echo "Supplements Sum: $supp_amount\n";
echo "Total Global: " . ($main_amount + $supp_amount) . "\n";
