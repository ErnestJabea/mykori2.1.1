<?php
namespace App\Listeners;

use App\Mail\MailValidationTransactionPmg;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Mail\MailValidationTransaction;
use App\Events\UserBalanceUpdated;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UpdateUserBalanceListener
{
    /**
     * Handle the event.
     *
     * @param  UserBalanceUpdated  $event
     * @return void
     */
    public function handle(UserBalanceUpdated $event)
    {
        DB::transaction(function () use ($event) {
            $user = User::find($event->transaction->user_id);
            $ancien_solde_fcp =  DB::table('users')
                ->where('id', $user->id)->value('solde');
            $ancien_solde_pmg =  DB::table('users')->where('id', $user->id)
                ->value('solde_pmg');
            $userById = DB::table('users')->where('id', $user->id)
                ->value('id');
            $transaction = $event->transaction;
            $amount = $event->transaction->amount;
            $product = Product::find($transaction->product_id);
            $customer = User::find($transaction->user_id);
            $nom_client = $customer->name;
            $date_transaction = Carbon::now();
            $nom_produit = $product->title;
            $ref_transaction = $transaction->ref;
            $montantTransaction = $transaction->amount;
            $vl = ($product->products_category_id == 1) ? "XAF " . $transaction->vl_buy : $transaction->vl_buy . "%";
            $dateValidation = Carbon::now();

            if ($transaction->isDirty('status')) {
                $oldStatus = $transaction->getOriginal('status');
                $newStatus = $transaction->status;

                if ($newStatus === 'Succès') {
                    // Logique pour mettre à jour le solde de l'utilisateur en fonction de la transaction
                    //Mise à jour de la date de validation
                    $transaction->date_validation = $date_transaction;
                    // Mettre à jour le solde de l'utilisateur et la transaction dans une transaction unique

                    if ($product->products_category_id == 1) {
                        $newBalance = $ancien_solde_fcp + $amount;
                        Log::info('Ancien solde', ['solde' => $ancien_solde_fcp, 'solde_pmg' => $ancien_solde_pmg]);
                        Log::info('Montant de la transaction', ['montantTransaction' => $montantTransaction]);


                        //dd("Ancien solde : " . $user->solde . ", Montant : " . $amount . ", Nouveau solde : " . $newBalance);
                        DB::table('users')
                            ->where('id', $userById)
                            ->update(['solde' => $newBalance]);

                        // Enregistrement du mouvement FCP
                        (new \App\Services\InvestmentService())->recordFcpMovement($transaction, 'souscription');

                        //$user->refresh();
                        Log::info('Nouveau solde mis à jour', ['newBalance' => $user->solde]);

                    } else {
                        $newBalance_ = max(0, $ancien_solde_pmg + $amount);

                        DB::table('users')
                            ->where('id', $userById)
                            ->update(['solde_pmg' => $newBalance_]);

                        // Enregistrement du mouvement PMG (MANDAT)
                        (new \App\Services\InvestmentService())->recordPmgMovement($transaction, 'souscription_initiale');
                    }

                    DB::table('transactions')
                        ->where('id', $transaction->id)
                        ->update(['date_validation' => $date_transaction]);

                    // Envoyer un e-mail de confirmation
                    if ($product->products_category_id == 1) {
                       // Mail::to($user->email)->send(new MailValidationTransaction($nom_client, $date_transaction, $newStatus, $nom_produit, $vl, $ref_transaction, $montantTransaction, number_format($transaction->nb_part, 0, ' ', ' ')));
                    } else {
                        // Mail::to($user->email)->send(new MailValidationTransactionPmg($nom_client, $date_transaction, $newStatus, $nom_produit, $vl, $ref_transaction, $montantTransaction, number_format($transaction->nb_part, 0, ' ', ' ')));
                    }
                } else if ($newStatus === 'En attente' || $newStatus === 'Refusé') {
                    // Logique pour mettre à jour le solde de l'utilisateur en fonction de la transaction
                    // Mettre à jour le solde de l'utilisateur dans une transaction unique
                    if ($product->products_category_id == 1) {
                        $newBalance = max(0, $user->solde - $amount);
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update(['solde' => $newBalance]);

                    } else {
                        $newBalance_ = max(0, $user->solde_pmg + $amount);
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update(['solde_pmg' => $newBalance_]);
                    }
                    DB::table('transactions')
                        ->where('id', $transaction->id)
                        ->update(['date_validation' => '']);

                    // Envoyer un e-mail de confirmation
                    if ($product->products_category_id == 1) {
                        //Mail::to($user->email)->send(new MailValidationTransaction($nom_client, $date_transaction, $newStatus, $nom_produit, $vl, $ref_transaction, $montantTransaction, number_format($transaction->nb_part, 0, ' ', ' ')));
                    } else {
                        //Mail::to($user->email)->send(new MailValidationTransactionPmg($nom_client, $date_transaction, $newStatus, $nom_produit, $vl, $ref_transaction, $montantTransaction, number_format($transaction->nb_part, 0, ' ', ' ')));
                    }
                }
            }
        });
    }
}
