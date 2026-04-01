<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\UserBalanceUpdatedMinus;
use App\Models\User;

class UpdateUserBalanceMinusListener
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
    public function handle(UserBalanceUpdatedMinus $event)
    {
        $transaction = $event->transaction;
        $user = User::find($transaction->user_id);
        $amount = $transaction->amount;
        $date_transaction = $event->date_transaction;

        // Mise à jour du solde de l'utilisateur
        $newBalance = max(0, $user->solde - $amount);

        $user->solde = $newBalance;

        // Vérifie si la sauvegarde de l'utilisateur s'est effectuée avec succès
        if ($user->save()) {
            // Met à jour la date de validation de la transaction (seulement si vide)
            $transaction->date_validation = $transaction->date_validation ?? $date_transaction;
            // Vérifie si la sauvegarde de la transaction s'est effectuée avec succès
            if ($transaction->save()) {
                // Gère l'erreur de sauvegarde de la transaction
                // Loggue ou notifie l'erreur, ou prend d'autres mesures nécessaires
            }
        } else {
            // Gère l'erreur de sauvegarde de l'utilisateur
            // Loggue ou notifie l'erreur, ou prend d'autres mesures nécessaires
        }
    }

}
