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
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->enum('type', [
                'souscription',
                'souscription_initiale',
                'versement_libre',
                'rachat_partiel',
                'rachat_total',
                'capitalisation_interets',
                'frais_gestion',
                'precompte_interets',
                'paiement_interets',
                'liquidite_interets',
                'remboursement',
                'dividende_interets'
            ]);
            $table->decimal('amount', 15, 2);
            $table->decimal('capital_before', 15, 2);
            $table->decimal('capital_after', 15, 2);
            $table->dateTime('date_operation');
            $table->decimal('interest_rate_at_moment', 8, 4)->nullable();
            $table->text('comments')->nullable();
            $table->text('comment')->nullable();
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
