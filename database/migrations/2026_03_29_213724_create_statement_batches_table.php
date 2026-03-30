<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatementBatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('statement_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Executed by
            $table->string('periode'); // Ex: Janvier 2026
            $table->integer('client_count');
            $table->integer('success_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->string('report_path')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('statement_batches');
    }
}
