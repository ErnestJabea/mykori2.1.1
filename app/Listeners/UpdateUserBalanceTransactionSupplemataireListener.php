<?php
namespace App\Listeners;

use App\Events\UserBalanceTransactionSupplemataireUpdated;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Services\InvestmentService;

class UpdateUserBalanceTransactionSupplemataireListener
{
    /**
     * Handle the event.
     *
     * @param  UserBalanceTransactionSupplemataireUpdated  $event
     * @return void
     */
    public function handle(UserBalanceTransactionSupplemataireUpdated $event)
    {
        DB::transaction(function () use ($event) {
            $transaction = $event->transaction;
            $user = User::find($transaction->user_id);
            $amount = $transaction->amount;
            $product = Product::find($transaction->product_id);

            if ($transaction->isDirty('status')) {
                $newStatus = $transaction->status;

                if ($newStatus === 'Succès') {
                    if ($product->products_category_id == 1) {
                        $newBalance = max(0, $user->solde + $amount);
                        $user->update(['solde' => $newBalance]);
                        
                        // Enregistrement du mouvement FCP
                        (new InvestmentService())->recordFcpMovement($transaction, 'versement_libre');
                    } else {
                        $newBalancePmg = max(0, $user->solde_pmg + $amount);
                        $user->update(['solde_pmg' => $newBalancePmg]);
                    }

                    $transaction->update(['date_validation' => $event->date_transaction]);

                } else if ($newStatus === 'En attente' || $newStatus === 'Refusé') {
                    if ($product->products_category_id == 1) {
                        $newBalance = max(0, $user->solde - $amount);
                        $user->update(['solde' => $newBalance]);
                    } else {
                        $newBalancePmg = max(0, $user->solde_pmg - $amount); // Correction potentielle ici aussi
                        $user->update(['solde_pmg' => $newBalancePmg]);
                    }
                    $transaction->update(['date_validation' => null]);
                }
            }
        });
    }
}
