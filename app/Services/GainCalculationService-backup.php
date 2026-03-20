<?php

namespace App\Services;

use App\Transaction;
use App\TransactionSupplementaire;
use App\AssetValue;
use App\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use DateTime;

class GainCalculationService
{



    public function getProductsWithGains()
    {
        $products = Product::all();
        $result = [];
        $user = Auth::user();

        // Initialiser des tableaux pour agréger les gains par type de produit
        $gainsParType = [
            1 => 0, // Type FCP
            2 => 0  // Type PMG
        ];

        foreach ($products as $product) {
            // Récupérer toutes les transactions principales et supplémentaires pour le produit
            $transactions = Transaction::where('user_id', $user->id)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            $additionalTransactions = TransactionSupplementaire::where('user_id', $user->id)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            $allTransactions = $transactions->merge($additionalTransactions);

            if ($allTransactions->isEmpty()) {
                continue;
            }

            $totalGain = 0;
            $totalGainFcp = 0;
            $cumulGains = 0;
            $gainWeekFcp = 0;
            $vl_actuel = null;
            $gainWeekTab = [];
            $gainMonth = 0;

            foreach ($allTransactions as $transaction) {
                if ($product->products_category_id == 1) {
                    // Récupérer les deux dernières valeurs liquidatives (VL) pour calculer le gain hebdomadaire
                    $assetValues = AssetValue::where('product_id', $transaction->product_id)
                        ->where('created_at', '>=', $transaction->date_validation)
                        ->orderBy('created_at', 'desc')
                        ->take(2)
                        ->get();

                    if ($assetValues->count() >= 1) {
                        $vl_actuel = $assetValues->first()->vl;

                        $vl_antepenultimate = $assetValues->count() == 2 ? $assetValues->last()->vl : $transaction->vl_buy;

                        //dd("Valeur liquidative actuelle = ".$vl_actuel,"Valeur liquidative précédente = ".$vl_antepenultimate, "Nombre de parts = ".$transaction->nb_part,"Différence Vl précédente Vl actuelle = ".$vl_actuel - $vl_antepenultimate, "Gain de la semaine (nb part * (vl_prec - vl_actuelle)) = ".$transaction->nb_part * ($vl_actuel - $vl_antepenultimate));
                        // Calcul de la valorisation du portefeuille
                        $valorisationPortefeuille = $this->calculateFCPGain($vl_actuel, $transaction);

                        //dd($transaction->nb_part * $vl_actuel);
                        // Calcul du gain entre l'avant-dernière et la dernière VL

                        $gain = $transaction->nb_part * ($vl_actuel - $vl_antepenultimate);
                        $gainWeekFcp += $gain;
                        $gainWeekTab[] = $gainWeekFcp;

                        // Calcul du cumul des gains/pertes depuis la souscription
                        $cumulGains += $valorisationPortefeuille - ($transaction->nb_part * $transaction->vl_buy);

                        // Ajouter le gain cumulé au total
                        $totalGainFcp += $this->calculateFCPGain($vl_actuel, $transaction);
                        //$totalGainFcp +=  (($transaction->nb_part * $vl_actuel)-$transaction -> amount);
                    }
                } else {
                    $date_echeance_exploded = explode(" ", $transaction->date_echeance)[0];
                    $date_validation_exploded = explode(" ", $transaction->date_validation)[0];
                    $vl_actuel = $transaction->vl_buy;

                    // Calculer le gain pour les produits PMG
                    $gainMonth = $this->calculatePMGMonthlyGain(
                        $transaction->amount,
                        $transaction->vl_buy,
                        $date_validation_exploded,
                        $date_echeance_exploded,
                        $this->getTodaysDate()
                    );

                    // Ajouter le gain total au total général
                    $totalGain = $gainMonth;
                }
            }

            // Agréger le gain total par type de produit
            $gainsParType[$product->products_category_id] += $totalGain;

            // Ajouter les résultats agrégés au tableau $result
            $result[] = [
                'product_name' => $product->title,
                'gain' => $totalGain,
                'vl_actuel' => $vl_actuel,
                'duree' => $product->duree,
                'nb_part' => $allTransactions->isNotEmpty() ? $transaction->nb_part : null,
                'montant_transaction' => $allTransactions->isNotEmpty() ? $transaction->amount : null,
                'type_product' => $product->products_category_id,
                'vl_achat' => $allTransactions->isNotEmpty() ? $transaction->vl_buy : null,
                'gain_semaine' => $gainWeekFcp, // Correction ici
                'gain_month' => $gainMonth,
                'slug' => $product->slug,
                'souscription' => $allTransactions->isNotEmpty() ? $transaction->date_validation : null,
                'gain_par_type' => $gainsParType,
                'cumul_gain_fcp' => $cumulGains,
                'valorisation_portefeuille_fcp' => isset($valorisationPortefeuille) ? $valorisationPortefeuille : null,
                'gain_semaine_fcp' => $gainWeekFcp,
                'total_gains_fcp' => $totalGainFcp
            ];

            $user->gain_pmg = $gainsParType[2] + ($allTransactions->isNotEmpty() ? $transaction->montant_initiale : 0);
            $user->gain = isset($valorisationPortefeuille) ? $valorisationPortefeuille : 0;
            $user->save();
        }

        return $result;
    }



