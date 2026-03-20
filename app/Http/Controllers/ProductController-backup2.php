<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\Models\User;
use App\Transaction;
use App\TransactionSupplementaire;
use App\AssetValue;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use DateTime;
use Session;


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
            $cumulGains = 0;
            $gainWeekFcp = 0;
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
                        $valorisationPortefeuille = $this->calculateFCPGain($vl_actuel, $transaction);

                        // Calcul du gain entre l'avant-dernière et la dernière VL
                        $gain = $transaction->nb_part * ($vl_actuel - $vl_antepenultimate);
                        $gainWeekFcp += $gain;
                        $gainWeekTab[] = $gainWeekFcp;

                        // Calcul du cumul des gains/pertes depuis la souscription
                        $cumulGains += $valorisationPortefeuille - ($transaction->nb_part * $transaction->vl_buy);

                        // Ajouter le gain cumulé au total
                        $totalGainFcp += $this->calculateFCPGain($vl_actuel, $transaction);
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
                    )/*['valo_pf']*/ ;

                    //dd($gainMonth);

                    $CummulgainMonth = $this->calculatePMGMonthlyGain(
                        $transaction->amount,
                        $transaction->vl_buy,
                        $date_validation_exploded,
                        $date_echeance_exploded,
                        $this->getTodaysDate()
                    )/*['cummul_interet']*/ ;
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


            if (!isset($latestValue))
                $latestValue = 0;
            if (!isset($secondLatestValue))
                $secondLatestValue = 0;
            if (!isset($value_diff))
                $value_diff = 0;

            $stat_val = $this->CalculDateEcheance($transaction->date_validation, $transaction->duree);

            $nbMoisJour = $this->calculateMonthsAndDaysBetweenDates($transaction->date_validation, $transaction->date_echeance);
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
                'gain_vl' => max(0, $value_diff * (float) $vl_actuel),
                'souscription' => $transaction->date_validation,
                'recent_gains' => $recentGains,
                'date_echeance' => $transaction->date_echeance,
                'soulte' => $transaction->montant_initiale,
                'gain_echeance' => $stat_val,
                'days_months' => $nbMoisJour,
                'valorisation_portefeuille_fcp' => isset($valorisationPortefeuille) ? $valorisationPortefeuille : 0,
                'gain_semaine_fcp' => $gainWeekFcp,
                'total_gains_fcp' => $totalGainFcp
            ];


            $totalPfPmg = 0;
            $totalPfFcp = 0;
            $totalSoulte = 0;




            foreach ($result as $res) {
                // Vérification du type de produit
                if ($res['type_product'] == 1) { // Produit FCP
                    if (isset($res['total_gains_fcp'])) {
                        // Cumul des gains pour les produits FCP
                        $totalPfFcp += $res['montant_transaction'] + $res['total_gains_fcp'];
                    }
                } elseif ($res['type_product'] == 2) { // Produit PMG
                    if (isset($res['gain_month'])) {
                        // Cumul des gains pour les produits PMG
                        $totalSoulte += $res['soulte'];
                        $totalPfPmg += $res['gain_month'] + $res['soulte'];
                    }
                }
            }

            //dd($totalPfPmg, $totalSoulte);
            //dd($result);

            //$user->gain = $totalGain;
            // $user->gain_pmg = $result['gain_month'] + $result['soulte'];



        }

        //dd($result);

        return $result;
    }


    public function getProductsWithGainsUser($user_id)
    {
        $products = Product::all();
        $result = [];
        foreach ($products as $product) {
            // Récupérer toutes les transactions principales pour le produit
            $transactions = Transaction::where('user_id', $user_id)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();


            // Récupérer toutes les transactions supplémentaires pour le produit
            $additionalTransactions = TransactionSupplementaire::where('user_id', $user_id)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            // Fusionner les transactions principales et supplémentaires
            $allTransactions = $transactions->merge($additionalTransactions);

            //dd($allTransactions);
            if ($allTransactions->isEmpty()) {
                continue;
            }



            $totalGain = 0;
            $totalGainFcp = 0;
            $cumulGains = 0;
            $gainWeekFcp = 0;
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
                        $valorisationPortefeuille = $this->calculateFCPGain($vl_actuel, $transaction);

                        // Calcul du gain entre l'avant-dernière et la dernière VL
                        $gain = $transaction->nb_part * ($vl_actuel - $vl_antepenultimate);
                        $gainWeekFcp += $gain;
                        $gainWeekTab[] = $gainWeekFcp;

                        // Calcul du cumul des gains/pertes depuis la souscription
                        $cumulGains += $valorisationPortefeuille - ($transaction->nb_part * $transaction->vl_buy);

                        // Ajouter le gain cumulé au total
                        $totalGainFcp += $this->calculateFCPGain($vl_actuel, $transaction);
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
                    )/*['valo_pf']*/ ;

                    //dd($gainMonth);

                    $CummulgainMonth = $this->calculatePMGMonthlyGain(
                        $transaction->amount,
                        $transaction->vl_buy,
                        $date_validation_exploded,
                        $date_echeance_exploded,
                        $this->getTodaysDate()
                    )/*['cummul_interet']*/ ;
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


            if (!isset($latestValue))
                $latestValue = 0;
            if (!isset($secondLatestValue))
                $secondLatestValue = 0;
            if (!isset($value_diff))
                $value_diff = 0;

            $stat_val = $this->CalculDateEcheance($transaction->date_validation, $transaction->duree);

            $nbMoisJour = $this->calculateMonthsAndDaysBetweenDates($transaction->date_validation, $transaction->date_echeance);
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
                'gain_vl' => max(0, $value_diff * (float) $vl_actuel),
                'souscription' => $transaction->date_validation,
                'recent_gains' => $recentGains,
                'date_echeance' => $transaction->date_echeance,
                'soulte' => $transaction->montant_initiale,
                'gain_echeance' => $stat_val,
                'days_months' => $nbMoisJour,
                'valorisation_portefeuille_fcp' => isset($valorisationPortefeuille) ? $valorisationPortefeuille : 0,
                'gain_semaine_fcp' => $gainWeekFcp,
                'total_gains_fcp' => $totalGainFcp
            ];




            $totalPfPmg = 0;
            $totalPfFcp = 0;

            foreach ($result as $res) {
                // Vérification du type de produit
                if ($res['type_product'] == 1) { // Produit FCP
                    if (isset($res['montant_transaction']) && isset($res['total_gains_fcp'])) {
                        // Cumul des gains pour les produits FCP
                        $totalPfFcp += (float) $res['montant_transaction'] + (float) $res['total_gains_fcp'];
                    }
                } elseif ($res['type_product'] == 2) { // Produit PMG
                    if (isset($res['gain_month']) && isset($res['soulte'])) {
                        // Cumul des gains pour les produits PMG
                        $totalPfPmg += (float) $res['gain_month'] + (float) $res['soulte'];
                    }
                }
            }

            // Vérification avant de mettre en session
            if ($totalPfFcp !== null && $totalPfFcp > 0) {
                Session::put('pf_fcp', $totalPfFcp);
            } else {
                Session::put('pf_fcp', 0);
            }

            if ($totalPfPmg !== null && $totalPfPmg > 0) {
                Session::put('pf_pmg', $totalPfPmg);
            } else {
                Session::put('pf_pmg', 0);
            }


            /* Session::put('pf_pmg', $totalPfPmg);
            Session::put('pf_fcp', $totalPfFcp); */

            //dd($result);

            //$user->gain = $totalGain;
            //$user->gain_pmg = $result['gain_month'] + $result['soulte'];



        }
        //dd(Session::get('pf_pmg'));

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

    public function calculatePMGMonthlyGainPerDay($initialAmount, $interestRate, $specificDate, $transactionDate)
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

    public function calculatePMGMonthlyGain($initialAmount, $annualInterestRate, $startDate, $endDate, $currentDate)
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




    public function countUserProducts($userId)
    {

        // Compter le nombre de produits distincts dans la table transactions
        $countFromTransactions = Transaction::where('user_id', $userId)
            ->distinct('product_id')
            ->count('product_id');

        // Compter le nombre de produits distincts dans la table transaction_supplementaire
        $countFromSupplementaryTransactions = TransactionSupplementaire::where('user_id', $userId)
            ->distinct('product_id')
            ->count('product_id');

        // Ajouter les deux comptes ensemble pour obtenir le total
        $totalProductCount = $countFromTransactions + $countFromSupplementaryTransactions;

        return $totalProductCount;
    }



    public function getTodaysDate()
    {
        return date('Y-m-d');
    }

    public function gainMonthPmg($initialAmount, $annuelInteret)
    {
        $monthlyRate = ($annuelInteret / 100) / 12;

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

        $status_duree = "";
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




    public function indexAssetManager()
    {

        $customers = User::where('role_id', '2')->orderBy('created_at', 'desc')->get();


        // Ajouter le nombre de produits pour chaque utilisateur
        foreach ($customers as $customer) {
            // Compter le nombre de produits distincts dans la table transactions
            $countFromTransactions = Transaction::where('user_id', $customer->id)->where('status', 'Succès')
                ->distinct('product_id')
                ->count('product_id');

            // Compter le nombre de produits distincts dans la table transaction_supplementaire
            $countFromSupplementaryTransactions = TransactionSupplementaire::where('user_id', $customer->id)->where('status', 'Succès')
                ->distinct('product_id')
                ->count('product_id');

            // Calculer le total des produits distincts pour cet utilisateur
            $totalProductCount = $countFromTransactions + $countFromSupplementaryTransactions;

            // Ajouter le nombre total de produits au modèle User
            $customer->product_count = $totalProductCount;
        }

        return view('front-end.asset-manager')->with('customers', $customers);

    }

    public function customers()
    {
        // Récupérer les clients avec le rôle '2'
        $customers = User::where('role_id', '2')->orderBy('created_at', 'desc')->get();

        foreach ($customers as $customer) {
            // Initialiser les variables pour chaque client
            $totalPortefeuilleFcp = 0;
            $totalPortefeuillePmg = 0;
            $totalGainMensuelFcp = 0;
            $totalGainMensuelPmg = 0;
            $recentGainsFcp = [];
            $recentGainsPmg = [];

            // Calculer le nombre total de produits distincts pour chaque utilisateur
            $countFromTransactions = Transaction::where('user_id', $customer->id)
                ->distinct('product_id')
                ->count('product_id');

            $countFromSupplementaryTransactions = TransactionSupplementaire::where('user_id', $customer->id)
                ->distinct('product_id')
                ->count('product_id');

            $totalProductCount = $countFromTransactions + $countFromSupplementaryTransactions;

            // Ajouter le nombre total de produits au modèle User
            $customer->product_count = $totalProductCount;

            // Récupérer les gains pour chaque produit de l'utilisateur
            $productsWithGains = $this->getProductsWithGainsUser($customer->id);

            foreach ($productsWithGains as $product) {
                if ($product['type_product'] == 1) {
                    // Cumul des portefeuilles FCP
                    $totalPortefeuilleFcp += $product['valorisation_portefeuille_fcp'];
                    // Cumul des gains mensuels FCP
                    $totalGainMensuelFcp += $product['gain_mensuel'];
                    // Stockage des gains récents FCP
                    $recentGainsFcp[] = $product['recent_gains'];
                } else {
                    // Cumul des portefeuilles PMG
                    $totalPortefeuillePmg += $product['gain_month'] + $product['soulte'];
                    // Cumul des gains mensuels PMG
                    $totalGainMensuelPmg += $product['gain_mensuel'];
                    // Stockage des gains récents PMG
                    $recentGainsPmg[] = $product['recent_gains'];
                }
            }

            // Calculer le portefeuille total pour ce client
            $customer->portefeuille_total = $customer->gain + $customer->solde + $totalPortefeuillePmg;
            $customer->gainMensuelFcp = $totalGainMensuelFcp;
            $customer->gainMensuelPmg = $totalGainMensuelPmg;
            $customer->recentGainsFcp = $recentGainsFcp;
            $customer->recentGainsPmg = $recentGainsPmg;
        }

        // Passer les clients à la vue
        return view('front-end.customer', ['customers' => $customers]);
    }



    public function customersDetail(Request $request)
    {
        // Récupérer les informations du client
        $customer = User::findOrFail($request->customer);

        // Compter le nombre de produits distincts que le client possède
        $product_nb = $this->countUserProducts($customer->id);

        // Initialiser les variables pour accumuler les valeurs des portefeuilles
        $totalPortefeuilleFcp = 0;
        $totalPortefeuillePmg = 0;

        // Récupérer les gains pour chaque produit du client
        $productsWithGains = $this->getProductsWithGainsUser($customer->id);

        foreach ($productsWithGains as $product) {
            if ($product['type_product'] == 1) {
                // Cumul des portefeuilles FCP
                $totalPortefeuilleFcp += $product['valorisation_portefeuille_fcp'];
            } else {
                // Cumul des portefeuilles PMG
                $totalPortefeuillePmg += $product['gain_month'] + $product['soulte'];
            }
        }

        // Calculer le portefeuille total pour ce client

        // dd($customer->id, $totalPortefeuilleFcp, $totalPortefeuillePmg);
        $portefeuille_total = $customer->gain + $customer->solde + $totalPortefeuillePmg;
        $productsWithGains = $this->getProductsWithGainsUser($customer->id);

        // Passer les données à la vue
        return view('front-end.customer-detail')
            ->with('customer', $customer)
            ->with('product_nb', $product_nb)
            ->with('productsWithGains', $productsWithGains)
            ->with('portefeuille_total', $portefeuille_total);
    }


}




