<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$userId = 70;

$movements = DB::table('fcp_movements')->where('user_id', $userId)->get();
foreach ($movements as $m) {
    echo "Raw Parts: " . sprintf("%.12f", $m->nb_parts_change) . "\n";
}

$sumParts = DB::table('fcp_movements')->where('user_id', $userId)->sum('nb_parts_change');
echo "Sum Parts (Default): " . sprintf("%.12f", $sumParts) . "\n";

$castSum = DB::table('fcp_movements')
    ->where('user_id', $userId)
    ->select(DB::raw('SUM(CAST(nb_parts_change AS DECIMAL(20,10))) as total'))
    ->value('total');
echo "Sum Parts (Cast): " . sprintf("%.12f", $castSum) . "\n";