    function ValorisationPortefeuilleFCP($vlSouscription, $nbParts, $vlDerniere, $vlAvantDerniere)
    {
        // Calcul de la valorisation du portefeuille à la date de la dernière VL
        $valorisationPortefeuille = $nbParts * $vlDerniere;

        // Calcul du gain entre l'avant-dernière et la dernière valeur liquidative
        $gain = $nbParts * ($vlDerniere - $vlAvantDerniere);

        // Calcul du cumul des gains/pertes depuis la souscription
        $cumulGains = $valorisationPortefeuille - ($nbParts * $vlSouscription);

        // Retourner les résultats sous forme de tableau associatif
        return [
            'valorisationPortefeuille' => $valorisationPortefeuille,
            'gain' => $gain,
            'cumulGains' => $cumulGains
        ];
    }

    private function calculatePMGMonthlyGainPerDay($initialAmount, $interestRate, $specificDate, $transactionDate)
    {
        // Conversion du taux d'intérêt annuel en taux journalier
        $dailyRate_ = (float) $interestRate / 100;

        $dailyRate = $dailyRate_ / 360;
        $portfolio = $initialAmount; // Portefeuille initial
        $cumulative = 0; // Cumul des intérêts pour le mois en cours

        // Calcul des timestamps pour les dates fournies
        $transactionTimestamp = strtotime($transactionDate);
        $specificTimestamp = strtotime($specificDate);
        $currentTimestamp = strtotime(date('Y-m-d')); // Date actuelle

        $interet_ = 0;


        // Vérification si la date actuelle est avant ou à la date spécifique
        if ($currentTimestamp <= $specificTimestamp) {
            // Calcul du nombre de jours écoulés depuis la date de transaction jusqu'à la date actuelle
            $elapsedDays = max(($currentTimestamp - $transactionTimestamp) / 86400, 0);

            // Calcul du début du mois en cours
            $startOfMonthTimestamp = strtotime(date('Y-m-01', $currentTimestamp));

            // Mise à jour du portefeuille pour chaque jour écoulé
            for ($i = 0; $i < $elapsedDays; $i++) {
                $interest = $portfolio * $dailyRate; // Intérêt pour le jour en cours
                $interet_ += $interest; // Mise à jour du portefeuille

                // Si le jour est dans le mois en cours, ajouter l'intérêt au cumul
                if ($transactionTimestamp + ($i * 86400) >= $startOfMonthTimestamp) {
                    $cumulative += $interest;
                }
            }


            // Préparation du résultat
            $result = [
                'valo_pf' => round($interet_, 2),
                'cummul_interet' => round($cumulative, 2),
            ];
        } else {
            // Si la date actuelle dépasse la date spécifique, retourner 0 pour le portefeuille
            $result = [
                'valo_pf' => 0,
                'cummul_interet' => 0,
            ];
        }

        return $result;
    }

    private function getTodaysDate()
    {
        return date('Y-m-d');
    }

