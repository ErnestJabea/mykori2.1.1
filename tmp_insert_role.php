<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    DB::table('roles')->insert([
        'id' => 5,
        'name' => 'compliance',
        'display_name' => 'Contrôle et Conformité',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "SUCCESS\n";
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
