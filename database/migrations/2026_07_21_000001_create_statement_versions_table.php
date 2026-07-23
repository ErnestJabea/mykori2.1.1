<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatementVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('statement_versions')) {
            Schema::create('statement_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->comment('Client ID');
                $table->unsignedBigInteger('product_id')->nullable()->comment('Product ID if specific');
                $table->string('period_name', 50)->comment('e.g. 2026-06 or Juin 2026');
                $table->date('statement_date');
                $table->integer('version_number')->default(1);
                $table->string('status', 50)->default('Brouillon')->comment('Brouillon, Calcule, A_controler, A_corriger, Valide, Verrouille, Envoye, Remplace');
                $table->string('pdf_path')->nullable();
                $table->string('sha256_hash', 64)->nullable()->comment('Cryptographic footprint of PDF');
                $table->string('payload_sha256_hash', 64)->nullable()->comment('Cryptographic footprint of calculated payload');
                $table->json('summary_payload')->nullable()->comment('Snapshot of calculated metrics');
                $table->timestamp('sent_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('validated_by')->nullable();
                $table->unsignedBigInteger('replaces_version_id')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'period_name']);
                $table->unique(['user_id', 'product_id', 'period_name', 'version_number'], 'stmt_versions_scope_unique');
                $table->index('status');
                $table->index('user_id');
                $table->index('product_id');
                $table->index('created_by');
                $table->index('validated_by');
                $table->foreign('replaces_version_id')->references('id')->on('statement_versions')->onDelete('set null');
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
        Schema::dropIfExists('statement_versions');
    }
}
