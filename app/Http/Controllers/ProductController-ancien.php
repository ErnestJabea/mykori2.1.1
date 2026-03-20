<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\Transaction;
use App\TransactionSupplementaire;
use App\AssetValue;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use DateTime;


class ProductController extends Controller
{
    public function calculateFCPGain($vl_actuel, $transaction)
    {
        $totalInvested = $transaction->amount;
        $montantTotal = $transaction->nb_part * $vl_actuel;
        $gain = max(0, round($montantTotal - $totalInvested));
        return $gain;
    }

    public function calculateFCPGainWeek($vl_actuel, $transaction)
    {
        $totalInvested = $transaction->amount;
        $montantTotal = $transaction->nb_part * $vl_actuel;
        $gain = max(0, round($montantTotal - $totalInvested));
        return $gain / 7;
    }





    public function calculatePMGGain($vl_buy, $transaction)
    {
        $totalInvested = $transaction->amount;
        $currentDate = Carbon::now();
        $daysDifference = Carbon::parse($transaction->date_validation)->diffInDays($currentDate) - 1;
        $rate = ($vl_buy / 100) / 360; // Supposons que vl_buy est le taux d'intérêt annuel
        $rate_invested = $totalInvested * $rate;
        //dd($rate_invested_without_days = $totalInvested + $rate_invested);
        return $totalInvested + $rate_invested;

    }

    public function calculatePMGGainWeek($vl_buy, $transaction)
    {
        $totalInvested = $transaction->amount;
        $currentDate = Carbon::now();
        $daysDifference = Carbon::parse($transaction->date_validation)->diffInDays($currentDate);
        $rate = $vl_buy / 100 / 52; // Supposons que vl_buy est le taux d'intérêt annuel
        $gain = ($totalInvested + $totalInvested * $rate) * $daysDifference;
        return $gain / 7;
    }

