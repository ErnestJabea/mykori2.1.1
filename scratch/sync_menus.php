<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new \App\Http\Controllers\AdminFrontendController();
// Use reflection to call private method
$reflection = new \ReflectionClass($controller);
$method = $reflection->getMethod('syncAdminMenus');
$method->setAccessible(true);
$method->invoke($controller);

echo "Menus synchronized successfully.\n";

$menus = \App\Models\FrontMenu::all();
foreach ($menus as $menu) {
    echo "ID: {$menu->id} | Title: {$menu->title} | Section: {$menu->section} | Route: {$menu->route} | Roles: " . json_encode($menu->roles_json) . "\n";
}
