<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionSupplementaire;
use App\Models\AssetValue;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use DateTime;
use Illuminate\Support\Facades\Session;
use App\Models\FinancialMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;


class ProductController extends Controller
{
    public function calculateFCPGain($vl_actuel, $transaction)
    {
        $totalInvested = $transaction->amount;
        $montantTotal = $transaction->nb_part * $vl_actuel;
        $gain = max(0, round($montantTotal - $totalInvested, 2));
        return $gain;
    }



    /**
     * Calcule la valorisation FCP basée sur les mouvements réels (parts)
     * Prend en compte les rachats et rajouts via la table fcp_movements
     */
    public function getFcpPortfolioValue($userId, $productId, $dateReference)
    {
        $nbPartsTotal = DB::table('fcp_movements')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('date_operation', '<=', $dateReference)
            ->sum('nb_parts_change') ?? 0;

        $latestVl = AssetValue::where('product_id', $productId)
            ->where('date_vl', '<=', $dateReference)
            ->orderBy('date_vl', 'desc')
            ->first();

        $vl = $latestVl ? (float)$latestVl->vl : 0;

        return [
            'parts' => (float)$nbPartsTotal,
            'vl' => $vl,
            'valorisation' => (float)$nbPartsTotal * $vl
        ];
    }

    public function calculateFCPGainWeek($vl_actuel, $transaction)
    {
        $totalInvested = $transaction->amount;
        $montantTotal = $transaction->nb_part * $vl_actuel;
        $gain = max(0, round($montantTotal - $totalInvested, 2));
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
                // ->whereDate('date_echeance', '<', now())
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
                'gain_month' => $gainMonth - $transaction->interet_rachat,
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
        $service = new \App\Services\InvestmentService();
        
        // Synchroniser si nécessaire (optionnel, mais utile lors de la transition)
        $service->syncFcpMovements();
        
        $fcpPortfolio = $service->getConsolidatedFcpPortfolio($user_id);
        
        // On récupère aussi les PMG car cette méthode semble être utilisée par GainCalculationService pour les deux
        $products = Product::where('products_category_id', 2)->get();
        $pmgResult = [];
        $currentDate = Carbon::now();

        foreach ($products as $product) {
            $transactions = Transaction::where('user_id', $user_id)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            foreach ($transactions as $transaction) {
                $dateEcheance = Carbon::parse($transaction->date_echeance);
                if ($dateEcheance->lt($currentDate)) continue;

                $totalValo = $this->calculatePMGValorization($transaction, $currentDate);

                $pmgResult[] = [
                    'id' => $product->id,
                    'product_id' => $product->id,
                    'product_name' => $product->title,
                    'type_product' => 2,
                    'capital_investi' => (float)$transaction->amount,
                    'montant_transaction' => (float)$transaction->amount, // Legacy support
                    'interets_generes' => (float)$totalValo - (float)$transaction->amount,
                    'gain_month' => (float)$totalValo - (float)$transaction->amount, // Legacy support
                    'soulte' => (float)$transaction->amount, // Legacy support
                    'portfolio_valeur' => (float)$totalValo,
                    'vl_actuel' => $transaction->vl_buy,
                    'vl_achat' => $transaction->vl_buy,
                    'date_echeance' => $transaction->date_echeance,
                    'souscription' => $transaction->date_validation,
                    'slug' => $product->slug,
                    'days_months' => $this->calculateMonthsAndDaysBetweenDates($transaction->date_validation, $transaction->date_echeance),
                    'gain_mensuel' => $this->gainMonthPmg($transaction->amount, $transaction->vl_buy),
                ];
            }
        }

        // Transformer le format FCP pour la compatibilité avec les vues existantes
        $fcpResult = array_map(function($p) {
            return [
                'id' => $p['product_id'],
                'product_id' => $p['product_id'],
                'product_name' => $p['name'],
                'type_product' => 1,
                'montant_transaction' => $p['total_invested'],
                'capital_investi' => $p['total_invested'],
                'total_gains_fcp' => $p['total_gain'],
                'gain_semaine_fcp' => $p['weekly_gain'],
                'portfolio_valeur' => $p['valuation'], // Nouveau nom
                'valorisation_portefeuille_fcp' => $p['valuation'], // Legacy
                'nb_part' => $p['total_parts'],
                'pru' => $p['pru'], // Nouveau: Prix de Revient Unitaire
                'vl_achat' => $p['current_vl'], // Temporaire ou vl_buy initial? 
                'vl_actuel' => $p['current_vl'],
                'slug' => $p['slug'],
                'date_echeance' => Carbon::now()->addYears(10)->toDateString(), // FCP n'a pas d'échéance fixe en général
                'souscription' => Carbon::now()->toDateString(), // Devrait être récupéré des mouvements
            ];
        }, $fcpPortfolio);

        return array_merge($fcpResult, $pmgResult);
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

                    if (strtotime($date_echeance_exploded) > strtotime($this->getTodaysDate())) {
                        $gainMonth = $this->calculatePMGMonthlyGain(
                            $transaction->amount,
                            $transaction->vl_buy,
                            $date_validation_exploded,
                            $date_echeance_exploded,
                            $this->getTodaysDate()
                        );

                        $totalGain += round($gainMonth + $product['soulte'], 2);
                    }
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
    /**
     * Calcul dynamique avec capitalisation annuelle automatique
     */
    public function calculatePMGMonthlyGain($initialAmount, $annualRate, $startDate, $endDate, $currentDate)
    {
        // 1. On part du dernier mouvement réel enregistré en base
        $lastMovement = DB::table('financial_movements')
            ->where('date_operation', '<=', $currentDate)
            ->orderBy('date_operation', 'desc')
            ->first();

        $currentCapital = $lastMovement ? (float)$lastMovement->capital_after : (float)$initialAmount;
        $dateCursor = Carbon::parse($lastMovement ? $lastMovement->date_operation : $startDate);
        $targetDate = Carbon::parse($currentDate)->min(Carbon::parse($endDate));

        // 2. Boucle de capitalisation annuelle automatique
        while ($dateCursor->copy()->addYear()->lte($targetDate)) {
            $nextAnniversary = $dateCursor->copy()->addYear();
            $daysInYear = $dateCursor->diffInDays($nextAnniversary); // Souvent 365 ou 366

            // Calcul et ajout au capital (Capitalisation)
            $yearInterest = ($currentCapital * ($annualRate / 100) * $daysInYear) / 360;
            $currentCapital += $yearInterest;
            $dateCursor = $nextAnniversary;
        }

        // 3. Calcul des intérêts pour la période résiduelle (moins d'un an)
        $remainingDays = $dateCursor->diffInDays($targetDate);
        $finalInterest = ($currentCapital * ($annualRate / 100) * $remainingDays) / 360;

        return round($currentCapital + $finalInterest, 0);
    }




    public function countUserProducts($userId)
    {

        // Compter le nombre de produits distincts dans la table transactions
        $countFromTransactions = Transaction::where('user_id', $userId)
            ->where('status', 'Succès')
            ->distinct('product_id')
            ->count('product_id');

        // Compter le nombre de produits distincts dans la table transaction_supplementaire
        $countFromSupplementaryTransactions = TransactionSupplementaire::where('user_id', $userId)
            ->distinct('product_id')
            ->where('status', 'Succès')
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
        return ($initialAmount * (($annuelInteret / 100) / 12));
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

    private function calculateMonthsAndDaysBetweenDates($startDate, $endDate)
    {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = $start->diff($end);
        return ['months' => $interval->m + ($interval->y * 12), 'days' => $interval->d];
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

    /**
     * Calcule la valorisation PMG en intercalant mouvements réels et anniversaires théoriques
     *
     */
public function calculatePMGValorization($trans, $refDate)
{
    $targetDate = Carbon::parse($refDate)->min(Carbon::parse($trans->date_echeance));
    $rate = (float)$trans->vl_buy / 100;

    // 1. On cherche le capital effectif à la date cible (ignore les capitalisations futures)
    $lastMovement = DB::table('financial_movements')
        ->where('transaction_id', $trans->id)
        ->whereIn('type', ['capitalisation_interets', 'rachat_partiel'])
        ->where('date_operation', '<=', $targetDate->toDateString())
        ->orderBy('date_operation', 'desc')
        ->first();

    $baseCapital = $lastMovement ? (float)$lastMovement->capital_after : (float)$trans->amount;
    $startDate = $lastMovement ? Carbon::parse($lastMovement->date_operation) : Carbon::parse($trans->date_validation);

    // 2. Calcul des intérêts courus (Base 360)
    $totalInterest = 0;
    if ($targetDate->gt($startDate)) {
        $nextMonth = $startDate->copy()->addMonthNoOverflow()->startOfMonth();

        if ($targetDate->lt($nextMonth)) {
            $totalInterest = ($baseCapital * $rate * $startDate->diffInDays($targetDate)) / 360;
        } else {
            $totalInterest = ($baseCapital * $rate * $startDate->diffInDays($startDate->copy()->endOfMonth())) / 360;
            $fullMonths = $nextMonth->diffInMonths($targetDate->copy()->addDay());
            $totalInterest += ($baseCapital * ($rate / 12)) * $fullMonths;
            $lastMonthStart = $nextMonth->copy()->addMonths($fullMonths);
            if ($lastMonthStart->lt($targetDate)) {
                $totalInterest += ($baseCapital * $rate * $lastMonthStart->diffInDays($targetDate)) / 360;
            }
        }
    }

    $precompte = DB::table('financial_movements')
        ->where('transaction_id', $trans->id)
        ->where('type', 'precompte_interets')
        ->value('amount') ?? 0;

    // Valorisation = (Capital à l'instant T - Précompte) + Intérêts courus du cycle
    return round(($baseCapital - $precompte) + $totalInterest, 0);
}
    //backup de la fonction de valorisation PMG avant refonte complète
    /* public function calculatePMGValorization($trans, $refDate)
    {
        $dateEcheance = Carbon::parse($trans->date_echeance);
        $targetDate = Carbon::parse($refDate)->min($dateEcheance);
        $rate = (float)$trans->vl_buy / 100;

        // 1. RECHERCHE DU DERNIER MOUVEMENT (Pivot de Capitalisation)
        // On cherche si une capitalisation a eu lieu AVANT ou à la date cible
        $lastMovement = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->where('date_operation', '<=', $targetDate->toDateString())
            ->orderBy('date_operation', 'desc')
            ->first();

        if ($lastMovement) {
            // Le capital est déjà mis à jour (ex: 21 400 000)
            $baseCapital = (float)$lastMovement->capital_after;
            $startDate = Carbon::parse($lastMovement->date_operation);
        } else {
            // On est encore sur le capital initial (ex: 20 000 000)
            $baseCapital = (float)$trans->amount;
            $startDate = Carbon::parse($trans->date_validation);
        }

        if ($startDate->gt($targetDate)) return round($baseCapital, 0);

        $totalInterest = 0;
        $nextMonth = $startDate->copy()->addMonthNoOverflow()->startOfMonth();

        // 2. LOGIQUE DE CALCUL HYBRIDE (Base 360)

        // Cas A : On est dans le mois de la signature (ou le mois de la capitalisation)
        if ($targetDate->lt($nextMonth)) {
            $days = $startDate->diffInDays($targetDate);
            $totalInterest = ($baseCapital * $rate * $days) / 360;
        }
        // Cas B : On a franchi au moins un premier mois civil
        else {
            // 1. Prorata du mois de départ (ex: du 23 au 31 = 8 jours)
            $daysInFirstMonth = $startDate->diffInDays($startDate->copy()->endOfMonth());
            $totalInterest = ($baseCapital * $rate * $daysInFirstMonth) / 360;

            // 2. Mois pleins (Forfait 1/12)
            $fullMonths = $nextMonth->diffInMonths($targetDate->copy()->addDay());
            $totalInterest += ($baseCapital * ($rate / 12)) * $fullMonths;

            // 3. Prorata du mois final (si la targetDate est en cours de mois)
            $lastMonthStart = $nextMonth->copy()->addMonths($fullMonths);
            if ($lastMonthStart->lt($targetDate)) {
                $days = $lastMonthStart->diffInDays($targetDate);
                $totalInterest += ($baseCapital * $rate * $days) / 360;
            }
        }

        return round($baseCapital + $totalInterest, 0);
    }
 */
    /**
     * Prépare les données consolidées pour la vue Customer
     */
    public function customers()
    {
        $customers = User::where('role_id', '2')->orderBy('name', 'asc')->get();
        $currentDate = Carbon::now();

        foreach ($customers as $customer) {
            $totalInvestiActive = 0;
            $totalValorisationActive = 0;
            $activeContractsCount = 0;

            $transactions = Transaction::where('user_id', $customer->id)
                ->where('status', 'Succès')
                ->get();

            foreach ($transactions as $trans) {
                $dateEcheance = Carbon::parse($trans->date_echeance);

                // ✅ Seuls les produits non échus comptent dans l'investissement actif
                if ($dateEcheance->gte($currentDate)) {
                    $activeContractsCount++;
                    $totalInvestiActive += (float)$trans->amount;

                    if ($trans->product->products_category_id == 2) {
                        // ✅ Appel corrigé avec 2 arguments seulement
                        $totalValorisationActive += $this->calculatePMGValorization($trans, $currentDate);
                    } else {
                        $fcpData = $this->getFcpPortfolioValue($customer->id, $trans->product_id, $currentDate);
                        $totalValorisationActive += $fcpData['valorisation'];
                    }
                }
            }

            // ✅ IMPORTANT : On attache les valeurs à l'objet pour la vue
            $customer->total_capital = $totalInvestiActive;
            $customer->portefeuille_total = $totalValorisationActive;
            $customer->total_interets = max(0, $totalValorisationActive - $totalInvestiActive);
            $customer->product_count = $activeContractsCount;
        }

        return view('front-end.customer', compact('customers'));
    }
    function generateUniqueCode($user)
    {
        return strtoupper(substr(md5($user->id . $user->name . $user->created_at), 0, 10));
    }

    public function getMaskedName($user)
    {
        return strtoupper(
            substr(md5($user->id . $user->name . $user->created_at), 0, 10)
        );
    }



    /** 
     * Détail Client : Valorisation précise et statistiques
     */
    public function customersDetail($customer_id)
    {
        $customer = User::findOrFail($customer_id);
        $currentDate = Carbon::now();

        // Récupération des produits avec gains (via la fonction que nous avons déjà harmonisée)
        $allProducts = $this->getProductsWithGainsUser($customer_id);

        // Initialisation des compteurs pour les boîtes de statistiques
        $totalInvestiActive = 0;
        $portefeuillePMG = 0;
        $portefeuilleFCP = 0;

        foreach ($allProducts as $item) {
            // On ne cumule que le capital des produits actifs
            $totalInvestiActive += $item['capital_investi'];

            if ($item['type_product'] == 2) { // PMG
                $portefeuillePMG += $item['portfolio_valeur'];
            } else { // FCP
                $portefeuilleFCP += $item['portfolio_valeur'];
            }
        }

        $portefeuilleTotal = $portefeuillePMG + $portefeuilleFCP;

        // ✅ Calcul des intérêts : Valeur actuelle totale - Capital initial total
        $totalInterets = max(0, $portefeuilleTotal - $totalInvestiActive);

        return view('front-end.customer-detail', [
            'customer' => $customer,
            'productsWithGains' => $allProducts,
            'portefeuille_total' => $portefeuilleTotal,
            'portefeuille_pmg' => $portefeuillePMG,
            'portefeuille_fcp' => $portefeuilleFCP,
            'total_interets' => $totalInterets,
            'total_investi' => $totalInvestiActive // Variable pour afficher le capital total si besoin
        ]);
    }
    

/*     public function downloadStatement($transaction_id)
    {
        $transaction = Transaction::with(['user', 'product'])->findOrFail($transaction_id);
        
        // Récupérer l'historique complet trié par date
        $movements = FinancialMovement::where('transaction_id', $transaction_id)
                        ->orderBy('date_operation', 'asc')
                        ->get();

        $pdf = Pdf::loadView('front-end.releves.releve-history', compact('transaction', 'movements'));
        
        return $pdf->download("releve_{$transaction->ref}.pdf");
    } */
    /**
     * Téléchargement du relevé historique
     * Distingue automatiquement le format FCP (Parts) du PMG (Cash)
     */
    public function downloadStatement($transaction_id)
    {
        $transaction = Transaction::with(['user', 'product'])->findOrFail($transaction_id);

        if ($transaction->product->products_category_id == 2) {
            // Relevé PMG : Historique des flux financiers (capitalisation/rachats)
            $movements = FinancialMovement::where('transaction_id', $transaction_id)
                ->orderBy('date_operation', 'asc')
                ->get();
            $view = 'front-end.releves.releve-history';
        } else {
            // Relevé FCP : Historique des parts
            $movements = DB::table('fcp_movements')
                ->where('transaction_id', $transaction_id)
                ->orderBy('date_operation', 'asc')
                ->get();
            $view = 'front-end.releves.releve-history-fcp';
        }

        $pdf = Pdf::loadView($view, [
            'transaction' => $transaction,
            'movements' => $movements,
            'client' => $transaction->user
        ]);

        return $pdf->download("releve_{$transaction->ref}.pdf");
    }




    /**
     * Fonction de simulation et de diagnostic pour les logs
     * @param string $dateRef Format 'Y-m-d' (ex: '2026-01-31')
     */
    public function debugClientPortfolios($dateRef)
    {
        $targetDate = Carbon::parse($dateRef);
        $customers = User::where('role_id', '2')->get();

        Log::channel('single')->info("=== DÉBUT SIMULATION KORI - DATE : $dateRef ===");

        foreach ($customers as $customer) {
            $transactions = Transaction::where('user_id', $customer->id)
                ->where('status', 'Succès')
                ->where('date_validation', '<=', $targetDate)
                ->get();

            if ($transactions->isEmpty()) continue;

            Log::channel('single')->info("CLIENT : {$customer->name} (ID: {$customer->id})");

            foreach ($transactions as $trans) {
                $dateEcheance = Carbon::parse($trans->date_echeance);
                $isEchu = $dateEcheance->lt($targetDate);

                // Analyse des mouvements financiers
                $movements = DB::table('financial_movements')
                    ->where('transaction_id', $trans->id)
                    ->where('date_operation', '<=', $targetDate)
                    ->get();

                $hasRachatPartiel = $movements->where('type', 'rachat_partiel')->isNotEmpty();
                $hasRachatTotal = $movements->where('type', 'rachat_total')->isNotEmpty();
                $hasCapitalisation = $movements->where('type', 'capitalisation_interets')->isNotEmpty();
                $hasAjout = $movements->where('type', 'versement_complementaire')->isNotEmpty();
                $hasPrecompte = $movements->where('type', 'precompte_interets')->isNotEmpty();

                // ✅ CORRECTION ICI : On passe l'objet $trans entier et la date cible
                $valorisation = 0;
                if ($trans->product->products_category_id == 2) {
                    $valorisation = $this->calculatePMGValorization($trans, $targetDate);
                } else {
                    $fcp = $this->getFcpPortfolioValue($customer->id, $trans->product_id, $targetDate);
                    $valorisation = $fcp['valorisation'];
                }

                // Calcul du capital investi réel (incluant les versements/rachats historiques)
                // Pour le log, on compare à l'investissement initial de la transaction
                $interets = $valorisation - $trans->amount;

                $logMsg = sprintf(
                    "  - Produit: %s | ID Trans: %s | Status: %s\n" .
                        "    Initial: %s | Valo à Date: %s | Intérêts: %s\n" .
                        "    Détails: [Rachat Partiel: %s][Rachat Total: %s] [Capit.: %s] [Ajout: %s] [Précompte: %s]",
                    $trans->product->title,
                    $trans->id,
                    $isEchu ? "ÉCHU" : "ACTIF",
                    number_format($trans->amount, 0, '.', ' '),
                    number_format($valorisation, 0, '.', ' '),
                    number_format($interets, 0, '.', ' '),
                    $hasRachatPartiel ? "OUI" : "NON",
                    $hasRachatTotal ? "OUI" : "NON",
                    $hasCapitalisation ? "OUI" : "NON",
                    $hasAjout ? "OUI" : "NON",
                    $hasPrecompte ? "OUI" : "NON"
                );

                Log::channel('single')->info($logMsg);
            }
            Log::channel('single')->info("-------------------------------------------");
        }

        Log::channel('single')->info("=== FIN DE SIMULATION ===");
        return "Simulation terminée. Consultez storage/logs/laravel.log";
    }

    /**
     * Synchronise les capitalisations pour toutes les transactions (existantes et futures)
     *
     */
    /* public function syncAnniversaryMovements()
    {
        // 1. Récupérer TOUTES les transactions de type PMG (Catégorie 2) validées
        $transactions = Transaction::where('status', 'Succès')
            ->whereHas('product', function ($q) {
                $q->where('products_category_id', 2);
            })->get();

        $today = Carbon::now();
        $syncReport = [];

        foreach ($transactions as $trans) {
            // Date de départ pour le calcul des anniversaires
            $startDate = Carbon::parse($trans->date_validation);
            $dateEcheance = Carbon::parse($trans->date_echeance);

            // On définit la limite de calcul (soit aujourd'hui, soit l'échéance si elle est passée)
            $limitDate = $today->copy()->min($dateEcheance);

            $cursor = $startDate->copy()->addYear();

            // 2. Boucle sur chaque année possible du contrat
            while ($cursor->lte($limitDate)) {
                $dateAnniversaire = $cursor->toDateString();

                // Vérifier si une capitalisation existe déjà à cette date précise pour cette transaction
                $alreadyExists = DB::table('financial_movements')
                    ->where('transaction_id', $trans->id)
                    ->where('type', 'capitalisation_interets')
                    ->whereDate('date_operation', $dateAnniversaire)
                    ->exists();

                if (!$alreadyExists) {
                    // Calcul de la valorisation exacte à cette date anniversaire
                    $valeurPortefeuille = $this->calculatePMGValorization($trans, $cursor);

                    // Récupération du capital juste avant cet anniversaire
                    $capitalAvant = DB::table('financial_movements')
                        ->where('transaction_id', $trans->id)
                        ->where('date_operation', '<', $dateAnniversaire)
                        ->orderBy('date_operation', 'desc')
                        ->value('capital_after') ?? $trans->amount;

                    $montantInteret = $valeurPortefeuille - $capitalAvant;

                    // 3. Insertion de la ligne de capitalisation manquante
                    DB::table('financial_movements')->insert([
                        'transaction_id' => $trans->id,
                        'date_operation' => $cursor->toDateTimeString(),
                        'type' => 'capitalisation_interets',
                        'amount'         => $montantInteret,
                        'capital_before' => $capitalAvant,
                        'capital_after'  => $valeurPortefeuille,
                        'comments'       => "Capitalisation automatique - Anniversaire " . $startDate->diffInYears($cursor) . " an(s)",
                        'created_at'     => now(),
                        'updated_at'     => now()
                    ]);

                    $syncReport[] = "Transaction {$trans->id} : Anniversaire du {$dateAnniversaire} synchronisé.";
                }

                $cursor->addYear(); // Passage à l'année suivante
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => count($syncReport) . " mouvements synchronisés.",
            'details' => $syncReport
        ]);
    } */

    public function syncAnniversaryMovements()
    {
        // On ne récupère que les produits PMG actifs
        $transactions = Transaction::where('status', 'Succès')
            ->whereHas('product', function ($q) {
                $q->where('products_category_id', 2);
            })->get();

        $today = Carbon::now();

        foreach ($transactions as $trans) {
            // Point de départ : 1 an après la validation
            $anniversary = Carbon::parse($trans->date_validation)->addYear();

            while ($anniversary->lte($today)) {
                $formattedDate = $anniversary->toDateString();

                // ✅ Sécurité : Normaliser l'anniversaire à minuit pour la comparaison
                $anniversaryMidnight = $anniversary->copy()->startOfDay();

                $exists = DB::table('financial_movements')
                    ->where('transaction_id', $trans->id)
                    ->where('type', 'capitalisation_interets')
                    // On vérifie sur la date uniquement
                    ->whereDate('date_operation', $formattedDate)
                    ->exists();

                if (!$exists) {
                    try {
                        // ✅ Utilise votre nouvelle fonction de calcul hybride
                        $valeurPortefeuille = $this->calculatePMGValorization($trans, $anniversaryMidnight);

                        // Récupération du capital juste avant cet anniversaire
                        $capitalAvant = DB::table('financial_movements')
                            ->where('transaction_id', $trans->id)
                            ->where('date_operation', '<', $anniversaryMidnight)
                            ->orderBy('date_operation', 'desc')
                            ->value('capital_after') ?? (float)$trans->amount;

                        $interetAdd = $valeurPortefeuille - $capitalAvant;

                        // On ne crée un mouvement que si l'intérêt est positif
                        if ($interetAdd > 0) {
                            DB::table('financial_movements')->insert([
                                'transaction_id' => $trans->id,
                                'user_id'        => $trans->user_id,
                                'date_operation' => $anniversaryMidnight->toDateTimeString(),
                                'type'           => 'capitalisation_interets',
                                'amount'         => $interetAdd,
                                'capital_before' => $capitalAvant,
                                'capital_after'  => $valeurPortefeuille,
                                'comments'       => 'Capitalisation automatique anniversaire ' . $anniversary->diffInYears(Carbon::parse($trans->date_validation)) . ' an(s)',
                                'created_at'     => now(),
                                'updated_at'     => now()
                            ]);

                            // ✅ Optionnel : Mettre à jour le montant principal de la transaction pour le suivi rapide
                            $trans->update(['amount' => $valeurPortefeuille]);
                        }

                        Log::info("SYNC OK : Trans {$trans->id} capitalisée pour le {$formattedDate}");
                    } catch (\Exception $e) {
                        Log::error("SYNC FAIL Trans {$trans->id} : " . $e->getMessage());
                    }
                }
                $anniversary->addYear(); // Passer à l'anniversaire suivant (si contrat de 2 ans ou plus)
            }
        }
    }
}
