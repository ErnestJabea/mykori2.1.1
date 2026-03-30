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

$total_amount_active = 0;
$total_initial_active = 0;

foreach ($customers as $c) {
    $all = $c->transactions->concat($c->transactionssupplementaires);
    foreach ($all as $t) {
        if (Carbon::parse($t->date_echeance)->gte($currentDate)) {
            $total_amount_active += (float)$t->amount;
            $total_initial_active += (float)$t->montant_initiale;
        }
    }
}

echo "Active Sum Amount: $total_amount_active\n";
echo "Active Sum Initial: $total_initial_active\n";
