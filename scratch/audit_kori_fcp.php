<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$clientId = 66; // KORI ASSET MANAGEMENT
$productId = 10; // FCP KORI SERENITE
$dateN = Carbon\Carbon::parse('2026-04-30');
$dateN1 = Carbon\Carbon::parse('2026-03-31');

echo "Audit KORI ASSET MANAGEMENT (ID 66) - Avril 2026:\n";

$allMovsInApril = \DB::table('fcp_movements')
    ->where('user_id', $clientId)
    ->where('product_id', $productId)
    ->whereDate('date_operation', '>=', $dateN1->copy()->addDay()->toDateString())
    ->whereDate('date_operation', '<=', $dateN->toDateString())
    ->get();

echo "Nombre de mouvements trouves en Avril: " . $allMovsInApril->count() . "\n";
foreach($allMovsInApril as $m) {
    echo " - ID: {$m->id} | Date Op: {$m->date_operation} | Type: {$m->type} | Amount: {$m->amount_xaf} | Parts: {$m->nb_parts_change}\n";
}

$totalPartsBefore = DB::table('fcp_movements')
    ->where('user_id', $clientId)
    ->where('product_id', $productId)
    ->whereDate('date_operation', '<=', $dateN1->toDateString())
    ->sum('nb_parts_change') ?? 0;
echo "Parts au 31/03 (Mois precedent): " . $totalPartsBefore . "\n";

$totalPartsAfter = DB::table('fcp_movements')
    ->where('user_id', $clientId)
    ->where('product_id', $productId)
    ->whereDate('date_operation', '<=', $dateN->toDateString())
    ->sum('nb_parts_change') ?? 0;
echo "Parts au 30/04 (Mois actuel): " . $totalPartsAfter . "\n";
