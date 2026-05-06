<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$movs = DB::table('fcp_movements')
    ->where('type', 'like', '%rachat%')
    ->where('nb_parts_change', '>', 0)
    ->get();

echo "Rachats avec parts POSITIVES trouves: " . $movs->count() . "\n";
foreach($movs as $m) {
    $u = App\Models\User::find($m->user_id);
    echo " - ID: {$m->id} | User: " . ($u->name ?? "ID ".$m->user_id) . " | Parts: {$m->nb_parts_change} | Date: {$m->date_operation}\n";
}
