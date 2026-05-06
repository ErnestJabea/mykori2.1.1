<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$clientId = 66; // KORI ASSET MANAGEMENT

echo "Reparation des mouvements pour KORI ASSET MANAGEMENT...\n";

// 1. Correction du rachat positif
$affected = DB::table('fcp_movements')
    ->where('id', 40734)
    ->update([
        'nb_parts_change' => -915.170972,
        'amount_xaf' => -10000000.00
    ]);

if ($affected) {
    echo " - Mouvement 40734 corrige en negatif.\n";
}

// 2. Recalcul des soldes progressifs (nb_parts_total)
$movs = DB::table('fcp_movements')
    ->where('user_id', $clientId)
    ->orderBy('date_operation', 'asc')
    ->orderBy('id', 'asc')
    ->get();

$runningTotal = 0;
foreach($movs as $m) {
    $runningTotal += (float)$m->nb_parts_change;
    DB::table('fcp_movements')->where('id', $m->id)->update(['nb_parts_total' => $runningTotal]);
    echo " - ID: {$m->id} | Type: {$m->type} | Change: {$m->nb_parts_change} | Nouveau Total: {$runningTotal}\n";
}

echo "Reparation terminee.\n";
