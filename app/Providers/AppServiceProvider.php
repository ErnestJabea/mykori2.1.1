<?php

namespace App\Providers;

use App\Observers\UpdateTransactionAchatObservateur;
use App\Observers\ValidationTransactionAchatObservateur;
use App\Transaction;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //

        // Transaction::observe(UpdateTransactionAchatObservateur::class);
        Transaction::observe(ValidationTransactionAchatObservateur::class);
    }
}
