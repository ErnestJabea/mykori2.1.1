<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionSupplementaire;
use App\Models\CustomerPortfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\AssetValue;
use Carbon\Carbon;

class AssetManagerController extends Controller
{
    public function createCustomer(Request $request, $portfolio_id = null)
    {
        $portfolioToEdit = $portfolio_id ? CustomerPortfolio::with('user')->find($portfolio_id) : null;
        $search = $request->input('search');

        // On prépare la requête avec les dossiers
        $query = CustomerPortfolio::with('user')->orderBy('created_at', 'desc');

        // FILTRE DE RECHERCHE
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'LIKE', "%{$search}%")
                  ->orWhere('type', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function ($qu) use ($search) {
                      $qu->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                  });
            });
        }

        // PAGINATION (10 par page)
        $portfolios = $query->paginate(10)->appends(['search' => $search]);
        
        if ($request->ajax()) {
            return view('front-end.partials.portfolios-table', compact('portfolios', 'search'));
        }

        return view('front-end.asset-manager.create-customer', [
            'portfolios' => $portfolios,
            'portfolioToEdit' => $portfolioToEdit,
            'customerToEdit' => $portfolioToEdit ? $portfolioToEdit->user : null,
            'search' => $search
        ]);
    }

    public function storeCustomer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255', // Plus de contrainte unique sur l'email car un user peut avoir 2 dossiers
            'type' => 'required|in:PMG,FCP',
            'genre' => 'required|integer|in:0,1,2',
            'localisation' => 'required|string|max:255',
            'bp' => 'nullable|string|max:255',
        ]);

        // 1. Recherche ou Création de l'Utilisateur Maître
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => \Illuminate\Support\Facades\Hash::make('12345678'),
                'genre' => $request->genre,
                'localisation' => $request->localisation,
                'bp' => $request->bp,
                'role_id' => 2, // Client
            ]);
            $isNewUser = true;
        } else {
            // Optionnel: Mettre à jour les infos si l'utilisateur existait déjà
            $user->update([
                'name' => $request->name,
                'genre' => $request->genre,
                'localisation' => $request->localisation,
                'bp' => $request->bp,
            ]);
            $isNewUser = false;
        }

        // 2. Vérification Anti-Doublon de Dossier de même type
        $type = $request->type;
        $portfolioExists = CustomerPortfolio::where('user_id', $user->id)->where('type', $type)->exists();
        
        if ($portfolioExists) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Ce client ($request->email) possède déjà un dossier de type $type. Si vous voulez le modifier, utilisez le bouton d'édition dans la liste ci-dessous.");
        }

        // 3. Génération automatique de la Référence
        $lastRef = CustomerPortfolio::where('type', $type)->orderBy('reference', 'desc')->first();
        
        if ($lastRef) {
            $number = (int) preg_replace('/[^0-9]/', '', $lastRef->reference);
            $newRef = $type . str_pad($number + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newRef = $type . '0001';
        }

        // 4. Création du Dossier (Portfolio)
        CustomerPortfolio::create([
            'user_id' => $user->id,
            'type' => $type,
            'reference' => $newRef,
            'status' => 'active'
        ]);

        $msg = $isNewUser 
            ? "Nouveau client " . $user->name . " créé avec succès (Dossier $newRef)." 
            : "Client " . $user->name . " reconnu. Un nouveau dossier $type ($newRef) a été rattaché à son compte.";

        return redirect()->route('asset-manager.create-customer')->with('success', $msg);
    }

    public function updateCustomer(Request $request, CustomerPortfolio $portfolio)
    {
        $customer = $portfolio->user;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('users')->ignore($customer->id),
            ],
            'genre' => 'required|integer|in:0,1,2',
            'localisation' => 'required|string|max:255',
            'bp' => 'nullable|string|max:255',
            'reference' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('customer_portfolios')->ignore($portfolio->id),
            ],
            'type' => 'required|in:PMG,FCP',
        ]);

        $customer->update([
            'name' => $request->name,
            'email' => $request->email,
            'genre' => $request->genre,
            'localisation' => $request->localisation,
            'bp' => $request->bp,
        ]);

        $portfolio->update([
            'reference' => $request->reference,
            'type' => $request->type
        ]);

        return redirect()->route('asset-manager.create-customer')->with('success', 'Dossier ' . $portfolio->reference . ' mis à jour avec succès.');
    }

    public function deletePortfolio(CustomerPortfolio $portfolio)
    {
        $ref = $portfolio->reference;
        $user = $portfolio->user;
        $type = $portfolio->type;

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            if ($type === 'PMG') {
                // 1. On récupère les IDs des transactions PMG du client
                $pmgTxIds = Transaction::where('user_id', $user->id)
                    ->whereHas('product', function($q){ $q->where('products_category_id', 2); })
                    ->pluck('id');
                
                // 2. On supprime les mouvements financiers liés
                \App\Models\FinancialMovement::whereIn('transaction_id', $pmgTxIds)->delete();
                
                // 3. On supprime les transactions
                Transaction::whereIn('id', $pmgTxIds)->delete();
            } else {
                // 1. Suppression des mouvements FCP spécifiques à l'utilisateur
                \Illuminate\Support\Facades\DB::table('fcp_movements')->where('user_id', $user->id)->delete();
                
                // 2. Suppression des transactions FCP
                Transaction::where('user_id', $user->id)
                    ->whereHas('product', function($q){ $q->where('products_category_id', 1); })
                    ->delete();
                    
                TransactionSupplementaire::where('user_id', $user->id)
                    ->whereHas('product', function($q){ $q->where('products_category_id', 1); })
                    ->delete();
            }

            // 3. Log de l'action dans la table d'audit
            \App\Models\UserActivityLog::log(
                "SUPPRESSION_DOSSIER",
                null,
                "L'Asset Manager " . \Illuminate\Support\Facades\Auth::user()->name . " a supprimé le dossier $ref ($type) du client " . ($user->name ?? 'Inconnu') . ". Tous les produits et mouvements rattachés ont été effacés."
            );

            // 4. Suppression du dossier lui-même
            $portfolio->delete();

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('asset-manager.create-customer')->with('success', "Le dossier $ref et tous ses investissements associés ont été supprimés definitivement.");
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollback();
            return redirect()->back()->with('error', "Une erreur est survenue lors de la suppression : " . $e->getMessage());
        }
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

        $customers = User::where('role_id', '2')->get();

        return view('front-end.asset-manager')->with('customers', $customers);
    }


    public function customers(Request $request)
    {
        $search = $request->input('search');
        $categoryFilter = $request->input('category', 'all'); // 'all', '1' (FCP), '2' (PMG)
        $sortBy = $request->input('sort_by', 'name');
        $order = $request->input('order', 'asc');

        $query = User::where('role_id', '2');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Filtrage par Portefeuilles (Nouveau système)
        if ($categoryFilter == '1') { // FCP
            $query->whereHas('portfolios', function($q) { $q->where('type', 'FCP'); });
        } elseif ($categoryFilter == '2') { // PMG
            $query->whereHas('portfolios', function($q) { $q->where('type', 'PMG'); });
        }

        $allUsers = $query->get();
        $processedUsers = collect();
        
        $globalTotalInvesti = 0;
        $globalTotalInterets = 0;
        $activeClientsCount = 0;
        $inactiveClientsCount = 0;

        foreach ($allUsers as $user) {
            $stats = $this->productControllerImport->getUserStats($user->id);
            $user->total_capital = $stats['total_invested'] ?? 0;
            $user->total_interets = $stats['total_gains'] ?? 0;
            $user->portefeuille_total = $stats['total_portfolio'] ?? 0;
            $user->product_count = $this->countUserProducts($user->id);
            
            if ($user->product_count > 0) {
                $activeClientsCount++;
                $globalTotalInvesti += $user->total_capital;
                $globalTotalInterets += $user->total_interets;
            } else {
                $inactiveClientsCount++;
            }

            // On ajoute systématiquement l'utilisateur à la liste finale
            $processedUsers->push($user);
        }

        // Tri
        if ($order == 'desc') {
            $processedUsers = $processedUsers->sortByDesc($sortBy);
        } else {
            $processedUsers = $processedUsers->sortBy($sortBy);
        }

        // Pagination
        $page = $request->input('page', 1);
        $perPage = 10;
        $pagedData = $processedUsers->slice(($page - 1) * $perPage, $perPage)->values();
        
        $customers = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedData,
            $processedUsers->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        if ($request->ajax()) {
            return view('front-end.partials.customer-table', compact(
                'customers', 'search', 'categoryFilter', 'sortBy', 'order',
                'globalTotalInvesti', 'globalTotalInterets', 'activeClientsCount', 'inactiveClientsCount'
            ));
        }

        return view('front-end.customer', compact(
            'customers', 'search', 'categoryFilter', 'sortBy', 'order',
            'globalTotalInvesti', 'globalTotalInterets', 'activeClientsCount', 'inactiveClientsCount'
        ));
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
        $total_plus_value_fcp = 0;

        foreach ($productsWithGains as $p) {
            if ($p['type_product'] == 1) { // FCP
                $portefeuille_fcp += (float)$p['portfolio_valeur'];
                $total_interets += (float)($p['total_gains_fcp'] ?? 0);
                $total_plus_value_fcp += (float)($p['total_gains_fcp'] ?? 0);
            } else { // PMG
                $portefeuille_pmg += (float)$p['portfolio_valeur'];
                $total_interets += (float)($p['interets_generes'] ?? 0);
            }
        }

        $portefeuille_total = $portefeuille_fcp + $portefeuille_pmg;

        // On récupère aussi tous les produits pour la modale d'ajout
        $products = Product::all()->map(function($p) {
            $latestVl = \App\Models\AssetValue::where('product_id', $p->id)
                ->orderBy('date_vl', 'desc')
                ->first();
            $p->recent_vl = $latestVl ? (float)$latestVl->vl : (float)$p->vl;
            return $p;
        });
        
        $categories = \App\Models\ProductsCategory::all();

        $availableMonthsRaw = $this->productControllerImport->getAvailableStatementMonths($customer->id);
        
        // Pagination manuelle
        $page = $request->get('page', 1);
        $perPage = 5;
        $offset = ($page * $perPage) - $perPage;
        
        $availableMonths = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($availableMonthsRaw, $offset, $perPage, true),
            count($availableMonthsRaw),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        if ($request->ajax()) {
            return view('front-end.partials.customer-months-table', compact('customer', 'availableMonths'));
        }

        return view('front-end.customer-detail', compact(
            'customer', 
            'productsWithGains', 
            'product_nb',
            'portefeuille_total',
            'portefeuille_pmg',
            'portefeuille_fcp',
            'total_interets',
            'total_plus_value_fcp',
            'products',
            'categories',
            'availableMonths'
        ));
    }

    public function customersStatementsMenu(Request $request)
    {
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'name');
        $order = $request->input('order', 'asc');
        $currentDate = Carbon::now();

        // 1. Base query
        $query = User::where('role_id', '2');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // 2. Fetch all for calculation and sorting
        $allMatchedUsers = $query->get();
        $processedUsers = collect();

        foreach ($allMatchedUsers as $user) {
            $stats = $this->productControllerImport->getUserStats($user->id);
            $user->total_capital = $stats['total_invested'];
            $user->total_interets = $stats['total_gains'];
            $user->portefeuille_total = $stats['total_portfolio'];
            
            $processedUsers->push($user);
        }

        // 3. Sorting
        if ($order == 'desc') {
            $processedUsers = $processedUsers->sortByDesc($sortBy);
        } else {
            $processedUsers = $processedUsers->sortBy($sortBy);
        }

        // 4. Manual Pagination
        $page = $request->input('page', 1);
        $perPage = 10;
        $pagedData = $processedUsers->slice(($page - 1) * $perPage, $perPage)->values();
        
        $customers = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedData,
            $processedUsers->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        if ($request->ajax()) {
            return view('front-end.partials.customer-statements-table', compact('customers', 'search', 'sortBy', 'order'));
        }

        return view('front-end.customer-statements', compact('customers', 'search', 'sortBy', 'order'));
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
                        ->where('date_vl', '>=', $transaction->date_validation)
                        ->orderBy('date_vl', 'desc')
                        ->first();


                    if ($latestAssetValue) {
                        $vl_actuel = $latestAssetValue->vl;
                        $totalGain += $this->productControllerImport->calculateFCPGain($vl_actuel, $transaction);
                        $recentGain[] = $this->productControllerImport->calculateFCPGain($vl_actuel, $transaction);
                        $recentGains[] = [
                            'gain' => $recentGain,
                            'product_id' => $product->id,
                        ];
                    }

                    // Calculer la différence hebdomadaire si nécessaire
                    $assetValues = AssetValue::where('product_id', $transaction->product_id)
                        ->where('date_vl', '>=', $transaction->date_validation)
                        ->orderBy('date_vl', 'desc')
                        ->take(2)
                        ->get();

                    if ($assetValues->count() >= 1) {
                        $vl_actuel = $assetValues->first()->vl;
                        $vl_antepenultimate = $assetValues->count() == 2 ? $assetValues->last()->vl : $transaction->vl_buy;

                        //dd("Valeur liquidative actuelle = ".$vl_actuel,"Valeur liquidative précédente = ".$vl_antepenultimate, "Nombre de parts = ".$transaction->nb_part,"Différence Vl précédente Vl actuelle = ".$vl_actuel - $vl_antepenultimate, "Gain de la semaine (nb part * (vl_prec - vl_actuelle)) = ".$transaction->nb_part * ($vl_actuel - $vl_antepenultimate));
                        // Calcul de la valorisation du portefeuille
                        $valorisationPortefeuille = $this->productControllerImport->calculateFCPGain($vl_actuel, $transaction);

                        // Calcul du gain entre l'avant-dernière et la dernière VL

                        $gain = $transaction->nb_part * ($vl_actuel - $vl_antepenultimate);
                        $gainWeekFcp += $gain;
                        $gainWeekTab[] = $gainWeekFcp;

                        //dd($gainWeekFcp);

                        // Calcul du cumul des gains/pertes depuis la souscription
                        $cumulGains += $valorisationPortefeuille - ($transaction->nb_part * $transaction->vl_buy);

                        // Ajouter le gain cumulé au total
                        $totalGainFcp += $this->productControllerImport->calculateFCPGain($vl_actuel, $transaction);
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

    /**
     * Gestion des Valeurs Liquidatives (VL)
     */
    public function vlHistory(Request $request)
    {
        $products = Product::where('products_category_id', 1)->get();
        $selectedProductId = $request->get('product_id', $products->first()?->id);
        
        $vls = AssetValue::with('product')
            ->where('product_id', $selectedProductId)
            ->orderBy('date_vl', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('front-end.asset-manager.vls', compact('products', 'vls', 'selectedProductId'));
    }

    public function storeVl(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'vl' => 'required|numeric|min:0',
            'date_vl' => 'required|date',
        ]);

        // Vérifier si une VL existe déjà à cette date pour ce produit
        $exists = AssetValue::where('product_id', $request->product_id)
            ->whereDate('date_vl', $request->date_vl)
            ->first();

        if ($exists) {
            $exists->update(['vl' => $request->vl]);
            $msg = "Valeur Liquidative mise à jour pour le " . Carbon::parse($request->date_vl)->format('d/m/Y');
        } else {
            AssetValue::create([
                'product_id' => $request->product_id,
                'vl' => $request->vl,
                'date_vl' => $request->date_vl,
            ]);
            $msg = "Nouvelle Valeur Liquidative enregistrée avec succès.";
        }

        \App\Models\UserActivityLog::log(
            "MAJ_VALEUR_LIQUIDATIVE",
            null,
            "L'Asset Manager " . auth()->user()->name . " a mis à jour la VL du produit ID #{$request->product_id} à {$request->vl} pour la date du {$request->date_vl}"
        );

        return back()->with('success', $msg);
    }

    /**
     * Suppression d'une valeur liquidative par l'Asset Manager
     */
    public function deleteVl($id)
    {
        $vl = \App\Models\AssetValue::findOrFail($id);
        $productName = $vl->product->title ?? 'N/A';
        $dateVl = $vl->date_vl;

        $vl->delete();

        \App\Models\UserActivityLog::log(
            "SUPPRESSION_VALEUR_LIQUIDATIVE",
            null,
            "Suppression de la VL du $dateVl pour le produit $productName par l'Asset Manager " . auth()->user()->name
        );

        return back()->with('success', 'Valeur liquidative supprimée avec succès.');
    }
}
