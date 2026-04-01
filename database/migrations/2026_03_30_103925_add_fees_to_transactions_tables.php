<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFeesToTransactionsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('fees', 20, 2)->default(0)->after('amount');
        });

        Schema::table('transaction_supplementaires', function (Blueprint $table) {
            $table->decimal('fees', 20, 2)->default(0)->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('fees');
        });

        Schema::table('transaction_supplementaires', function (Blueprint $table) {
            $table->dropColumn('fees');
        });
    }
}
