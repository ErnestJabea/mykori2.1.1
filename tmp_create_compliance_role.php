<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use TCG\Voyager\Models\Role;
use TCG\Voyager\Models\Permission;

try {
    $role = Role::where('name', 'compliance')->first();
    if (!$role) {
        $role = new Role();
        $role->name = 'compliance';
        $role->display_name = 'Contrôle et Conformité';
        $role->save();
        echo "Role 'compliance' created with ID: " . $role->id . "\n";
    } else {
        echo "Role 'compliance' already exists with ID: " . $role->id . "\n";
    }

    $permissions = Permission::whereIn('key', [
        'browse_admin',
        'browse_users', 'read_users',
        'browse_transactions', 'read_transactions',
        'browse_products', 'read_products',
        'browse_asset_values', 'read_asset_values',
    ])->pluck('id')->toArray();

    $role->permissions()->sync($permissions);
    echo "Permissions Synced.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
