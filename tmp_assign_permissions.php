<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use TCG\Voyager\Models\Permission;

$roleId = 5;

// All READ/BROWSE permissions
$permissions = Permission::where('key', 'like', 'browse_%')
    ->orWhere('key', 'like', 'read_%')
    ->pluck('id')->toArray();

// Sync permissions via DB specifically for the role
DB::table('permission_role')->where('role_id', $roleId)->delete();
foreach($permissions as $p) {
    DB::table('permission_role')->insert([
        'permission_id' => $p,
        'role_id' => $roleId
    ]);
}
echo "Synced " . count($permissions) . " permissions.\n";
