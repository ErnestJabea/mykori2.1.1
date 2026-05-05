<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$schema = DB::select('SHOW CREATE TABLE fcp_movements');
echo $schema[0]->{'Create Table'} . "\n";
