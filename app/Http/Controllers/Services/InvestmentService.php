<?php

namespace App\Services;

use App\Models\FinancialMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvestmentService
{
    /**
     * Calcule et applique la capitalisation à la date anniversaire.
     */
    public function refreshCapitalization($transaction)
    {
        $this->ensureInitialMovement($transaction);
        $startDate = Carbon::parse($transaction->date_validation);
        $now = Carbon::now();
        $annualRate = $transaction->vl_buy / 100;

        // Déterminer combien de dates anniversaires sont passées depuis le début
        $yearsElapsed = $startDate->diffInYears($now);

        for ($i = 1; $i <= $yearsElapsed; $i++) {
            $anniversaryDate = $startDate->copy()->addYears($i);

            // Vérifier si la capitalisation pour cette année spécifique a déjà été faite
            $alreadyProcessed = FinancialMovement::where('transaction_id', $transaction->id)
                ->where('type', 'capitalisation_interets')
                ->whereDate('date_operation', $anniversaryDate->toDateString())
                ->exists();

            if (!$alreadyProcessed) {
                $this->executeCapitalization($transaction, $anniversaryDate, $annualRate);
            }
        }
    }

    public function syncExistingTransactions()
        {
            // Récupérer toutes les transactions "Succès" qui n'ont pas encore de mouvement initial
            $transactions = \App\Models\Transaction::where('status', 'Succès')->get();

            foreach ($transactions as $transaction) {
                $exists = FinancialMovement::where('transaction_id', $transaction->id)
                    ->where('type', 'souscription_initiale')
                    ->exists();

                if (!$exists) {
                    FinancialMovement::create([
                        'transaction_id' => $transaction->id,
                        'type'           => 'souscription_initiale',
                        'amount'         => (float) $transaction->amount,
                        'capital_before' => 0,
                        'capital_after'  => (float) $transaction->amount,
                        'date_operation' => $transaction->date_validation ?? $transaction->created_at,
                        'interest_rate_at_moment' => $transaction->vl_buy,
                        'comment'        => "Migration : Investissement initial récupéré"
                    ]);
                }
            }
        }

    private function executeCapitalization($transaction, $date, $rate)
    {
        DB::transaction(function () use ($transaction, $date, $rate) {
            // 1. Trouver le dernier mouvement pour connaître le capital actuel
            $lastMovement = FinancialMovement::where('transaction_id', $transaction->id)
                ->orderBy('date_operation', 'desc')
                ->first();

            $capitalBefore = $lastMovement ? $lastMovement->capital_after : $transaction->amount;
            
            // 2. Calculer l'intérêt simple sur 1 an : Capital * Taux
            $interestAmount = round($capitalBefore * $rate, 2);

            // 3. Enregistrer le nouveau mouvement
            FinancialMovement::create([
                'transaction_id' => $transaction->id,
                'type' => 'capitalisation_interets',
                'amount' => $interestAmount,
                'capital_before' => $capitalBefore,
                'capital_after' => $capitalBefore + $interestAmount,
                'date_operation' => $date,
                'interest_rate_at_moment' => $transaction->vl_buy,
                'comment' => "Capitalisation annuelle automatique"
            ]);
        });
    }


    /**
         * Calcule la valorisation actuelle et la performance pour un FCP
         */
        public function getFcpPerformance($transaction)
        {
            // Récupérer la dernière VL enregistrée pour ce produit
            $latestAssetValue = \App\Models\AssetValue::where('product_id', $transaction->product_id)
                                ->orderBy('created_at', 'desc')
                                ->first();

            // Utiliser la dernière VL ou la VL d'achat par défaut
            $currentVl = $latestAssetValue ? (float)$latestAssetValue->vl : (float)$transaction->vl_buy;
            
            // Récupération sécurisée du nombre de parts (cast depuis varchar)
            $nbParts = (float)$transaction->nb_part;
            
            $valuationActuelle = $nbParts * $currentVl;
            $montantInvesti = (float)$transaction->amount;
            $plusValue = $valuationActuelle - $montantInvesti;

            return [
                'current_vl' => $currentVl,
                'nb_parts' => $nbParts,
                'valuation_actuelle' => $valuationActuelle,
                'plus_value' => $plusValue,
                'rendement_total' => $montantInvesti > 0 ? ($plusValue / $montantInvesti) * 100 : 0
            ];
        }

    private function ensureInitialMovement($transaction)
{
    $exists = FinancialMovement::where('transaction_id', $transaction->id)
                ->where('type', 'souscription_initiale')
                ->exists();

    if (!$exists) {
        FinancialMovement::create([
            'transaction_id' => $transaction->id,
            'type'           => 'souscription_initiale',
            'amount'         => $transaction->amount,
            'capital_before' => 0,
            'capital_after'  => $transaction->amount,
            'date_operation' => $transaction->date_validation,
            'interest_rate_at_moment' => $transaction->vl_buy,
            'comment'        => "Investissement initial"
        ]);
    }
}
}