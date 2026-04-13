<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrmProspectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_prospects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commercial_id')->nullable(); // Le commercial qui suit ce prospect
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('amount_expected', 20, 2)->default(0); // Montant estimé de l'investissement
            $table->enum('status', ['new', 'negotiation', 'won', 'lost'])->default('new');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('commercial_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crm_prospects');
    }
}
