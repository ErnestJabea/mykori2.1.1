<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach(DB::select('DESCRIBE roles') as $f) {
    echo $f->Field . " | " . $f->Extra . "\n";
}
