<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

$cols = Schema::getColumnListing('fcp_movements');
print_r($cols);
$cols2 = Schema::getColumnListing('financial_movements');
print_r($cols2);
