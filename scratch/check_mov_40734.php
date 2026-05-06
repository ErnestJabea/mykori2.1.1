<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$m = DB::table('fcp_movements')->where('id', 40734)->first();
if($m) {
    echo "ID: {$m->id} | Type: {$m->type} | Parts: {$m->nb_parts_change} | Amount: {$m->amount_xaf} | Date: {$m->date_operation}\n";
} else {
    echo "Mouvement non trouvé\n";
}
