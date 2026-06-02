<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncAnniversaryMovements extends Command
{


    protected $signature = 'pmg:sync-anniversary';
    protected $description = 'Capitalisation automatique des intérêts aux dates anniversaires';
    public function handle()
    {
        $this->info("Début de la synchronisation des capitalisations PMG...");

        try {
            $controller = app(\App\Http\Controllers\ProductController::class);
            $controller->syncAnniversaryMovements();
            $this->info("✔ Synchronisation terminée avec succès !");
        } catch (\Exception $e) {
            $this->error("Erreur générale : " . $e->getMessage());
        }
    }

    // Copiez ici votre fonction calculatePMGValorization corrigée
    public function calculatePMGValorization($trans, $refDate)
    {
        $dateEcheance = Carbon::parse($trans->date_echeance);
        $targetDate = Carbon::parse($refDate)->min($dateEcheance);
        $rate = (float)$trans->vl_buy / 100;
        $dateValidation = Carbon::parse($trans->date_validation);

        // --- AJOUT : DÉTECTION DU TYPE PRÉCOMPTÉ ---
        // On vérifie si la transaction ou le produit est marqué "precompte_interets"
        if ($trans->product->type_interet === 'precompte_interets') {
            // Si on est à l'échéance, la valeur est le Nominal total
            if ($targetDate->gte($dateEcheance)) {
                return round($trans->amount, 0);
            }

            // Calcul du prorata non acquis (ce que le client devrait "rendre" s'il part maintenant)
            $joursRestants = $targetDate->diffInDays($dateEcheance);
            $interetNonAcquis = ($trans->amount * $rate * $joursRestants) / 360;

            // La valeur actuelle est le Nominal moins les intérêts non encore "gagnés" par le temps
            return round($trans->amount - $interetNonAcquis, 0);
        }
        // --- FIN AJOUT ---

        // 1. RECHERCHE DU DERNIER MOUVEMENT (Pivot de Capitalisation)
        $lastMovement = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->where('date_operation', '<=', $targetDate->toDateString())
            ->orderBy('date_operation', 'desc')
            ->first();

        if ($lastMovement) {
            $baseCapital = (float)$lastMovement->capital_after;
            $startDate = Carbon::parse($lastMovement->date_operation);
        } else {
            $baseCapital = (float)$trans->amount;
            $startDate = $dateValidation;
        }

        if ($startDate->gt($targetDate)) return round($baseCapital, 0);

        $totalInterest = 0;
        $nextMonth = $startDate->copy()->addMonthNoOverflow()->startOfMonth();

        // 2. LOGIQUE DE CALCUL HYBRIDE (Base 360) - PRODUITS CLASSIQUES
        if ($targetDate->lt($nextMonth)) {
            $days = $startDate->diffInDays($targetDate);
            $totalInterest = ($baseCapital * $rate * $days) / 360;
        } else {
            $daysInFirstMonth = $startDate->diffInDays($startDate->copy()->endOfMonth());
            $totalInterest = ($baseCapital * $rate * $daysInFirstMonth) / 360;

            $fullMonths = $nextMonth->diffInMonths($targetDate->copy()->addDay());
            $totalInterest += ($baseCapital * ($rate / 12)) * $fullMonths;

            $lastMonthStart = $nextMonth->copy()->addMonths($fullMonths);
            if ($lastMonthStart->lt($targetDate)) {
                $days = $lastMonthStart->diffInDays($targetDate);
                $totalInterest += ($baseCapital * $rate * $days) / 360;
            }
        }

        return round($baseCapital + $totalInterest, 0);
    }
}