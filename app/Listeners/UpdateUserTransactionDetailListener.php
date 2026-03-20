<?php

namespace App\Listeners;

use App\Events\TransactionUpdatedDetails;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\UserBalanceUpdatedDetails;

class UpdateUserTransactionDetailListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(TransactionUpdatedDetails $event)
    {
        $transaction = $event->transaction;
        $date_transaction = $event->date_transaction;

        $transaction->update(['date_validation', $date_transaction]);


    }
}
