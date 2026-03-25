<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach(\TCG\Voyager\Models\Role::all() as $r) {
    echo $r->id . ": " . $r->display_name . " (" . $r->name . ")\n";
}
