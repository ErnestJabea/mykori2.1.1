<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerPortfoliosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_portfolios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Relation avec la table users (le compte mail unique)
            $table->string('type'); // 'PMG' ou 'FCP'
            $table->string('reference')->unique(); // La référence auto: PMG0001, FCP0001...
            $table->string('status')->default('active');
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
        Schema::dropIfExists('customer_portfolios');
    }
}
