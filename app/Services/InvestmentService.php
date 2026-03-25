<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\FinancialMovement;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class InvestmentService
{
    // Calculer la valeur d'un portefeuille à une date T
    public function getValuation($transactionId, $targetDate)
    {
        $transaction = Transaction::find($transactionId);
        if (!$transaction) {
            throw new Exception('Transaction not found');
        }

        $target = Carbon::parse($targetDate)->endOfDay();

        $movements = DB::table('financial_movements')
            ->where('transaction_id', $transactionId)
            ->where('date_operation', '<=', $target->toDateString())
            ->orderBy('date_operation')
            ->orderBy('id')
            ->get();

        // Solde initial
        $balance = 0.0;
        $accruedInterest = 0.0;

        // Si le transaction a un taux (vl_buy), on l'utilise comme taux annuel
        $annualRate = isset($transaction->vl_buy) ? (float) $transaction->vl_buy : 0.0;
        $dailyRate = $annualRate / 100.0 / 360.0;

        // Déterminer la date de départ pour le calcul d'intérêts
        $lastDate = isset($transaction->date_validation) ? Carbon::parse($transaction->date_validation) : ($movements->count() ? Carbon::parse($movements->first()->date_operation) : $target);

        foreach ($movements as $m) {
            $mDate = Carbon::parse($m->date_operation);
            $days = max(0, $mDate->diffInDays($lastDate));

            // Intérêts courus depuis la dernière opération
            if ($days > 0 && $dailyRate > 0) {
                $accruedInterest += $balance * $dailyRate * $days;
            }

            // Appliquer le mouvement (entrées vs sorties)
            if (in_array($m->type, ['souscription', 'versement_libre', 'capitalisation_interets'])) {
                $balance += (float) $m->amount;
            } else {
                // rachat_partiel, rachat_total, frais_gestion -> sorties
                $balance -= (float) $m->amount;
            }

            $lastDate = $mDate;
        }

        // Intérêts depuis la dernière opération jusqu'à la date cible
        $days = max(0, $target->diffInDays($lastDate));
        if ($days > 0 && $dailyRate > 0) {
            $accruedInterest += $balance * $dailyRate * $days;
        }

        return [
            'capital' => round($balance, 2),
            'accrued_interest' => round($accruedInterest, 2),
            'valuation' => round($balance + $accruedInterest, 2),
        ];
    }

    /**
     * Calcule la valorisation actuelle et la performance pour un FCP
     */
    public function getFcpPerformance($transaction)
    {
        // Récupérer la toute dernière VL enregistrée (le dernier vendredi)
        $latestAssetValue = \App\Models\AssetValue::where('product_id', $transaction->product_id)
            ->orderBy('date_vl', 'desc') // On suppose que vous avez une colonne date_vl
            ->first();

        $currentVl = $latestAssetValue ? (float)$latestAssetValue->vl : (float)$transaction->vl_buy;

        // Le nombre de parts est fixe depuis la souscription (sauf rachat/versement)
        $nbParts = (float)$transaction->nb_part;

        $valuationActuelle = $nbParts * $currentVl;
        $montantInvesti = (float)$transaction->amount;
        $plusValue = $valuationActuelle - $montantInvesti;

        return [
            'current_vl' => $currentVl,
            'date_vl' => $latestAssetValue ? $latestAssetValue->date_vl : $transaction->date_validation,
            'nb_parts' => $nbParts,
            'valuation_actuelle' => $valuationActuelle,
            'plus_value' => $plusValue,
            'rendement_total' => $montantInvesti > 0 ? ($plusValue / $montantInvesti) * 100 : 0
        ];
    }


    public function recordFcpMovement($transaction, $type = 'souscription')
    {
        // On récupère la VL actuelle (du dernier vendredi)
        $latestVl = \App\Models\AssetValue::where('product_id', $transaction->product_id)
            ->orderBy('date_vl', 'desc')
            ->first();

        $vl = $latestVl ? (float)$latestVl->vl : (float)$transaction->vl_buy;

        // Calcul précis des parts
        $nbParts = (float)$transaction->nb_part > 0 ? (float)$transaction->nb_part : (float)$transaction->amount / $vl;

        return DB::table('fcp_movements')->insert([
            'transaction_id' => $transaction->id,
            'user_id'        => $transaction->user_id,
            'product_id'     => $transaction->product_id, // Ajout du product_id manquant
            'type'           => $type,
            'amount_xaf'     => $transaction->amount,
            'vl_applied'     => $vl,
            'nb_parts_change' => $nbParts,
            'nb_parts_total' => $this->getCurrentParts($transaction->user_id, $transaction->product_id) + $nbParts,
            'date_operation' => $transaction->date_validation ?? now(),
            'comment'        => "Validation de parts FCP via " . $transaction->payment_mode
        ]);
    }

    /**
     * Récupère le solde actuel de parts pour un client sur un produit donné.
     */
    public function getCurrentParts($userId, $productId)
    {
        return DB::table('fcp_movements')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->sum('nb_parts_change') ?? 0;
    }

    /**
     * Calcule et applique la capitalisation à la date anniversaire.
     */
    public function refreshCapitalization($transaction)
    {
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

    public function syncExistingTransactions()
    {
        // Récupérer toutes les transactions "Succès" qui n'ont pas encore de mouvement initial
        $transactions = Transaction::where('status', 'Succès')->get();

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

    /**
     * Exécute un rachat (partiel ou total)
     */
    public function executeRedemption($transactionId, $amountRequested, $fees = 0)
    {
        return DB::transaction(function () use ($transactionId, $amountRequested, $fees) {
            // 1. Récupérer le dernier état du capital
            $lastMovement = FinancialMovement::where('transaction_id', $transactionId)
                ->orderBy('date_operation', 'desc')
                ->first();

            $currentCapital = $lastMovement ? $lastMovement->capital_after : 0;

            // 2. Vérification de sécurité
            if ($amountRequested > $currentCapital) {
                throw new \Exception("Fonds insuffisants pour ce rachat.");
            }

            // 3. Enregistrer le mouvement de rachat
            return FinancialMovement::create([
                'transaction_id' => $transactionId,
                'type'           => 'rachat_partiel',
                'amount'         => -$amountRequested, // Négatif car c'est une sortie
                'capital_before' => $currentCapital,
                'capital_after'  => $currentCapital - $amountRequested,
                'date_operation' => now(),
                'comment'        => "Rachat demandé par le client. Frais appliqués: $fees"
            ]);
        });
    }

    public function getFcpFullHistory($transaction)
    {
        // 1. Récupérer les flux réels (achats/ventes)
        $movements = DB::table('fcp_movements')
            ->where('transaction_id', $transaction->id)
            ->get();

        // 2. Récupérer l'évolution des VL depuis la souscription
        $dateDebut = $movements->min('date_operation');
        $vls = DB::table('asset_values')
            ->where('product_id', $transaction->product_id)
            ->where('date_vl', '>=', $dateDebut)
            ->orderBy('date_vl', 'asc')
            ->get();

        // 3. Fusionner pour créer un historique chronologique
        // On affiche la valorisation du portefeuille à chaque nouvelle VL
        return $vls->map(function ($vl) use ($movements) {
            $partsAuMoment = $movements->where('date_operation', '<=', $vl->date_vl)->sum('nb_parts_change');
            return [
                'date' => $vl->date_vl,
                'libelle' => 'Valorisation hebdomadaire',
                'vl' => $vl->vl,
                'parts' => $partsAuMoment,
                'valeur' => $partsAuMoment * $vl->vl
            ];
        });
    }

    /**
     * Récupère l'historique FCP pour un produit et une période donnés
     */
    public function getFcpStatementData($userId, $productId, $startDate, $endDate)
    {
        // 1. Mouvements durant la période
        $movements = DB::table('fcp_movements')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->whereBetween('date_operation', [$startDate, $endDate])
            ->orderBy('date_operation', 'asc')
            ->get();

        // 2. Solde de parts au début de la période (pour le report)
        $partsAtStart = DB::table('fcp_movements')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('date_operation', '<', $startDate)
            ->sum('nb_parts_change') ?? 0;

        // 3. VL à la date de fin (ou la plus proche)
        $latestVl = DB::table('asset_values')
            ->where('product_id', $productId)
            ->where('date_vl', '<=', $endDate)
            ->orderBy('date_vl', 'desc')
            ->first();

        return [
            'movements' => $movements,
            'parts_at_start' => $partsAtStart,
            'latest_vl' => $latestVl,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
    }
    /**
     * Récupère le portefeuille consolidé FCP d'un utilisateur.
     */
    public function getConsolidatedFcpPortfolio($userId)
    {
        $currentDate = Carbon::now();

        // 1. On récupère tous les produits FCP que l'utilisateur possède via les mouvements
        $productIds = DB::table('fcp_movements')
            ->where('user_id', $userId)
            ->distinct()
            ->pluck('product_id');

        $portfolio = [];

        foreach ($productIds as $productId) {
            $product = \App\Models\Product::find($productId);
            if (!$product) continue;

            $stats = $this->getCurrentStatus($userId, $productId);

            $latestVlEntry = \App\Models\AssetValue::where('product_id', $productId)
                ->orderBy('created_at', 'desc')
                ->first();

            $currentVl = $latestVlEntry ? (float)$latestVlEntry->vl : 0;
            
            // Récupérer l'avant-dernière VL pour la performance hebdo
            $previousVlEntry = \App\Models\AssetValue::where('product_id', $productId)
                ->where('id', '!=', $latestVlEntry ? $latestVlEntry->id : 0)
                ->orderBy('created_at', 'desc')
                ->first();
            $prevVl = $previousVlEntry ? (float)$previousVlEntry->vl : $currentVl;

            $valuation = $stats['parts'] * $currentVl;
            $invested = $stats['invested'];
            $gainTotal = $valuation - $invested;
            $gainHebdo = $stats['parts'] * ($currentVl - $prevVl);
            $pru = $stats['parts'] > 0 ? $invested / $stats['parts'] : 0;

            $portfolio[] = [
                'product_id' => $product->id,
                'name' => $product->title,
                'slug' => $product->slug,
                'total_parts' => $stats['parts'],
                'total_invested' => $invested,
                'current_vl' => $currentVl,
                'previous_vl' => $prevVl,
                'pru' => $pru, // Prix de Revient Unitaire
                'valuation' => $valuation,
                'total_gain' => $gainTotal,
                'weekly_gain' => $gainHebdo,
                'performance' => $invested > 0 ? ($gainTotal / $invested) * 100 : 0,
            ];
        }

        return $portfolio;
    }

    /**
     * Calcule le statut actuel (parts et investissement net) d'un produit pour un utilisateur.
     */
    public function getCurrentStatus($userId, $productId)
    {
        $movements = DB::table('fcp_movements')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->get();

        $parts = 0;
        $invested = 0;

        foreach ($movements as $m) {
            $parts += (float)$m->nb_parts_change;
            if (in_array($m->type, ['souscription', 'versement_libre'])) {
                $invested += (float)$m->amount_xaf;
            } elseif (in_array($m->type, ['rachat_partiel', 'rachat_total'])) {
                $invested -= abs((float)$m->amount_xaf);
            }
        }

        return [
            'parts' => max(0, $parts),
            'invested' => max(0, $invested)
        ];
    }

    /**
     * Synchronise les transactions anciennes vers la table fcp_movements.
     */
    public function syncFcpMovements()
    {
        // On récupère les transactions de type FCP validées
        $transactions = \App\Models\Transaction::where('status', 'Succès')
            ->whereHas('product', function($query) {
                $query->where('products_category_id', 1);
            })->get();

        $count = 0;
        foreach ($transactions as $trans) {
            $exists = DB::table('fcp_movements')
                ->where('transaction_id', $trans->id)
                ->exists();

            if (!$exists) {
                $this->recordFcpMovement($trans, 'souscription');
                $count++;
            }
        }

        // Transactions supplémentaires (versements libres)
        $supps = \App\Models\TransactionSupplementaire::where('status', 'Succès')->get();
        foreach ($supps as $trans) {
             $exists = DB::table('fcp_movements')
                ->where('transaction_id', $trans->id)
                ->exists();

            if (!$exists) {
                $this->recordFcpMovement($trans, 'versement_libre');
                $count++;
            }
        }

        return $count;
    }
}
