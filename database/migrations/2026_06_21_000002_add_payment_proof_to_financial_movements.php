<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentProofToFinancialMovements extends Migration
{
    public function up()
    {
        Schema::table('financial_movements', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('comments');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->string('payment_proof_path')->nullable()->after('payment_reference');
            $table->foreignId('payment_recorded_by')->nullable()->after('payment_proof_path')->constrained('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('financial_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_recorded_by');
            $table->dropColumn(['payment_method', 'payment_reference', 'payment_proof_path']);
        });
    }
}
