<?php

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

try {
    if (!Schema::hasTable('customer_portfolios')) {
        Schema::create('customer_portfolios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); 
            $table->string('type'); 
            $table->string('reference')->unique(); 
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        echo "SUCCESS: Table 'customer_portfolios' créée avec succès via Web-Trigger.";
    } else {
        echo "ALREADY_EXISTS: La table 'customer_portfolios' existe déjà.";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
