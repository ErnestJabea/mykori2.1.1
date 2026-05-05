<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$vl = DB::table('asset_values')->where('product_id', 10)->orderBy('date_vl', 'desc')->first();
echo "VL for Product 10: " . $vl->vl . " (Date: " . $vl->date_vl . ")\n";

$vl_raw = DB::table('asset_values')->where('product_id', 10)->orderBy('date_vl', 'desc')->value('vl');
echo "VL Raw: " . sprintf("%.10f", $vl_raw) . "\n";
