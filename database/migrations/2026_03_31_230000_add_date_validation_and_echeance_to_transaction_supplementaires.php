<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDateValidationAndEcheanceToTransactionSupplementaires extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_supplementaires', function (Blueprint $table) {
            if (!Schema::hasColumn('transaction_supplementaires', 'date_validation')) {
                $table->date('date_validation')->nullable()->after('status');
            }
            if (!Schema::hasColumn('transaction_supplementaires', 'date_echeance')) {
                $table->date('date_echeance')->nullable()->after('date_validation');
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
        Schema::table('transaction_supplementaires', function (Blueprint $table) {
            $table->dropColumn(['date_validation', 'date_echeance']);
        });
    }
}