    public function getProductsWithGains()
    {
        $products = Product::all();
        $result = [];
        $user = Auth::user();
        foreach ($products as $product) {
            // Récupérer toutes les transactions principales pour le produit
            $transactions = Transaction::where('user_id', Auth::user()->id)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            // Récupérer toutes les transactions supplémentaires pour le produit
            $additionalTransactions = TransactionSupplementaire::where('user_id', Auth::user()->id)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            // Fusionner les transactions principales et supplémentaires
            $allTransactions = $transactions->merge($additionalTransactions);

            if ($allTransactions->isEmpty()) {
                continue;
            }

            $totalGain = 0;
            $totalGainFcp = 0;
            $cumulGains =0;
            $gainWeekFcp =0;
            $vl_actuel = null;
            $gainMonth = 0;
            $gainWeek = 0; // Réinitialisation pour éviter l'accumulation incorrecte
            $currentDate = Carbon::now();
            $gainMensuel = 0;
            $recentGains = [];
            $recentGain = [];


            foreach ($allTransactions as $transaction) {
                if ($product->products_category_id == 1) {
                    // Calculer la valeur actuelle la plus récente pour les produits FCP
                    $latestAssetValue = AssetValue::where('product_id', $transaction->product_id)
                        ->where('created_at', '>=', $transaction->date_validation)
                        ->orderBy('created_at', 'desc')
                        ->first();


                    if ($latestAssetValue) {
                        $vl_actuel = $latestAssetValue->vl;
                        $totalGain += $this->calculateFCPGain($vl_actuel, $transaction);
                        $recentGain[] = $this->calculateFCPGain($vl_actuel, $transaction);
                        $recentGains[] = [
                            'gain' => $recentGain,
                            'product_id' => $product->id,
                        ];
                    }

                    // Calculer la différence hebdomadaire si nécessaire
                    $assetValues = AssetValue::where('product_id', $transaction->product_id)
                        ->where('created_at', '>=', $transaction->date_validation)
                        ->orderBy('created_at', 'desc')
                        ->take(2)
                        ->get();

                    if ($assetValues->count() >= 1) {
                        $vl_actuel = $assetValues->first()->vl;
                        $vl_antepenultimate = $assetValues->count() == 2 ? $assetValues->last()->vl : $transaction->vl_buy;

                        // Calcul de la valorisation du portefeuille
                        $valorisationPortefeuille =  $this->calculateFCPGain($vl_actuel, $transaction);

                        // Calcul du gain entre l'avant-dernière et la dernière VL
                        $gain = $transaction->nb_part * ($vl_actuel - $vl_antepenultimate);
                        $gainWeekFcp += $gain;
                        $gainWeekTab[] = $gainWeekFcp;

                        // Calcul du cumul des gains/pertes depuis la souscription
                        $cumulGains += $valorisationPortefeuille - ($transaction->nb_part * (float)$transaction->vl_buy);

                        // Ajouter le gain cumulé au total
                        $totalGainFcp +=  $this->calculateFCPGain($vl_actuel, $transaction);
                    }




                } else {
                    // Calculer le gain pour les produits PMG
                    $vl_actuel = $transaction->vl_buy;


                    $date_echeance_exploded = explode(" ", $transaction->date_echeance)[0];
                    $date_validation_exploded = explode(" ", $transaction->date_validation)[0];

                    $gainMonth = $this->calculatePMGMonthlyGain(
                        $transaction->amount,
                        $transaction->vl_buy,
                        $date_validation_exploded,
                        $date_echeance_exploded,
                        $this->getTodaysDate()
                    )/*['valo_pf']*/;

                    //dd($gainMonth);

                    $CummulgainMonth = $this->calculatePMGMonthlyGain(
                        $transaction->amount,
                        $transaction->vl_buy,
                        $date_validation_exploded,
                        $date_echeance_exploded,
                        $this->getTodaysDate()
                    )/*['cummul_interet']*/;
                    // dd($gainMonth/360);

                    $recentGain[] = $this->calculatePMGMonthlyGain(
                        $transaction->amount,
                        $transaction->vl_buy,
                        $date_validation_exploded,
                        $date_echeance_exploded,
                        $this->getTodaysDate()
                    );
                   // $totalGain = $gainMonth + $transaction->amount;

                    if ($currentDate->diffInMonths($transaction->date_validation) != 0) {
                        $gainMensuel = $gainMonth / $currentDate->diffInMonths($transaction->date_validation);
                        $recentGain[] = $gainMensuel;
                    } else {
                        $gainMensuel = 0;
                    }
                    $recentGains[$product->id] = [
                        'gain' => $recentGain,
                    ];


                    $gainProduit = $gainMensuel;


                    //dd($currentDate->diffInMonths($transaction->date_validation));
                    // Calculer le gain mensuel pour les produits PMG
                }
            }


            if(!isset($latestValue)) $latestValue =0;
            if(!isset($secondLatestValue)) $secondLatestValue =0;
            if(!isset($value_diff)) $value_diff =0;

            $stat_val =  $this -> CalculDateEcheance($transaction->date_validation, $transaction-> duree);

            $nbMoisJour = $this ->  calculateMonthsAndDaysBetweenDates($transaction->date_validation, $transaction-> date_echeance);
            //dd($nbMoisJour);
            // Ajouter les résultats agrégés au tableau $result
            $result[] = [
                'product_name' => $product->title,
                //'gain' => $totalGain,
                'derniere_valeur_FCP' => $latestValue,
                'avant_derniere_valeur_FCP' => $secondLatestValue,
                'vl_actuel' => $vl_actuel,
                'duree' => $transaction->duree,
                'nb_part' => $transaction->nb_part,
                'montant_transaction' => $transaction->amount,
                'type_product' => $product->products_category_id,
                'vl_achat' => isset($transaction) ? $transaction->vl_buy : null, // Assurez-vous que vl_buy est correctement défini
                'gain_semaine' => $gainWeek,
                'gain_month' => $gainMonth,
                'gain_mensuel' => $this->gainMonthPmg($transaction->amount, $transaction->vl_buy),
                'slug' => $product->slug,
                'gain_vl' => max(0, $value_diff * (float)$vl_actuel),
                'souscription' => $transaction->date_validation,
                'recent_gains' => $recentGains,
                'date_echeance'=> $transaction  -> date_echeance,
                'soulte'=> $transaction  -> montant_initiale,
                'gain_echeance'=> $stat_val,
                'days_months'=> $nbMoisJour,
                'valorisation_portefeuille_fcp'=>$valorisationPortefeuille,
                'gain_semaine_fcp'=>$gainWeekFcp,
                'total_gains_fcp'=>$totalGainFcp
            ];

            //dd($result);

            //$user->gain = $totalGain;
            //$user->gain_pmg = $totalGain;

        }

        //dd($result);

        return $result;
    }


