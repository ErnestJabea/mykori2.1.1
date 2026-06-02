<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditPortefeuilleComplet extends Command
{
    /**
     * La signature de la commande avec la date limite par défaut.
     */
    protected $signature = 'audit:portefeuille {date_limit=2026-01-31}';
    protected $description = 'Audit détaillé des valorisations (Détection dynamique Précompté vs Postcompté)';

    public function handle()
    {
        $limitDate = Carbon::parse($this->argument('date_limit'));
        $this->info("=== DÉBUT DE L'AUDIT DÉTAILLÉ AU {$limitDate->toDateString()} ===");

        // Récupération des transactions PMG (Catégorie 2) avec relations
        $transactions = Transaction::with(['user', 'product'])
            ->where('status', 'Succès')
            ->whereHas('product', function ($q) {
                $q->where('products_category_id', 2);
            })->get();

        $this->info(count($transactions) . " dossiers à analyser.");

        foreach ($transactions as $trans) {
            $clientName = $trans->user ? $trans->user->name : "Client Inconnu";
            
            // 1. DÉTECTION DU TYPE (Vérification si un mouvement de précompte existe pour cette transaction)
            $precompteMouvement = DB::table('financial_movements')
                ->where('transaction_id', $trans->id)
                ->where('type', 'precompte_interets')
                ->first();

            $isPrecompted = !is_null($precompteMouvement);
            $typeLabel = $isPrecompted ? 'PRÉCOMPTÉ' : 'POSTCOMPTÉ';
            $color = $isPrecompted ? 'cyan' : 'yellow';

            $this->line("\n" . str_repeat("=", 90));
            $this->line(" CLIENT : <fg=white;bg=black;options=bold> {$clientName} </> | ID : {$trans->id}");
            $this->line(" TYPE   : <fg={$color};options=bold>{$typeLabel}</> | TAUX : {$trans->vl_buy}% | NOMINAL : " . number_format($trans->amount, 0, ',', ' ') . " XAF");
            
            if ($isPrecompted) {
                $this->line(" INFO   : Mouvement de précompte de " . number_format($precompteMouvement->amount, 0, ',', ' ') . " XAF détecté en base.");
            }
            $this->line(str_repeat("-", 90));

            $currentDate = Carbon::parse($trans->date_validation)->endOfMonth();

            // Boucle mensuelle de simulation
            while ($currentDate->lte($limitDate)) {
                $details = "";
                $valeur = $this->calculateWithAuditDetails($trans, $currentDate, $isPrecompted, $details);

                $output = sprintf(
                    " [%s] | Val: <fg=green>%14s</> XAF | %s",
                    $currentDate->format('m/Y'),
                    number_format($valeur, 0, ',', ' '),
                    $details
                );

                $this->line($output);
                $currentDate->addMonth()->endOfMonth();
            }
        }

        $this->info("\n=== FIN DE L'AUDIT GÉNÉRAL ===");
    }

    /**
     * Calcule la valorisation et génère les détails techniques pour l'affichage.
     */
    private function calculateWithAuditDetails($trans, $refDate, $isPrecompted, &$details)
    {
        $dateEcheance = Carbon::parse($trans->date_echeance);
        $targetDate = Carbon::parse($refDate)->min($dateEcheance);
        $rate = (float)$trans->vl_buy / 100;
        $dateValidation = Carbon::parse($trans->date_validation);

        // --- CAS PRÉCOMPTÉ ---
        if ($isPrecompted) {
            if ($targetDate->gte($dateEcheance)) {
                $details = "<fg=magenta>ÉCHÉANCE ATTEINTE</>";
                return round($trans->amount, 0);
            }

            $joursRestants = $targetDate->diffInDays($dateEcheance);
            $interetNonAcquis = ($trans->amount * $rate * $joursRestants) / 360;
            
            $details = sprintf("<fg=magenta>Rachat</> (Jours restants: %3dj | Prorata non acquis: -%s)", 
                $joursRestants, 
                number_format($interetNonAcquis, 0, ',', ' ')
            );
            
            return round($trans->amount - $interetNonAcquis, 0);
        }

        // --- CAS POSTCOMPTÉ ---
        $lastMovement = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->where('type', 'capitalisation_interets')
            ->where('date_operation', '<=', $targetDate->toDateString())
            ->orderBy('date_operation', 'desc')
            ->first();

        $baseCapital = $lastMovement ? (float)$lastMovement->capital_after : (float)$trans->amount;
        $baseLabel = $lastMovement ? "Cap. Replacé" : "Cap. Initial";

        // Appel de la fonction de calcul hybride standard
        $valeur = $this->calculatePMGValorization($trans, $targetDate);

        $details = sprintf("<fg=blue>Classique</> (Base: %s | %s)", 
            number_format($baseCapital, 0, ',', ' '), 
            $baseLabel
        );

        return $valeur;
    }

    /**
     * Logique de calcul standard (à copier également dans votre ProductController)
     */
public function calculatePMGValorization($trans, $refDate)
{
    $targetDate = Carbon::parse($refDate)->min(Carbon::parse($trans->date_echeance));
    $rate = (float)$trans->vl_buy / 100;

    // --- 1. ÉTAT DU CAPITAL (Prise en compte Capitalisation et Rachats) ---
    $lastMovement = DB::table('financial_movements')
        ->where('transaction_id', $trans->id)
        ->whereIn('type', ['capitalisation_interets', 'rachat_partiel'])
        ->where('date_operation', '<=', $targetDate->toDateString())
        ->orderBy('date_operation', 'desc')
        ->first();

    $baseCapital = $lastMovement ? (float)$lastMovement->capital_after : (float)$trans->amount;
    $startDate = $lastMovement ? Carbon::parse($lastMovement->date_operation) : Carbon::parse($trans->date_validation);

    // --- 2. CALCUL DES INTÉRÊTS COURUS (Méthode Hybride 360) ---
    $totalInterest = 0;
    if ($targetDate->gt($startDate)) {
        $nextMonth = $startDate->copy()->addMonthNoOverflow()->startOfMonth();

        if ($targetDate->lt($nextMonth)) {
            $totalInterest = ($baseCapital * $rate * $startDate->diffInDays($targetDate)) / 360;
        } else {
            // Prorata mois de départ
            $totalInterest = ($baseCapital * $rate * $startDate->diffInDays($startDate->copy()->endOfMonth())) / 360;
            
            // Mois pleins (Forfait 1/12)
            $fullMonths = $nextMonth->diffInMonths($targetDate->copy()->addDay());
            $totalInterest += ($baseCapital * ($rate / 12)) * $fullMonths;

            // Prorata mois final
            $lastMonthStart = $nextMonth->copy()->addMonths($fullMonths);
            if ($lastMonthStart->lt($targetDate)) {
                $totalInterest += ($baseCapital * $rate * $lastMonthStart->diffInDays($targetDate)) / 360;
            }
        }
    }

    // --- 3. DÉDUCTION DU PRÉCOMPTE ---
    $precompte = DB::table('financial_movements')
        ->where('transaction_id', $trans->id)
        ->where('type', 'precompte_interets')
        ->value('amount') ?? 0;

    // Valorisation = (Capital Actuel - Précompte) + Intérêts du cycle
    return round(($baseCapital - $precompte) + $totalInterest, 0);
}
}