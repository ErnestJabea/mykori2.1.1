<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$duplicates = DB::select("SELECT product_id, date_vl, COUNT(*) as count FROM asset_values GROUP BY product_id, date_vl HAVING count > 1");
print_r($duplicates);
