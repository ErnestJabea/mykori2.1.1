<?php

namespace App\Http\Controllers;

use App\AssetValue;
use App\Models\User;
use App\Transaction;
use App\Product;
use App\TransactionSupplementaire;
use App\Services\PortofolioService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Services\GainCalculationService;

class UserController extends Controller
{

    protected $portfolioService;
    protected $productControllerImport;

    public function __construct(PortofolioService $portfolioService, ProductController $productController)
    {
        $this->portfolioService = $portfolioService;
        $this->productControllerImport = $productController;
    }

    public function show()
    {

        $products = Product::where('products_category_id', 1)->where('nb_action', '>', 0)->get();
        $transactions = Transaction::where("user_id", Auth::user()->id)->orderBy("created_at", 'desc')->take(10)->get();

        $this->updateUserGainsUp();


        // Récupérer l'utilisateur authentifié
        $user_ = Auth::user();

        // Vérifier si l'utilisateur est authentifié
        if (!$user_) {
            // Rediriger vers la page de connexion ou gérer l'accès non autorisé
            return redirect()->route('login')->with('error', 'Veuillez vous connecter pour accéder à cette page.');
        }

        // Rechercher l'utilisateur authentifié dans la base de données
        $user_ = User::find($user_->id);

        // Vérifier si l'utilisateur existe dans la base de données
        if (!$user_) {
            // Gérer l'accès non autorisé ou l'erreur utilisateur introuvable
            abort(403, 'Utilisateur introuvable');
        }

        // Calculer le portefeuille et le gain
            $portfolio = $this->portfolioService->calculatePortfolio($user_);
            $gain_user_fcp = $this->getTotalGainsFCP($user_);
            $gain_user_pmg = $this->getTotalGainsPMG($user_);
            $result_gain = $this->productControllerImport->getProductsWithGainsPieChart();
            //dd($result_gain);
            $gain_user = $gain_user_fcp + $gain_user_pmg;
            //$gain_user = $this->getGainTotal($user_);
            $resultatsAvecPourcentage2 = $this->getGainData();
            $chartData = $this->getChartData();



            //dd($chartData);



        // Passer les données à la vue et retourner
        return view('front-end.dashboard',
            compact('portfolio','result_gain',
                'gain_user', 'user_', 'products', 'transactions',
        'resultatsAvecPourcentage2', 'chartData'));
    }

    /* ************************************************** */
    /*                                                    */
    /*         Gains total FCP                            */
    /*                                                    */
    /*                                                    */
    /* ************************************************** */
    private function getTotalGainsFCP(User $user)
    {
        // Récupérer tous les produits
        $products = Product::all();

        $totalGains = 0; // Variable pour stocker le gain total

        foreach ($products as $product) {
            // Récupérer les transactions associées à ce produit avec un état "Succès"
            $transactions = Transaction::where('user_id', $user->id)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            $additionalTransactions = TransactionSupplementaire::where('user_id', $user->id)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            // Fusionner les transactions principales et supplémentaires
            $allTransactions = $transactions->merge($additionalTransactions);

            if ($allTransactions->isEmpty()) {
                continue; // Passer au produit suivant si aucune transaction n'existe
            }

            foreach ($allTransactions as $transaction) {
                // Récupérer les valeurs liquidatives des 4 dernières semaines pour le produit
                $validationDate = Carbon::parse($transaction->date_validation);
                $assetValues = AssetValue::where('created_at', '>=', $validationDate)
                    ->where('product_id', $transaction->product_id)
                    ->orderBy('created_at', 'desc')
                    ->take(4)
                    ->get();

                //dd($this->calculatePMGGain2(5, $transaction));

                // Si aucune valeur liquidative n'est trouvée, définir les gains à zéro
                if ($assetValues->isEmpty()) {
                    continue;
                }


                foreach ($assetValues as $assetValue) {
                    $gain = $this->calculateFCPGain($assetValue->vl, $transaction);
                    // Ajouter le gain au total général
                    $totalGains += $gain;
                }

            }
        }

        return $totalGains; // Retourne le gain total de tous les produits
    }

    /* ************************************************** */
    /*                                                    */
    /*         Gains total PMG                            */
    /*                                                    */
    /*                                                    */
    /* ************************************************** */



    private function getTotalGainsPMG(User $user)
    {
        // Récupérer tous les produits
        $products = Product::where('products_category_id', 2)->get();

        $totalGains = 0; // Variable pour stocker le gain total

        foreach ($products as $product) {
            // Récupérer les transactions associées à ce produit avec un état "Succès"
            $transactions = Transaction::where('user_id', $user->id)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            $additionalTransactions = TransactionSupplementaire::where('user_id', $user->id)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            // Fusionner les transactions principales et supplémentaires
            $allTransactions = $transactions->merge($additionalTransactions);

            if ($allTransactions->isEmpty()) {
                continue; // Passer au produit suivant si aucune transaction n'existe
            }

            foreach ($allTransactions as $transaction) {

                $totalGains += $this->calculatePMGGain2($transaction->vl_buy, $transaction);

            }

        }


        return $totalGains; // Retourne le gain total de tous les produits
    }




