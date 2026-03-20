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
    $this->info("Début du test : Synchronisation des anniversaires (Filtre : Précomptés uniquement)...");
    
    $today = Carbon::now();

    // On récupère les transactions actives
    $transactions = Transaction::where('status', 'Succès')
        ->where('date_echeance', '>', $today) 
        ->whereHas('product', function ($q) {
            $q->where('products_category_id', 2);
        })->get();

    foreach ($transactions as $trans) {
        // --- FILTRE DE TEST : Uniquement les précomptés ---
        // On vérifie si le produit lié est de type 'precompte_interets'
        if ($trans->product->type_interet !== 'precompte_interets') {
            continue; // Ignore les autres produits pour ce test
        }

        $this->info("Analyse du compte précompté : Trans ID {$trans->id} - Client: {$trans->user_id}");
        Log::info("--- TEST PRÉCOMPTÉ DÉBUT : Trans ID {$trans->id} ---");

        // Point de départ : 1 an après la validation
        $anniversary = Carbon::parse($trans->date_validation)->addYear();

        while ($anniversary->lte($today)) {
            $formattedDate = $anniversary->toDateString();
            
            // On vérifie l'existence d'une capitalisation
            $exists = DB::table('financial_movements')
                ->where('transaction_id', $trans->id)
                ->where('type', 'capitalisation_interets')
                ->whereDate('date_operation', $formattedDate)
                ->exists();

            if (!$exists) {
                try {
                    $anniversaryMidnight = $anniversary->copy()->startOfDay();
                    
                    // Calcul via votre fonction calculatePMGValorization (qui contient la logique précomptée)
                    $valeurPortefeuille = $this->calculatePMGValorization($trans, $anniversaryMidnight);

                    // Récupération du capital précédent
                    $lastMove = DB::table('financial_movements')
                        ->where('transaction_id', $trans->id)
                        ->where('date_operation', '<', $anniversaryMidnight->toDateTimeString())
                        ->orderBy('date_operation', 'desc')
                        ->first();

                    $capitalAvant = $lastMove ? (float)$lastMove->capital_after : (float)$trans->amount;
                    $interetAdd = $valeurPortefeuille - $capitalAvant;

                    // Log spécifique pour voir le comportement du précompté
                    Log::info("  > Anniversaire : {$formattedDate}");
                    Log::info("  > Cap. Avant : " . number_format($capitalAvant, 0) . " | Val. Portefeuille : " . number_format($valeurPortefeuille, 0));
                    Log::info("  > Différence (intérêt à capitaliser) : " . $interetAdd);

                    // NOTE : Pour un produit précompté, interetAdd devrait théoriquement être proche de 0 ou 
                    // correspondre uniquement à la remontée vers la valeur nominale.
                    
                    if ($interetAdd > 0) {
                        // On insère l'ajustement si nécessaire
                        DB::table('financial_movements')->insert([
                            'transaction_id' => $trans->id,
                            'user_id'        => $trans->user_id,
                            'date_operation' => $anniversaryMidnight->toDateTimeString(),
                            'type'           => 'capitalisation_interets',
                            'amount'         => round($interetAdd, 0),
                            'capital_before' => round($capitalAvant, 0),
                            'capital_after'  => round($valeurPortefeuille, 0),
                            'comments'       => 'Ajustement valeur précomptée anniversaire ' . $anniversary->diffInYears(Carbon::parse($trans->date_validation)) . ' an(s)',
                            'created_at'     => now(),
                            'updated_at'     => now()
                        ]);

                        $trans->update(['amount' => round($valeurPortefeuille, 0)]);
                        $this->info("✔ Ajustement précompté inséré : Trans {$trans->id} au {$formattedDate}");
                    }
                } catch (\Exception $e) {
                    $this->error("Erreur Trans {$trans->id} : " . $e->getMessage());
                    Log::error("Erreur Sync Précompté Trans {$trans->id} : " . $e->getMessage());
                }
            }
            $anniversary->addYear();
        }
        Log::info("--- TEST PRÉCOMPTÉ FIN : Trans ID {$trans->id} ---");
    }
    $this->info("Test terminé. Vérifiez les logs (storage/logs/laravel.log) pour les détails.");
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