    public function getProductsWithGainsPieChart()
    {
        $products = Product::all();
        $result = [];
        $user = Auth::user();
        $productGains = [];

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

            $totalGain = 0;

            foreach ($allTransactions as $transaction) {
                if ($product->products_category_id == 1) {
                    $latestAssetValue = AssetValue::where('product_id', $transaction->product_id)
                        ->where('created_at', '>=', $transaction->date_validation)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($latestAssetValue) {
                        $vl_actuel = $latestAssetValue->vl;
                        $totalGain += $this->calculateFCPGain($vl_actuel, $transaction);
                    }
                } else {
                    $date_echeance_exploded = explode(" ", $transaction->date_echeance)[0];
                    $date_validation_exploded = explode(" ", $transaction->date_validation)[0];

                    $gainMonth = $this->calculatePMGMonthlyGain(
                        $transaction->amount,
                        $transaction->vl_buy,
                        $date_validation_exploded,
                        $date_echeance_exploded,
                        $this->getTodaysDate()
                    );

                    $totalGain += $gainMonth;
                }
            }

            $productGains[] = [
                'product_name' => $product->title,
                'total_gain' => $totalGain
            ];

            $user->gain = $totalGain;
            $user->gain_pmg = $totalGain;
        }

        return $productGains;
    }

    private function calculatePMGMonthlyGainPerDay($initialAmount, $interestRate, $specificDate, $transactionDate)
    {
        // Conversion du taux d'intérêt annuel en taux journalier
        $dailyRate_ = (float)$interestRate / 100 ;

        $dailyRate=$dailyRate_/360;
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

                //error_log("la valorisation jour  ".$i.": ".$interest);

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

    private function calculatePMGMonthlyGain($initialAmount, $annualInterestRate, $startDate, $endDate, $currentDate)
    {
        // Conversion des dates en objets DateTime
        $startDate = new DateTime($startDate);
        $endDate = new DateTime($endDate);
        $currentDate = new DateTime($currentDate);
        $portofolio_=0;

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






    private function getTodaysDate()
    {
        return date('Y-m-d');
    }

    private function gainMonthPmg($initialAmount, $annuelInteret )
    {
        $interest = (float)$annuelInteret;
        $monthlyRate = ( $interest / 100) / 12;

        $interestPerMonth = $initialAmount * $monthlyRate;

        return $interestPerMonth;
    }


    private function calculateDaysFromMonths($months, $transactionDate)
    {
        // Obtention du timestamp de la date de transaction
        $transactionTimestamp = strtotime($transactionDate);

        // Calcul du timestamp de la fin du mois en cours
        $currentMonthEndTimestamp = strtotime(date('Y-m-t', $transactionTimestamp));

        // Calcul du nombre de jours dans le mois de la transaction
        $daysInTransactionMonth = date('t', $transactionTimestamp);

        // Calcul du nombre de jours restants dans la période spécifiée (en mois complets)
        $remainingDays = $months * 30.4;

        // Calcul du nombre total de jours
        $totalDays = $daysInTransactionMonth + $remainingDays;

        // Si la date de transaction est après le dernier jour du mois, ajuster le nombre de jours
        if ($transactionTimestamp > $currentMonthEndTimestamp) {
            $totalDays += $daysInTransactionMonth; // Ajouter le nombre de jours du mois suivant
        }

        return $totalDays;
    }


    function calculateMonthsAndDaysBetweenDates($startDate, $endDate)
    {
        // Convertir les dates en objets DateTime
        $startDateTime = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);

        // Calculer la différence entre les deux dates
        $interval = $startDateTime->diff($endDateTime);

        // Extraire les mois et jours de l'intervalle
        $months = $interval->m + ($interval->y * 12);
        $days = $interval->d;

        // Retourner les résultats sous forme de tableau
        return [
            'months' => $months,
            'days' => $days
        ];
    }

    public function showProductsWithGains()
    {

        $productsWithGains = $this->getProductsWithGains();
        $user = Auth::user();
        return view('front-end.my-products', compact('productsWithGains', 'user'));
    }


    public function showProductGain($slug)
    {

        $product = Product::where('slug', $slug)->first();
        $result = $this->getProductsWithGains();
        return view('front-end.product-detail-gain', compact('result', 'product'));
    }



    public function CalculDateEcheance($startDate, $monthsToAdd)
    {
        // Convertir la date de départ en instance de Carbon
        $startDate = Carbon::parse($startDate);

        // Ajouter les mois à la date de départ
        $endDate = $startDate->copy()->addMonths($monthsToAdd);

        // Obtenir la date actuelle
        $currentDate = Carbon::now();

        $status_duree="";
        // Comparer la date actuelle avec la date finale
        if ($currentDate->lessThan($endDate)) {
            // Afficher l'information si la date actuelle est inférieure à la date finale
            $status_duree = 1;
            return $status_duree;
        } else {
            // Sinon, ne rien afficher ou afficher une autre information
            $status_duree = 0;
            return $status_duree;
        }
    }


}




