<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = DB::select("SHOW COLUMNS FROM financial_movements");
foreach ($columns as $col) {
    echo "Field: {$col->Field} | Type: {$col->Type}\n";
}
