<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Transaction;
use App\Services\InvestmentService;
use Illuminate\Support\Facades\DB;

class SyncFcpTransactions extends Command
{
    protected $signature = 'fcp:sync-history';
    protected $description = 'Migre les transactions FCP existantes vers la table fcp_movements';

    public function handle()
    {
        $service = new InvestmentService();
        
        // On récupère les transactions FCP (catégorie != 2) qui ont réussi
        // et qui ne sont pas encore dans fcp_movements
        $transactions = Transaction::where('status', 'Succès')
            ->whereHas('product', function($query) {
                $query->where('products_category_id', '!=', 2);
            })
            ->orderBy('date_validation', 'asc')
            ->get();

        $count = 0;

        foreach ($transactions as $t) {
            $exists = DB::table('fcp_movements')->where('transaction_id', $t->id)->exists();

            if (!$exists) {
                // Récupération du solde actuel de parts avant cette insertion
                $currentParts = $service->getCurrentParts($t->user_id, $t->product_id);
                
                $vlAtMoment = (float)$t->vl_buy > 0 ? (float)$t->vl_buy : 1;
                $nbPartsChange = (float)$t->amount / $vlAtMoment;

                DB::table('fcp_movements')->insert([
                    'transaction_id'  => $t->id,
                    'user_id'         => $t->user_id,
                    'product_id'      => $t->product_id,
                    'type'            => 'souscription',
                    'amount_xaf'      => (float)$t->amount,
                    'vl_applied'      => $vlAtMoment,
                    'nb_parts_change' => $nbPartsChange,
                    'nb_parts_total'  => $currentParts + $nbPartsChange,
                    'date_operation'  => $t->date_validation ?? $t->created_at,
                    'comment'         => "Migration historique : " . $t->title,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                $count++;
            }
        }

        $this->info("$count transactions FCP ont été synchronisées avec succès.");
    }
}