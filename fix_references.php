<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$movements = DB::table('fcp_movements')->get();
$count = 0;

foreach ($movements as $m) {
    if (!$m->date_operation) continue;
    $dateStr = date('dmY', strtotime($m->date_operation));
    $newRef = 'RCH-' . $dateStr . '-' . strtoupper(Str::random(4));
    DB::table('fcp_movements')->where('id', $m->id)->update(['reference' => $newRef]);
    $count++;
}

echo "Mise à jour terminée. $count records updated.\n";
