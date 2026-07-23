<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatementAuditLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('statement_audit_logs')) {
            Schema::create('statement_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('event_type', 50)->comment('DEMANDE_CORRECTION, SIMULATION, VALIDATION, REJET, RECALCUL, IMPRESSION_PDF');
                $table->unsignedBigInteger('statement_version_id')->nullable();
                $table->unsignedBigInteger('correction_id')->nullable();
                $table->unsignedBigInteger('user_id')->comment('Client ID concerné');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('target_entity', 100)->nullable();
                $table->unsignedBigInteger('target_id')->nullable();
                $table->string('field_name', 100)->nullable();
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->text('reason')->nullable();
                $table->text('comment')->nullable();
                $table->string('status_before', 50)->nullable();
                $table->string('status_after', 50)->nullable();
                $table->integer('version_number')->default(1);
                $table->unsignedBigInteger('operator_id')->comment('Auteur de l action');
                $table->unsignedBigInteger('controller_id')->nullable()->comment('Contrôleur si applicable');
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('action_at')->useCurrent();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('attachment_path')->nullable();
                $table->json('technical_context')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'event_type']);
                $table->index('user_id');
                $table->index('product_id');
                $table->index('operator_id');
                $table->index('controller_id');
                $table->index(['target_entity', 'target_id']);
                $table->foreign('statement_version_id')->references('id')->on('statement_versions')->onDelete('set null');
                $table->foreign('correction_id')->references('id')->on('statement_corrections')->onDelete('set null');
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
        Schema::dropIfExists('statement_audit_logs');
    }
}
