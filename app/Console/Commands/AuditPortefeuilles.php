<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuditPortefeuilles extends Command
{
    protected $signature = 'pmg:audit-logs {limit_date?}';
    protected $description = 'Calcule et enregistre la valorisation de tous les portefeuilles dans les logs';

    public function handle()
    {

        $limitDateInput = $this->argument('limit_date') ?? '2026-02-28';
        $limitDate = Carbon::parse($limitDateInput);
        $this->info("Génération de l'audit mensuel dans les logs...");

        $clients = User::whereHas('transactions', function ($q) {
            $q->where('status', 'Succès');
        })->with(['transactions.product'])->get();

        foreach ($clients as $client) {
            Log::info("=== HISTORIQUE CLIENT : {$client->name} (ID: {$client->id}) ===");

            foreach ($client->transactions as $trans) {
                $startDate = Carbon::parse($trans->date_validation);
                $currentMonth = $startDate->copy()->endOfMonth();

                Log::info("  PRODUIT : " . ($trans->product->name ?? 'PMG') . " | Capital: " . number_format($trans->amount, 0, ',', ' ') . " XAF");

                // Boucle mois par mois jusqu'à la date limite
                while ($currentMonth->lte($limitDate)) {

                    // On utilise votre fonction calculatePMGValorization (Base 360 / Hybride)
                    $val = $this->calculatePMGValorization($trans, $currentMonth);
                    $gainTotal = $val - $trans->amount;

                    Log::info("    > [Mois: {$currentMonth->format('M Y')}] Valeur: " . number_format($val, 0, ',', ' ') . " | Gain cumulé: " . number_format($gainTotal, 0, ',', ' '));

                    // Passage au mois suivant (dernier jour du mois)
                    $currentMonth->addMonthNoOverflow()->endOfMonth();
                }
                Log::info("  --------------------------------------------------");
            }
        }

        $this->info("Audit mensuel terminé. Consultez storage/logs/laravel.log");
    }

    // Insérez ici votre fonction calculatePMGValorization commentée précédemment

public function calculatePMGValorization($trans, $refDate)
{
    $dateEcheance = Carbon::parse($trans->date_echeance);
    $targetDate = Carbon::parse($refDate)->min($dateEcheance);
    $rate = (float)$trans->vl_buy / 100;

    // 1. RECHERCHE DU DERNIER MOUVEMENT (Pivot de Capitalisation)
    $lastMovement = DB::table('financial_movements')
        ->where('transaction_id', $trans->id)
        ->where('date_operation', '<=', $targetDate->toDateString())
        ->orderBy('date_operation', 'desc')
        ->first();

    if ($lastMovement) {
        $baseCapital = (float)$lastMovement->capital_after;
        $startDate = Carbon::parse($lastMovement->date_operation);

        // LOG SPÉCIFIQUE : Détection du replacement dans le mois en cours d'audit
        if ($lastMovement->type === 'capitalisation' && Carbon::parse($lastMovement->date_operation)->isSameMonth($targetDate)) {
            $beforeCap = (float)$lastMovement->capital_before + (float)$lastMovement->amount;
            
            Log::info("   [★ REPLACEMENT ANNUEL ★] Date: " . $lastMovement->date_operation);
            Log::info("     > Cumul Intérêts + Capital Précédent : " . number_format($beforeCap, 0, ',', ' ') . " XAF");
            Log::info("     > Nouveau Capital de base (Replacé)  : " . number_format($baseCapital, 0, ',', ' ') . " XAF");
        }
    } else {
        $baseCapital = (float)$trans->amount;
        $startDate = Carbon::parse($trans->date_validation);
    }

    if ($startDate->gt($targetDate)) return round($baseCapital, 0);

    $totalInterest = 0;
    $nextMonth = $startDate->copy()->addMonthNoOverflow()->startOfMonth();

    // 2. LOGIQUE DE CALCUL HYBRIDE (Base 360)
    if ($targetDate->lt($nextMonth)) {
        $days = $startDate->diffInDays($targetDate); 
        $totalInterest = ($baseCapital * $rate * $days) / 360;
    } 
    else {
        // A. Prorata du mois de départ (ex: 11 jours pour le 20/12)
        $daysInFirstMonth = $startDate->diffInDays($startDate->copy()->endOfMonth());
        $totalInterest = ($baseCapital * $rate * $daysInFirstMonth) / 360;

        // B. Mois pleins (Forfait 1/12)
        $fullMonths = $nextMonth->diffInMonths($targetDate->copy()->addDay());
        $totalInterest += ($baseCapital * ($rate / 12)) * $fullMonths;

        // C. Prorata du mois final (si la targetDate est en cours de mois, ex: 31/01)
        $lastMonthStart = $nextMonth->copy()->addMonths($fullMonths);
        if ($lastMonthStart->lt($targetDate)) {
            $days = $lastMonthStart->diffInDays($targetDate);
            $totalInterest += ($baseCapital * $rate * $days) / 360;
        }
    }

    return round($baseCapital + $totalInterest, 0);
}
}
