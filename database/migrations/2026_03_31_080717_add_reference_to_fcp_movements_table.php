<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddReferenceToFcpMovementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fcp_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('fcp_movements', 'reference')) {
                $table->string('reference')->nullable()->after('id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fcp_movements', function (Blueprint $table) {
            $table->dropColumn('reference');
        });
    }
}
