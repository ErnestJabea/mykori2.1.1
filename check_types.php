<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

$types = DB::table('financial_movements')->distinct()->pluck('type');
echo "EXHAUSTIVE MOVEMENT TYPES LIST:\n";
foreach ($types as $type) {
    echo "- $type\n";
}
echo "\n";
