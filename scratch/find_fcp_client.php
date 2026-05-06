<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$movs = DB::table('fcp_movements')
    ->select('user_id', DB::raw('SUM(nb_parts_change) as total_parts'))
    ->groupBy('user_id')
    ->get();

foreach($movs as $m) {
    if ($m->total_parts > 5000) {
        $u = App\Models\User::find($m->user_id);
        echo "User: " . ($u->name ?? "ID ".$m->user_id) . " | Parts: " . $m->total_parts . "\n";
    }
}
