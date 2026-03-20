<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TestPrecompteLogic extends Command
{
    protected $signature = 'test:precompte';
    protected $description = 'Simulateur de calcul pour intérêts précomptés';

    public function handle()
    {
        $this->info("=== DÉBUT DU TEST LOGIQUE PRÉCOMPTÉE ===");

        // 1. Définition des paramètres du test
        $data = (object)[
            'amount' => 100000000,
            'vl_buy' => 9, // 9%
            'date_validation' => '2024-10-05 00:00:00',
            'date_echeance' => '2026-08-05 00:00:00',
            'type_interet' => 'precompte_interets'
        ];

        $startDate = Carbon::parse($data->date_validation);
        $endDate = Carbon::parse($data->date_echeance);
        $limitDate = Carbon::parse('2026-01-31');

        $this->table(
            ['Date d\'Arrêté', 'Type', 'Valeur de Rachat (XAF)', 'Gain théorique acquis'],
            []
        );

        $current = $startDate->copy()->endOfMonth();

        // 2. Boucle de simulation mensuelle
        while ($current->lte($limitDate)) {
            $valorization = $this->simulateValue($data, $current);
            
            // Calcul du gain acquis par le temps (Valeur actuelle - Net décaissé au début)
            // Le net décaissé au début = Nominal - (Nominal * Taux * 2ans / 360)
            $totalInterests = ($data->amount * ($data->vl_buy / 100) * 720) / 360;
            $netInitial = $data->amount - $totalInterests;
            $gainAcquis = $valorization - $netInitial;

            $this->line("<info>{$current->toDateString()}</info> | Precompte | <comment>" . number_format($valorization, 0, ',', ' ') . "</comment> | +" . number_format($gainAcquis, 0, ',', ' '));
            
            $current->addMonth()->endOfMonth();
        }

        $this->info("=== FIN DU TEST ===");
    }

    /**
     * Logique de calcul isolée pour le test
     */
    private function simulateValue($trans, $refDate)
    {
        $dateEcheance = Carbon::parse($trans->date_echeance);
        $targetDate = Carbon::parse($refDate)->min($dateEcheance);
        $rate = (float)$trans->vl_buy / 100;

        // Si on est à l'échéance, on touche le Nominal (150M)
        if ($targetDate->gte($dateEcheance)) {
            return $trans->amount;
        }

        // Calcul du prorata non acquis (temps restant jusqu'à l'échéance)
        // On utilise la base 360 comme convenu
        $joursRestants = $targetDate->diffInDays($dateEcheance);
        $interetNonAcquis = ($trans->amount * $rate * $joursRestants) / 360;

        // La valeur de rachat est le Nominal moins les intérêts restants à courir
        return round($trans->amount - $interetNonAcquis, 0);
    }
}