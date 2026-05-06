<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$prods = DB::table('products')->where('products_category_id', 1)->select('id', 'title')->get();
print_r($prods);
