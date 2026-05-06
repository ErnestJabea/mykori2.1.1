<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$clients = DB::table('users')->where('name', 'LIKE', '%KORI ASSET%')->select('id', 'name')->get();
print_r($clients);