    private function calculatePMGMonthlyGain($initialAmount, $annualInterestRate, $startDate, $endDate, $currentDate)
    {
        // Conversion des dates en objets DateTime
        $startDate = new DateTime($startDate);
        $endDate = new DateTime($endDate);
        $currentDate = new DateTime($currentDate);
        $portofolio_ = 0;

        // Si startDate ne correspond pas au début du mois, déplacer au premier jour du mois suivant
        if ($startDate->format('d') !== '01') {
            $startDate->modify('first day of next month');
        }

        // Vérifie si la date actuelle est après la date d'échéance
        if ($currentDate > $endDate) {
            $effectiveEndDate = $endDate;
        } else {
            $effectiveEndDate = $currentDate;
        }

        // Calcul du taux d'intérêt mensuel
        $monthlyRate = ($annualInterestRate / 100) / 12;

        // Calcul du nombre de mois écoulés
        $interval = $startDate->diff($effectiveEndDate);
        $monthsElapsed = $interval->y * 12 + $interval->m;

        // Calcul des intérêts cumulés
        $totalCumulativeInterest = 0;
        for ($i = 0; $i < $monthsElapsed; $i++) {
            $interestForCurrentMonth = $initialAmount * $monthlyRate;
            $totalCumulativeInterest += $interestForCurrentMonth;

            // Ajouter l'intérêt seulement à la fin du mois
            $portofolio_ += $interestForCurrentMonth;
        }

        // Valorisation du portefeuille à la date actuelle
        $portfolioValueAtCurrentDate = $initialAmount + $portofolio_;

        return $portfolioValueAtCurrentDate;
    }

    public function calculateFCPGain($vl_actuel, $transaction)
    {
        $totalInvested = $transaction->amount;
        $montantTotal = floor($vl_actuel * $transaction->nb_part);
        //dd(floor($montantTotal));

        $gain = max(0, round($montantTotal - $totalInvested));

        //dd(floor($vl_actuel * $transaction->nb_part));
        return $gain;
    }

    /*
    private function calculateFCPGain($vl_actuel, $transaction)
    {

        // Calculer le montant total investi dans le produit
        $totalInvested = $transaction->amount;
        $montantTotal = round($transaction->nb_part, 2) * $vl_actuel;

        //Calcule du gain ou de la perte en fonction de la valeur liquidative du produit
        $gain = max(0, round($montantTotal - $totalInvested));
        // dd('la vl est : ' . $vl_actuel . ', le montant total arrondi est :' . round($montantTotal) . ' et le gain est : ' . round($gain));
        //dd($gain);

        return $gain;

    }*/

    private function calculatePMGGain($vl_actuel, $transaction)
    {
        $currentDate = Carbon::now();

        // Calculer la différence en jours entre la date de validation et la date actuelle
        $daysDifference = Carbon::parse($transaction->date_validation)->diffInDays($currentDate);

        $totalInvested = $transaction->amount;
        $dailyRate = 1 + ($vl_actuel / 100) / 52;
        $totalGain = $totalInvested * pow(1 + $dailyRate, $daysDifference);
        $gain = round(max(0, $totalGain - $totalInvested));
        return $gain;
    }

    public function getChartData()
    {
        $products = Product::all();
        $fcpChartData = [];
        $pmgChartData = [];
        $weekLabels = [];

        foreach ($products as $product) {
            $transactions = Transaction::where('user_id', Auth::user()->id)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            $additionalTransactions = TransactionSupplementaire::where('user_id', Auth::user()->id)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            $allTransactions = $transactions->merge($additionalTransactions);

            if ($allTransactions->isEmpty()) {
                continue;
            }

            $weeklyGains = [];

            foreach ($allTransactions as $transaction) {
                $validationDate = Carbon::parse($transaction->date_validation);
                $currentDate = Carbon::now();
                $weeks = $validationDate->diffInWeeks($currentDate);

                for ($i = 0; $i <= $weeks; $i++) {
                    $startOfWeek = $validationDate->copy()->addWeeks($i)->startOfWeek();
                    $endOfWeek = $startOfWeek->copy()->endOfWeek();

                    $assetValue = AssetValue::whereBetween('created_at', [$startOfWeek, $endOfWeek])
                        ->where('product_id', $transaction->product_id)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($assetValue) {
                        $gain = ($product->products_category_id == 1)
                            ? $this->calculateFCPGain($assetValue->vl, $transaction)
                            : $this->calculatePMGGain($assetValue->vl, $transaction);
                    } else {
                        $gain = 0;
                    }

                    $weeklyGains[] = $gain;

                    if (!in_array('Semaine ' . ($i + 1), $weekLabels)) {
                        $weekLabels[] = 'Semaine ' . ($i + 1);
                    }
                }
            }

            $chartData = [
                'name' => $product->title,
                'data' => array_pad($weeklyGains, count($weekLabels), 0)
            ];

            if ($product->products_category_id == 1) {
                $fcpChartData[] = $chartData;
            } else {
                $pmgChartData[] = $chartData;
            }
        }

        return [
            'weekLabels' => $weekLabels,
            'fcpChartData' => $fcpChartData,
            'pmgChartData' => $pmgChartData
        ];
    }
}