    public function getGainData()
    {
        // Récupérer les transactions réussies de l'utilisateur
        $transactions = Transaction::select('product_id', DB::raw('SUM(amount) as total_amount'))
            ->where('user_id', Auth::user()->id)
            ->where('status', 'Succès')
            ->groupBy('product_id')
            ->get();

        // Récupérer les sous-transactions réussies de l'utilisateur
        $subTransactions = TransactionSupplementaire::select('product_id', DB::raw('SUM(amount) as total_amount'))
            ->where('user_id', Auth::user()->id)
            ->where('status', 'Succès')
            ->groupBy('product_id')
            ->get();

        // Fusionner les deux collections de résultats
        $mergedTransactions = $transactions->merge($subTransactions);

        // Regrouper les résultats par ID de produit et sommer les gains
        $groupedTransactions = $mergedTransactions->groupBy('product_id')->map(function ($items) {
            return [
                'product_id' => $items->first()->product_id,
                'total_amount' => $items->sum('total_amount')
            ];
        });

        // Calculer le total des gains de tous les produits
        $totalGains = $groupedTransactions->sum('total_amount');

        //dd($totalGains);

        // Calculer le pourcentage des gains pour chaque produit
        $resultatsAvecPourcentage = [];

        // Calculer le pourcentage des gains pour chaque produit
        foreach ($groupedTransactions as $transaction) {
            $pourcentageGainsProduit = ($transaction['total_amount'] / $totalGains) * 100;

            // Ajouter les données du produit avec le pourcentage de gains calculé au tableau
            $resultatsAvecPourcentage[] = [
                'id_produit' => $transaction['product_id'],
                'total_amount' => $transaction['total_amount'],
                'pourcentage_gains' => $pourcentageGainsProduit
            ];
        }

        //dd($resultatsAvecPourcentage);
        return $resultatsAvecPourcentage;
    }







    private function getChartData()
    {
        // Récupérer tous les produits
        $products = Product::all();

        $chartData = [];

        foreach ($products as $product) {
            // Récupérer les transactions associées à ce produit avec un état "Succès"
            $transactions = Transaction::where('user_id', Auth::user()->id)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            $additionalTransactions = TransactionSupplementaire::where('user_id', Auth::user()->id)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            // Fusionner les transactions principales et supplémentaires
            $allTransactions = $transactions->merge($additionalTransactions);

            if ($allTransactions->isEmpty()) {
                continue; // Passer au produit suivant si aucune transaction n'existe
            }

            $weeklyGains = [];
            $weekLabels = [];

            foreach ($allTransactions as $transaction) {
                // Récupérer les valeurs liquidatives des 4 dernières semaines pour le produit
                // Convertir date_validation en instance de Carbon
                $validationDate = Carbon::parse($transaction->date_validation);
                $assetValues = AssetValue::where('created_at', '>=', $validationDate)
                    ->where('product_id', $transaction->product_id)
                    ->orderBy('created_at', 'desc')
                    ->take(4)
                    ->get();

                // Si aucune valeur liquidative n'est trouvée, définir les gains à zéro
                if ($assetValues->isEmpty()) {
                    $weeklyGains = array_pad($weeklyGains, 4, 0); // Ajouter des zéros pour les 4 dernières semaines
                    continue;
                }

                foreach ($assetValues as $index => $assetValue) {
                    if (empty($weekLabels)) {
                        $weekLabels[] = 'Semaine ' . ($index + 1);
                    }

                    $gain = ($product->products_category_id == 1)
                        ? $this->calculateFCPGain($assetValue->vl, $transaction)
                        : $this->calculatePMGMonthlyGain($transaction->vl_buy, $transaction);
                    $weeklyGains[] = $gain;
                }
            }

            // Ajouter les gains hebdomadaires pour ce produit aux données du graphique
            $chartData[] = [
                'name' => $product->title,
                'data' => array_pad($weeklyGains, 4, 0) // Assurez-vous d'avoir 4 valeurs, complétez avec des zéros si nécessaire
            ];
            return [
                'weekLabels' => array_pad($weekLabels, 4, 'Semaine ' . (count($weekLabels) + 1)),
                'chartData' => $chartData
            ];
        }

    }









    private function calculateFCPGain($vl_actuel, $transaction)
    {
        $totalInvested = $transaction->amount;
        $montantTotal = $transaction->nb_part * $vl_actuel;
        $gain = max(0, round($montantTotal - $totalInvested));
        return $gain;
    }


    //Calcul du gain des PMG par semaine
    private function calculatePMGGain($vl_actuel, $transaction)
    {

        $totalInvested = $transaction->amount;


        $currentDate = Carbon::now();


        $daysDifference = Carbon::parse($transaction->date_validation)->diffInDays($currentDate);
        $weeklyRate = ($vl_actuel / 100) / 52;


        // Gain annuel
        $annualGain = $$totalInvested * pow(1 + $weeklyRate, $daysDifference);

        // Gain hebdomadaire


        $gain = max(0, $annualGain - $totalInvested);


        return $gain;
    }

