<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use App\Models\User;

$customers = User::where('role_id', '2')
    ->with(['transactions' => fn($q) => $q->where('status', 'Succès'), 
            'transactionssupplementaires' => fn($q) => $q->where('status', 'Succès')])
    ->get();

$currentDate = Carbon::now();
$sum_amount = 0;
$sum_montant_initiale = 0;
$sum_composite = 0;

foreach ($customers as $c) {
    $all = $c->transactions->concat($c->transactionssupplementaires);
    foreach ($all as $t) {
        if (Carbon::parse($t->date_echeance)->gte($currentDate)) {
            $sum_amount += (float)$t->amount;
            $sum_montant_initiale += (float)$t->montant_initiale;
            $sum_composite += (float)($t->montant_initiale ?? $t->amount);
        }
    }
}

echo "Somme Amount: $sum_amount\n";
echo "Somme Montant Initiale: $sum_montant_initiale\n";
echo "Somme Composite (Initial ?? Amount): $sum_composite\n";
echo "Différence (Am - Initial): " . ($sum_amount - $sum_montant_initiale) . "\n";
