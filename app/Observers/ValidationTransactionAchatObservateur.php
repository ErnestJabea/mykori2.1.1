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
use App\Events\UserBalanceUpdatedMinus;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ValidationTransactionAchatObservateur
{

    /**
     * Handle the Transaction "updated" event.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return void
     */

    /*public function updated(Transaction $transaction)
    {

        // Obtenir le nouveau statut de la transaction
        $newStatus = $transaction->status;

        // Obtenir les informations du produit associé à la transaction
        $product = Product::find($transaction->product_id);

        // Obtenir les informations du client associé à la transaction
        $customer = User::find($transaction->user_id);

        // Nom du client
        $nom_client = $customer->name;

        // Date de la transaction
        $date_transaction = $transaction->created_at;

        // Nom du produit
        $nom_produit = $product->title;

        // Référence de la transaction
        $ref_transaction = $transaction->ref;

        // Montant de la transaction
        $montantTransaction = $transaction->amount;

        // Variable pour stocker la valeur du produit ou du pourcentage de réduction
        $vl = '';

        // Vérifier le nouveau statut de la transaction
        if ($newStatus === 'Succès') {
            // Déterminer si la transaction concerne un produit de type 1 ou 2
            $vl = ($product->products_category_id == 1) ? "XAF " . $transaction->vl_buy : $transaction->vl_buy . "%";

            // Calculer la différence de solde du client en fonction du nouveau statut de la transaction

            // Mettre à jour le solde du client en garantissant qu'il ne devienne jamais négatif
            event(new UserBalanceUpdated($transaction, $date_transaction));

            // Envoi d'un e-mail d'activation ou de refus de la transaction (décommenter une fois que cette fonctionnalité est implémentée)
            Mail::to($customer->email)->send(new MailValidationTransaction($nom_client, $date_transaction, $newStatus, $nom_produit, $vl, $ref_transaction, $montantTransaction, number_format($transaction->nb_part, 0, ' ', ' ')));
        } elseif ($newStatus === 'Refusé' || $newStatus === 'En attente') {
            // Mettre à jour le solde du client en cas de statut "En attente"
            event(new UserBalanceUpdatedMinus($transaction, "0"));
        }
    }
 */



}