    //Calcul du gain des PMG par an
    private function calculatePMGGain2($vl_actuel, $transaction)
    {
        $totalInvested = $transaction->amount;
        $currentDate = Carbon::now();

        // Calculer la différence en jours entre la date de validation et la date actuelle
        $daysDifference = Carbon::parse($transaction->date_validation)->diffInDays($currentDate);

        // Calculer le taux journalier
        $dailyRate = (float)$vl_actuel / 100 / 360;

        // Calculer le gain total
        $totalGain = $totalInvested * pow(1 + $dailyRate, $daysDifference);

        // Calcule du gain net
        $gain = round(max(0, $totalGain - $totalInvested));

        return $gain;
    }




    public function updateUserGainsUp()
    {
        $gainService = new GainCalculationService();
        $gainService->getProductsWithGains();

        return response()->json(['message' => 'Gains updated successfully']);
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
            $vl_actuel = null;
            $gainMonth = 0;
            $gainWeek = 0; // Réinitialisation pour éviter l'accumulation incorrecte
            $currentDate = Carbon::now();
            $gainMensuel = 0;


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
                    }

                    // Calculer la différence hebdomadaire si nécessaire
                    $assetValues = AssetValue::where('product_id', $transaction->product_id)
                        ->where('created_at', '>=', $transaction->date_validation)
                        ->orderBy('created_at', 'desc')
                        ->take(2)
                        ->get();


                    if ($assetValues->count() == 1) {
                        $latestValue = $transaction->vl_buy;
                        $secondLatestValue = $assetValues->last()->vl;
                        $difference = $secondLatestValue - $latestValue;
                        $gainWeek += $difference;
                    }

                    if ($assetValues->count() >= 1) {
                        $latestValue = $assetValues->first()->vl;
                        $secondLatestValue = $assetValues->last()->vl;
                        $difference = $secondLatestValue - $latestValue ;
                        $gainWeek += $difference;
                    }


                    //$user->gain = $totalGain;
                } else {
                    // Calculer le gain pour les produits PMG
                    $vl_actuel = $transaction->vl_buy;
                    $gainMonth += $this->calculatePMGMonthlyGain(
                        $transaction->amount,
                        $transaction->vl_buy,
                        $currentDate->diffInMonths($transaction->date_validation),
                        $product->duree
                    );
                    
                    $valo_pf = $this->calculatePMGMonthlyGain(
                        $transaction->amount,
                        $transaction->vl_buy,
                        $transaction->date_echeance,
                        $transaction->date_validation
                    )['valo_pf'];

                    $CummulgainMonth = $this->calculatePMGMonthlyGain(
                        $transaction->amount,
                        $transaction->vl_buy,
                        $transaction->date_echeance,
                        $transaction->date_validation
                    )['cummul_interet'];
                    $totalGain = $gainMonth + $transaction->amount ;


                    if($currentDate->diffInMonths($transaction->date_validation)!=0){
                        $gainMensuel = $gainMonth / $currentDate->diffInMonths($transaction->date_validation);
                    }else{
                        $gainMensuel = 0;
                    }


                    //dd($currentDate->diffInMonths($transaction->date_validation));
                    // Calculer le gain mensuel pour les produits PMG

                   //$user->gain_pmg = $gainMonth;

                }
            }

            // Ajouter les résultats agrégés au tableau $result
            $result[] = [
                'product_name' => $product->title,
                'gain' => $totalGain,
                'vl_actuel' => $vl_actuel,
                'duree' => $product->duree,
                'nb_part' => $transaction -> nb_part,
                'montant_transaction' => $transaction -> amount,
                'type_product' => $product->products_category_id,
                'vl_achat' => isset($transaction) ? $transaction->vl_buy : null, // Assurez-vous que vl_buy est correctement défini
                'gain_semaine' => $gainWeek,
                'gain_month' => $gainMonth,
                'gain_mensuel' => $gainMensuel,
                'slug' => $product->slug,
                'souscription'=> $transaction->date_validation
            ];

        }
            //$user->save();


        return $result;
    }




    private function calculatePMGMonthlyGain($initialAmount, $interestRate, $specificDate, $transactionDate)
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

                error_log("la valorisation jour  ".$i.": ".$interest);

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


    public function calculatePMGGain_($vl_buy, $transaction)
    {
        $totalInvested = $transaction->amount;
        $currentDate = Carbon::now();
        $daysDifference = Carbon::parse($transaction->date_validation)->diffInDays($currentDate) - 1;
        $rate = ($vl_buy / 100) / 360; // Supposons que vl_buy est le taux d'intérêt annuel
        $rate_invested =$totalInvested * $rate;
        //dd($rate_invested_without_days = $totalInvested + $rate_invested);
        return $totalInvested + $rate_invested;

    }



}
