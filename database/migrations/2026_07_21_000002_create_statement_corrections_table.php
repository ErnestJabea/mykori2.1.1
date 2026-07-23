<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatementCorrectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('statement_corrections')) {
            Schema::create('statement_corrections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('statement_version_id')->nullable();
                $table->unsignedBigInteger('user_id')->comment('Client ID');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('correction_type', 50)->default('source_data')->comment('source_data or adjustment_movement');
                $table->string('target_entity', 100)->comment('Transaction, FinancialMovement, etc.');
                $table->unsignedBigInteger('target_id')->nullable();
                $table->string('field_name', 100);
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->text('reason')->comment('Motif obligatoire');
                $table->text('description')->nullable();
                $table->string('attachment_path')->nullable();
                $table->json('simulation_payload')->nullable()->comment('Pre-calculated diff delta');
                $table->string('status', 50)->default('A_controler')->comment('A_controler, Valide, Rejete');
                $table->unsignedBigInteger('operator_id')->comment('Saisi par');
                $table->unsignedBigInteger('controller_id')->nullable()->comment('Valide/Rejete par');
                $table->timestamp('validated_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['target_entity', 'target_id']);
                $table->foreign('statement_version_id')->references('id')->on('statement_versions')->onDelete('cascade');
                $table->index('user_id');
                $table->index('product_id');
                $table->index('operator_id');
                $table->index('controller_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('statement_corrections');
    }
}
