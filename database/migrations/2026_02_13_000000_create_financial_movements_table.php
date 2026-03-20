<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinancialMovementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('financial_movements', function (Blueprint $table) {
            $table->id();
            // transactions.id is int unsigned in the existing schema, use unsignedInteger
            $table->unsignedInteger('transaction_id');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->enum('type', [
                'souscription',
                'versement_libre',
                'rachat_partiel',
                'rachat_total',
                'capitalisation_interets',
                'frais_gestion'
            ]);
            $table->decimal('amount', 15, 2);
            $table->decimal('capital_before', 15, 2);
            $table->decimal('capital_after', 15, 2);
            $table->date('date_operation');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('financial_movements');
    }
}
