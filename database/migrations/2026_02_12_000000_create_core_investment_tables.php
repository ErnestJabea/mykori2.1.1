<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoreInvestmentTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('products_categories')) {
            Schema::create('products_categories', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('slug')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('products_category_id')->nullable()->index();
                $table->string('title')->nullable();
                $table->string('slug')->nullable()->index();
                $table->decimal('vl', 20, 6)->nullable();
                $table->decimal('nb_action', 20, 6)->default(0);
                $table->integer('duree')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('ref')->nullable()->index();
                $table->string('payment_mode')->nullable();
                $table->decimal('amount', 20, 2)->default(0);
                $table->string('status')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->decimal('vl_buy', 20, 6)->default(0);
                $table->decimal('nb_part', 24, 10)->default(0);
                $table->date('date_validation')->nullable();
                $table->decimal('montant_initiale', 20, 2)->nullable();
                $table->integer('type')->nullable();
                $table->integer('duree')->nullable();
                $table->date('date_echeance')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transaction_supplementaires')) {
            Schema::create('transaction_supplementaires', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transaction_id')->nullable()->index();
                $table->string('title')->nullable();
                $table->string('ref')->nullable()->index();
                $table->string('payment_mode')->nullable();
                $table->decimal('amount', 20, 2)->default(0);
                $table->string('status')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->decimal('vl_buy', 20, 6)->default(0);
                $table->decimal('nb_part', 24, 10)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('asset_values')) {
            Schema::create('asset_values', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->decimal('vl', 20, 6)->default(0);
                $table->date('date_vl')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('fcp_movements')) {
            Schema::create('fcp_movements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transaction_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('type')->nullable()->index();
                $table->decimal('amount_xaf', 20, 2)->default(0);
                $table->decimal('vl_applied', 20, 6)->default(0);
                $table->decimal('nb_parts_change', 24, 10)->default(0);
                $table->decimal('nb_parts_total', 24, 10)->default(0);
                $table->dateTime('date_operation')->nullable()->index();
                $table->text('comment')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('fcp_movements');
        Schema::dropIfExists('asset_values');
        Schema::dropIfExists('transaction_supplementaires');
        Schema::dropIfExists('financial_movements');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('products');
        Schema::dropIfExists('products_categories');
    }
}
