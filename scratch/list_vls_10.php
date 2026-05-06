<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$vls = DB::table('asset_values')->where('product_id', 10)->orderBy('date_vl', 'desc')->get();
foreach($vls as $vl) {
    echo "ID: {$vl->id} | Date: {$vl->date_vl} | VL: {$vl->vl}\n";
}
