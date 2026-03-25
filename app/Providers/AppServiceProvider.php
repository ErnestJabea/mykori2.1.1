<?php

namespace App\Providers;

use App\Observers\UpdateTransactionAchatObservateur;
use App\Observers\ValidationTransactionAchatObservateur;
use App\Models\Transaction;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

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
        Paginator::useTailwind();
        //

        // Transaction::observe(UpdateTransactionAchatObservateur::class);
        Transaction::observe(ValidationTransactionAchatObservateur::class);
    }
}
