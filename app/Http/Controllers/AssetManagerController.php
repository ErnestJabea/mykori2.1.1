<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionSupplementaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\AssetValue;
use Carbon\Carbon;

class AssetManagerController extends Controller
{
    public function createCustomer()
    {
        return view('front-end.asset-manager.create-customer');
    }

    public function storeCustomer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'genre' => 'required|integer|in:0,1,2',
            'localisation' => 'required|string|max:255',
            'bp' => 'nullable|string|max:255',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make('12345678'),
            'genre' => $request->genre,
            'localisation' => $request->localisation,
            'bp' => $request->bp,
            'role_id' => 2, // Client
        ]);

        return redirect()->route('customer')->with('success', 'Client créé avec succès. Le mot de passe par défaut est 12345678.');
    }

    //
    protected $portfolioService;
    protected $productControllerImport;

    public function __construct(ProductController $productController)
    {
        $this->productControllerImport = $productController;
    }


    public function index()
    {

        $customers = User::where('2');

        return view('front-end.asset-manager')->with('customers', $customers);
    }


    public function customers()
    {
        $customers = User::where('role_id', '2')->orderBy('created_at', 'desc')->get();

        // Ajouter le nombre de produits pour chaque utilisateur
        foreach ($customers as $customer) {
            // Compter le nombre de produits distincts dans la table transactions
            $countFromTransactions = Transaction::where('user_id', $customer->id)
                ->distinct('product_id')
                ->count('product_id');

            // Compter le nombre de produits distincts dans la table transaction_supplementaire
            $countFromSupplementaryTransactions = TransactionSupplementaire::where('user_id', $customer->id)
                ->distinct('product_id')
                ->count('product_id');

            // Calculer le total des produits distincts pour cet utilisateur
            $totalProductCount = $countFromTransactions + $countFromSupplementaryTransactions;

            // Ajouter le nombre total de produits au modèle User
            $customer->product_count = $totalProductCount;
        }


        return view('front-end.customer')->with('customers', $customers);
    }


    public function customersDetail(Request $request)
    {
        $customer = User::findOrFail($request->customer);
        
        $product_nb = $this->countUserProducts($customer->id);
        
        // Utiliser la méthode centralisée pour obtenir tous les produits avec leurs gains/valorisations
        $productsWithGains = $this->productControllerImport->getProductsWithGainsUser($customer->id);
        
        $portefeuille_fcp = 0;
        $portefeuille_pmg = 0;
        $total_interets = 0;

        foreach ($productsWithGains as $p) {
            if ($p['type_product'] == 1) { // FCP
                $portefeuille_fcp += (float)$p['portfolio_valeur'];
                $total_interets += (float)($p['total_gains_fcp'] ?? 0);
            } else { // PMG
                $portefeuille_pmg += (float)$p['portfolio_valeur'];
                $total_interets += (float)($p['interets_generes'] ?? 0);
            }
        }

        $portefeuille_total = $portefeuille_fcp + $portefeuille_pmg;

        // On récupère aussi tous les produits pour la modale d'ajout
        $products = Product::all()->map(function($p) {
            $latestVl = \App\Models\AssetValue::where('product_id', $p->id)
                ->orderBy('created_at', 'desc')
                ->first();
            $p->recent_vl = $latestVl ? (float)$latestVl->vl : (float)$p->vl;
            return $p;
        });
        
        $categories = \App\Models\ProductsCategory::all();

        return view('front-end.customer-detail', compact(
            'customer', 
            'productsWithGains', 
            'product_nb',
            'portefeuille_total',
            'portefeuille_pmg',
            'portefeuille_fcp',
            'total_interets',
            'products',
            'categories'
        ));
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


    public function getProductsWithGains($user_id)
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
                        $totalGain += $this->productControllerImport->calculateFCPGain(floor($vl_actuel), $transaction);
                        $recentGain[] = $this->productControllerImport->calculateFCPGain(floor($vl_actuel), $transaction);
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
                        $vl_antepenultimate = $assetValues->count() == 2 ? floor($assetValues->last()->vl) : floor($transaction->vl_buy);

                        //dd("Valeur liquidative actuelle = ".$vl_actuel,"Valeur liquidative précédente = ".$vl_antepenultimate, "Nombre de parts = ".$transaction->nb_part,"Différence Vl précédente Vl actuelle = ".$vl_actuel - $vl_antepenultimate, "Gain de la semaine (nb part * (vl_prec - vl_actuelle)) = ".$transaction->nb_part * ($vl_actuel - $vl_antepenultimate));
                        // Calcul de la valorisation du portefeuille
                        $valorisationPortefeuille = $this->productControllerImport->calculateFCPGain(floor($vl_actuel), $transaction);

                        // Calcul du gain entre l'avant-dernière et la dernière VL

                        $gain = $transaction->nb_part * (floor($vl_actuel) - floor($vl_antepenultimate));
                        $gainWeekFcp += $gain;
                        $gainWeekTab[] = $gainWeekFcp;

                        //dd($gainWeekFcp);

                        // Calcul du cumul des gains/pertes depuis la souscription
                        $cumulGains += $valorisationPortefeuille - ($transaction->nb_part * $transaction->vl_buy);

                        // Ajouter le gain cumulé au total
                        $totalGainFcp += $this->productControllerImport->calculateFCPGain(floor($vl_actuel), $transaction);
                    }




                } else {
                    // Calculer le gain pour les produits PMG
                    $vl_actuel = $transaction->vl_buy;


                    $date_echeance_exploded = explode(" ", $transaction->date_echeance)[0];
                    $date_validation_exploded = explode(" ", $transaction->date_validation)[0];

                    $gainMonth = $this->productControllerImport->calculatePMGMonthlyGain(
                        $transaction->amount,
                        $transaction->vl_buy,
                        $date_validation_exploded,
                        $date_echeance_exploded,
                        $this->productControllerImport->getTodaysDate()
                    )/*['valo_pf']*/ ;

                    //dd($gainMonth);

                    $CummulgainMonth = $this->productControllerImport->calculatePMGMonthlyGain(
                        $transaction->amount,
                        $transaction->vl_buy,
                        $date_validation_exploded,
                        $date_echeance_exploded,
                        $this->productControllerImport->getTodaysDate()
                    )/*['cummul_interet']*/ ;
                    // dd($gainMonth/360);

                    $recentGain[] = $this->productControllerImport->calculatePMGMonthlyGain(
                        $transaction->amount,
                        $transaction->vl_buy,
                        $date_validation_exploded,
                        $date_echeance_exploded,
                        $this->productControllerImport->getTodaysDate()
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

            $stat_val = $this->productControllerImport->CalculDateEcheance($transaction->date_validation, $transaction->duree);

            $nbMoisJour = $this->productControllerImport->calculateMonthsAndDaysBetweenDates($transaction->date_validation, $transaction->date_echeance);
            //dd($nbMoisJour);
            //dd($totalGainFcp + $transaction->amount);
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
                'gain_mensuel' => $this->productControllerImport->gainMonthPmg($transaction->amount, $transaction->vl_buy),
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

            //dd($result);

            //$user->gain = $totalGain;
            //$user->gain_pmg = $totalGain;

        }

        //dd($result);

        return $result;
    }


}
