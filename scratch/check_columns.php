<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "Colonnes 'transactions':\n";
print_r(Schema::getColumnListing('transactions'));

echo "\nColonnes 'transactions_supplementaires':\n";
print_r(Schema::getColumnListing('transactions_supplementaires'));
