<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $sections = [
            'asset_manager' => ['order' => 10, 'roles' => [1, 3, 4, 8]],
            'compliance'    => ['order' => 10, 'roles' => [1, 5, 8]],
            'backoffice'    => ['order' => 10, 'roles' => [1, 6, 8]],
            'dg'            => ['order' => 10, 'roles' => [1, 7, 8]],
            'admin'         => ['order' => 10, 'roles' => [1, 8]],
        ];

        foreach ($sections as $section => $info) {
            $exists = DB::table('front_menus')
                ->where('section', $section)
                ->where('route', 'control-adjustments.index')
                ->exists();

            if (!$exists) {
                DB::table('front_menus')->insert([
                    'title'       => 'Contrôle & Ajustements',
                    'route'       => 'control-adjustments.index',
                    'icon'        => 'las la-sliders-h',
                    'section'     => $section,
                    'roles_json'  => json_encode($info['roles']),
                    'permission'  => 'view_control_adjustments',
                    'order'       => $info['order'],
                    'is_active'   => 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down()
    {
        DB::table('front_menus')
            ->where('route', 'control-adjustments.index')
            ->delete();
    }
};
