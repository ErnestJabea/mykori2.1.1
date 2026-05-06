<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$col = DB::select("SHOW COLUMNS FROM asset_values LIKE 'vl'");
print_r($col);
