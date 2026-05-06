<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tables = DB::select('SHOW TABLES');
foreach($tables as $t) {
    $name = array_values((array)$t)[0];
    if(str_contains($name, 'rachat')) {
        echo $name . "\n";
    }
}
