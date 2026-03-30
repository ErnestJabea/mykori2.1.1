<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateFrontMenusTable extends Migration
{
    public function up()
    {
        Schema::create('front_menus', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('route')->nullable();
            $table->string('icon')->default('las la-link');
            $table->string('section'); // e.g., asset_manager, compliance, backoffice, dg, admin
            $table->json('roles_json')->nullable();
            $table->string('permission')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed initial data based on existing static config
        $initialMenus = [
            // Asset Manager
            ['title' => 'Tableau de bord', 'route' => 'asset-manager', 'icon' => 'las la-home', 'section' => 'asset_manager', 'roles_json' => json_encode([3, 4, 7, 8]), 'order' => 1],
            ['title' => 'Clients', 'route' => 'customer', 'icon' => 'las la-piggy-bank', 'section' => 'asset_manager', 'roles_json' => json_encode([3, 4, 7, 8]), 'order' => 2],
            ['title' => 'Créer un client', 'route' => 'asset-manager.create-customer', 'icon' => 'las la-user-plus', 'section' => 'asset_manager', 'roles_json' => json_encode([3, 4, 8]), 'order' => 3],
            
            // Compliance
            ['title' => 'Tableau de bord', 'route' => 'compliance.dashboard', 'icon' => 'las la-shield-alt', 'section' => 'compliance', 'roles_json' => json_encode([5, 8]), 'order' => 1],
            ['title' => 'Audit Clients', 'route' => 'compliance.clients', 'icon' => 'las la-user-check', 'section' => 'compliance', 'roles_json' => json_encode([5, 8]), 'order' => 2],
            
            // Backoffice
            ['title' => 'Dashboard BO', 'route' => 'backoffice.dashboard', 'icon' => 'las la-tachometer-alt', 'section' => 'backoffice', 'roles_json' => json_encode([6, 8]), 'order' => 1],
            ['title' => 'Validations', 'route' => 'backoffice.transactions', 'icon' => 'las la-list', 'section' => 'backoffice', 'roles_json' => json_encode([6, 8]), 'order' => 2],
            
            // DG
            ['title' => 'Dashboard D.G.', 'route' => 'dg.dashboard', 'icon' => 'las la-gavel', 'section' => 'dg', 'roles_json' => json_encode([7, 8]), 'order' => 1],
            
            // Admin
            ['title' => 'Système', 'route' => 'admin.front.dashboard', 'icon' => 'las la-tools', 'section' => 'admin', 'roles_json' => json_encode([1, 8]), 'order' => 1, 'permission' => 'view_admin_frontend'],
            ['title' => 'Utilisateurs', 'route' => 'admin.front.users', 'icon' => 'las la-user-cog', 'section' => 'admin', 'roles_json' => json_encode([1, 8]), 'order' => 2, 'permission' => 'manage_users'],
            ['title' => 'Gestion Menus', 'route' => 'admin.front.menus', 'icon' => 'las la-bars', 'section' => 'admin', 'roles_json' => json_encode([1, 8]), 'order' => 3, 'permission' => 'manage_menus'],
            ['title' => 'Log d\'Audit', 'route' => 'admin.front.logs', 'icon' => 'las la-history', 'section' => 'admin', 'roles_json' => json_encode([1, 8]), 'order' => 4, 'permission' => 'view_audit_logs'],
        ];

        foreach ($initialMenus as $menu) {
            DB::table('front_menus')->insert($menu + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('front_menus');
    }
}
