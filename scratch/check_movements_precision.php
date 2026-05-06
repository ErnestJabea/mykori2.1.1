<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$col = DB::select("SHOW COLUMNS FROM fcp_movements LIKE 'vl_applied'");
print_r($col);

$col2 = DB::select("SHOW COLUMNS FROM fcp_movements LIKE 'nb_parts_change'");
print_r($col2);
