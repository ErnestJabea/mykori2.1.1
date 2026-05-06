<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$clientId = 44; // Vroua Sarah
$productId = 10; // FCP KORI SERENITE
$dateN = Carbon\Carbon::parse('2026-04-30');
$dateN1 = Carbon\Carbon::parse('2026-03-31');

$partsSouscritesMois = \DB::table('fcp_movements')
    ->where('user_id', $clientId)
    ->where('product_id', $productId)
    ->whereDate('date_operation', '>=', $dateN1->copy()->addDay()->toDateString())
    ->whereDate('date_operation', '<=', $dateN->toDateString())
    ->where('nb_parts_change', '>', 0)
    ->sum('nb_parts_change') ?? 0;

$allMovsInApril = \DB::table('fcp_movements')
    ->where('user_id', $clientId)
    ->where('product_id', $productId)
    ->whereDate('date_operation', '>=', $dateN1->copy()->addDay()->toDateString())
    ->whereDate('date_operation', '<=', $dateN->toDateString())
    ->get();

echo "Diagnostic Releve Avril 2026 - Vroua Sarah:\n";
echo "Product: FCP KORI SERENITE (ID 10)\n";
echo "Date N1 (Fin Mars): " . $dateN1->toDateString() . "\n";
echo "Date N (Fin Avril): " . $dateN->toDateString() . "\n";
echo "Parts Souscrites calculees: " . $partsSouscritesMois . "\n";
echo "Nombre de mouvements trouves en Avril: " . $allMovsInApril->count() . "\n";

foreach($allMovsInApril as $m) {
    echo " - ID: {$m->id} | Date Op: {$m->date_operation} | Type: {$m->type} | Parts: {$m->nb_parts_change}\n";
}

$partsN = DB::table('fcp_movements')
    ->where('user_id', $clientId)
    ->where('product_id', $productId)
    ->whereDate('date_operation', '<=', $dateN->toDateString())
    ->sum('nb_parts_change') ?? 0;
echo "Parts au 30/04 (Solde): " . $partsN . "\n";
