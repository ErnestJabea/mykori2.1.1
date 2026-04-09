<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFeesToFcpMovementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fcp_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('fcp_movements', 'fees')) {
                $table->decimal('fees', 20, 2)->default(0)->after('amount_xaf');
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
            if (Schema::hasColumn('fcp_movements', 'fees')) {
                $table->dropColumn('fees');
            }
        });
    }
}
