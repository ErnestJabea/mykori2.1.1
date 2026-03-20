<?php

namespace App\Observers;

use App\Events\TransactionUpdatedDetails;
use App\Events\UserBalanceUpdatedDetails;
use App\Mail\MailValidationTransaction;
use App\Mail\MailValidationTransactionPmg;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Product;
use App\Events\UserBalanceUpdated;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class UpdateTransactionAchatObservateur
{

    /**
     * Handle the Transaction "updated" event.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return void
     */

    public function updated(Transaction $transaction)
    {
        // Obtenir la date et l'heure actuelle
        $date_validation = Carbon::now();

        // Obtenir le nouveau statut de la transaction
        $newStatus = $transaction->status;


        // Vérifier le nouveau statut de la transaction
        if ($newStatus === 'Succès' || $newStatus === 'Refusé') {
            event(new TransactionUpdatedDetails($transaction, $date_validation));

            // Envoi d'un e-mail d'activation ou de refus de la transaction (décommenter une fois que cette fonctionnalité est implémentée)
        } else {
            event(new TransactionUpdatedDetails($transaction, '0'));
        }
    }



}
