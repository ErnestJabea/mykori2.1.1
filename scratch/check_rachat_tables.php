<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::select('SHOW TABLES');
foreach($tables as $table) {
    foreach($table as $key => $value) {
        if(str_contains(strtolower($value), 'rachat')) {
            echo $value . PHP_EOL;
        }
    }
}
