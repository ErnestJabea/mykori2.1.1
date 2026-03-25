<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$r = TCG\Voyager\Models\Role::where('name', 'compliance')->first();
if ($r) {
    echo "Role Name: " . $r->name . "\n";
    echo "Role ID: " . $r->id . "\n";
} else {
    echo "Role not found.\n";
}
