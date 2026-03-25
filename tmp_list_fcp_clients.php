<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductsCategory;
use App\Models\Product;
use App\Models\Transaction;

echo "--- CATEGORIES ---\n";
foreach(ProductsCategory::all() as $c) {
    echo $c->id . ": " . $c->abreviation . " (" . $c->title . ")\n";
}

echo "\n--- FCP PRODUCTS ---\n";
foreach(Product::where('products_category_id', 1)->get() as $p) {
    echo $p->id . ": " . $p->title . "\n";
}

echo "\n--- FCP TRANSACTIONS ---\n";
foreach(Transaction::whereHas('product', fn($q) => $q->where('products_category_id', 1))->get() as $t) {
    echo "ID: " . $t->id . " | User ID: " . $t->user_id . " | Product ID: " . $t->product_id . " | Status: " . $t->status . "\n";
}
