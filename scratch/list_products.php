<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$products = App\Models\Product::all();
foreach($products as $p) {
    echo "ID: {$p->id} | Title: {$p->title} | Cat: {$p->products_category_id}\n";
}
