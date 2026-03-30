<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddValidationWorkflowToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('is_compliance_validated')->default(0)->after('status');
            $table->boolean('is_backoffice_validated')->default(0)->after('is_compliance_validated');
            $table->boolean('is_dg_validated')->default(0)->after('is_backoffice_validated');
            
            $table->timestamp('compliance_validated_at')->nullable()->after('is_dg_validated');
            $table->timestamp('backoffice_validated_at')->nullable()->after('compliance_validated_at');
            $table->timestamp('dg_validated_at')->nullable()->after('backoffice_validated_at');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
           $table->dropColumn(['is_compliance_validated', 'is_backoffice_validated', 'is_dg_validated', 'compliance_validated_at', 'backoffice_validated_at', 'dg_validated_at']);
        });
    }
}
