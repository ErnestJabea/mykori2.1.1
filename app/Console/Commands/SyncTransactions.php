<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Transaction;
use App\Models\FinancialMovement;

class SyncTransactions extends Command
{
    protected $signature = 'finance:sync-initial';
    protected $description = 'Synchronise les montants initiaux des transactions vers les mouvements financiers';

    public function handle()
    {
        $transactions = Transaction::where('status', 'Succès')->get();
        $count = 0;

        foreach ($transactions as $t) {
            $exists = FinancialMovement::where('transaction_id', $t->id)
                ->where('type', 'souscription_initiale')
                ->exists();

            if (!$exists) {
                FinancialMovement::create([
                    'transaction_id' => $t->id,
                    'type'           => 'souscription_initiale',
                    'amount'         => (float) $t->amount,
                    'capital_before' => 0,
                    'capital_after'  => (float) $t->amount,
                    'date_operation' => $t->date_validation ?? $t->created_at,
                    'interest_rate_at_moment' => $t->vl_buy,
                    'comment'        => "Migration automatique de l'investissement initial"
                ]);
                $count++;
            }
        }
        $this->info("$count transactions ont été synchronisées.");
    }
}