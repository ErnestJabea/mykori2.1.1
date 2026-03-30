<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertNewRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('roles')->updateOrInsert(
            ['id' => 6],
            ['name' => 'backoffice', 'display_name' => 'Backoffice', 'created_at' => now(), 'updated_at' => now()]
        );

        DB::table('roles')->updateOrInsert(
            ['id' => 7],
            ['name' => 'director_general', 'display_name' => 'Directeur Général', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down()
    {
        DB::table('roles')->whereIn('id', [6, 7])->delete();
    }
}
