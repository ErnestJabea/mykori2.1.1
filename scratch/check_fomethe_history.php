<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$movements = DB::table('fcp_movements')->where('user_id', 36)->where('product_id', 10)->get();
print_r($movements);
