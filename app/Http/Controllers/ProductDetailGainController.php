<?php

namespace App\Http\Controllers;

use App\Services\GainCalculationService;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Http\Controllers\ProductController;
use App\Models\Transaction;
use App\Models\TransactionSupplementaire;
use App\Models\AssetValue;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProductDetailGainController extends Controller {



    public function getProductWithGains($slug)
    {
        $product = Product::find($slug);

        // Récupérer toutes les transactions principales pour le produit
        $transactions = Transaction::where('user_id', Auth::user()->id)
            ->where('status', 'Succès')
            ->where('product_id', $slug)
            ->get();


        // Récupérer toutes les transactions supplémentaires pour le produit
        $additionalTransactions = TransactionSupplementaire::where('user_id', Auth::user()->id)
            ->where('status', 'Succès')
            ->where('product_id', $slug)
            ->get();

        // Fusionner les transactions principales et supplémentaires
        $allTransactions = $transactions->merge($additionalTransactions);
;

        if ($allTransactions->isEmpty()) {
            return abort(404, 'Aucune Transaction trouvée');
        }

        $totalGain = 0;
        $vl_actuel = null;
        $gainMonth = 0;
        $gainWeek = 0;
        $currentDate = Carbon::now();
        $recentGains = [];
        $recentGain = [];

        foreach ($allTransactions as $transaction) {
            if ($product->products_category_id == 1) {
                // Calculer les valeurs actuelles les plus récentes pour les produits FCP
                $recentAssetValues = AssetValue::where('product_id', $transaction->product_id)
                    ->where('date_vl', '>=', $transaction->date_validation)
                    ->orderBy('date_vl', 'desc')
                    ->take(8)
                    ->get();

                //dd($recentAssetValues);
                if ($recentAssetValues->isNotEmpty()) {
                    $recentAssetValues = $recentAssetValues->sortBy('date_vl');
                    $vl_actuel = $recentAssetValues->last()->vl;
                    foreach ($recentAssetValues as $assetValue) {
                        $recentGain = $this->calculateFCPGain($assetValue->vl, $transaction);
                        $recentGains[] = [
                            'gain' => $recentGain,
                            'date' => $assetValue->date_vl
                        ];
                        // On ne cumule que le gain le plus récent pour totalGain ici ? 
                        // En fait la boucle calcule les gains historiques. 
                        // L'ancien code ajoutait à totalGain dans la boucle, ce qui semble faux s'il veut le gain TOTAL actuel.
                    }
                    $totalGain = $this->calculateFCPGain($vl_actuel, $transaction);
                }

                // Calculer la différence hebdomadaire si nécessaire
                if ($recentAssetValues->count() >= 2) {
                    $latestValue = $recentAssetValues->first()->vl;
                    $secondLatestValue = $recentAssetValues->last()->vl;
                    $difference = $latestValue - $secondLatestValue;
                    $gainWeek += $difference;
                }
            } else {
                // Calculer le gain pour les produits PMG
                $vl_actuel = $transaction->vl_buy;
                $recentGain = $this->calculatePMGGain($vl_actuel, $transaction);

                // Calculer le gain mensuel pour les produits PMG
                $gainMonth += $this->calculatePMGMonthlyGain(
                    $transaction->amount,
                    $transaction->vl_buy,
                    $currentDate->diffInMonths($transaction->date_validation),
                    $transaction->duree
                );
                $recentGains[] = $gainMonth;
            }
        }

        // Préparer le résultat pour un produit spécifique
        $productWithGains = [
            'product_id' => $product->id,
            'product_name' => $product->title,
            'gain' => $totalGain,
            'vl_actuel' => (float)$vl_actuel,
            'duree' => $transaction->duree,
            'nb_part' => $transaction->nb_part,
            'type_product' => $product->products_category_id,
            'date_souscription' => $transaction->date_validation,
            'vl_achat' => isset($transaction) ? (float)$transaction->vl_buy : null,
            'gain_semaine' => $gainWeek,
            'montant_transaction' => $transaction->amount,
            'gain_month' => $gainMonth,
            'slug' => $product->slug,
            'recent_gains' => $recentGains,
        ];

        //dd($productWithGains);

        return $productWithGains;
    }


    private function calculatePMGMonthlyGain($initialAmount, $interestRate, $months, $maxMonths)
    {
        // Calcul du taux d'intérêt mensuel à partir du taux annuel en pourcentage
        $monthlyRate = ($interestRate / 100);
        $taux_mensuel = $monthlyRate / 12;

        // Initialisation du portefeuille avec le montant initial investi
        $portfolio = $initialAmount;

        // Boucle pour chaque mois jusqu'à la limite maxMonths
        for ($i = 0; $i < min($months, $maxMonths); $i++) {
            // Calcul de l'intérêt pour le mois en cours
            $interest = $portfolio * $taux_mensuel;
            // Ajout de l'intérêt au portefeuille (intérêt composé)
            $portfolio += $interest;
        }

        // Calcul du gain total
        $gain = $portfolio - $initialAmount;

        // Retour du gain arrondi à deux décimales
        return round($gain, 2);
    }




    public function calculateFCPGain($vl_actuel, $transaction)
    {
        $totalInvested = $transaction->amount;
        $montantTotal = $transaction->nb_part * $vl_actuel;
        $gain = max(0, round($montantTotal - $totalInvested));
        return $gain;
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

    public function showProductGain($slug)
    {
        $product = Product::where('slug', $slug)->first();
        $productWithGains = $this->getProductWithGains($product->id);
        //dd($productWithGains);
        return view('front-end.product-detail-gain', ['product' => $productWithGains]);
    }


}