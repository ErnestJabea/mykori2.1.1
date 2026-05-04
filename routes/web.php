<?php

use App\Http\Controllers\AchatActionController;
use App\Http\Controllers\AchatActionCustomerController;
use App\Http\Controllers\AdminFrontendController;
use App\Http\Controllers\AssetManagerController;
use App\Http\Controllers\BackofficeController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\DirectorGeneralController;
use App\Http\Controllers\ListeClientReleveController;
use App\Http\Controllers\MovementController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductDetailGainController;
use App\Http\Controllers\TransactionViewController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationOtpController;
use App\Models\AssetValue;
use App\Models\FinancialMovement;
use App\Models\Product;
use App\Models\ProductsCategory;
use App\Models\Transaction;
use App\Models\TransactionSupplementaire;
use App\Models\User;
use App\Services\InvestmentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;

/*
|--------------------------------------------------------------------------
*/

Route::get('/clear-fcp-data', function() {
    return DB::transaction(function () {
        $fcpProductIds = DB::table('products')->where('products_category_id', 1)->pluck('id');
        DB::table('fcp_movements')->whereIn('product_id', $fcpProductIds)->delete();
        DB::table('transaction_supplementaires')->whereIn('product_id', $fcpProductIds)->delete();
        DB::table('transactions')->whereIn('product_id', $fcpProductIds)->delete();
        return "NETTOYAGE RÉUSSI : Toutes les données FCP ont été effacées. Vous pouvez maintenant ré-insérer vos clients proprement via l'interface.";
    });
});

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


require __DIR__ . '/auth.php';




Route::name('home')->get('/', function () {
    return view('front-end.home');
});

// Route TEMPORAIRE pour la création de la table portfolios
Route::get('/run-portfolios-migration', function () {
    try {
        if (!Schema::hasTable('customer_portfolios')) {
            Schema::create('customer_portfolios', function ($table) {
                $table->id();
                $table->foreignId('user_id')->constrained();
                $table->string('type'); // FCP ou PMG
                $table->string('reference')->unique();
                $table->string('status')->default('active');
                $table->timestamps();
            });
            $msg = "Table créée. ";
        } else {
            $msg = "Table existante. ";
        }

        $users = \App\Models\User::where('role_id', 2)->get();
        $count = 0;

        foreach ($users as $u) {
            // Vérifier si le client a déjà au moins un dossier
            $exists = \App\Models\CustomerPortfolio::where('user_id', $u->id)->exists();
            if (!$exists) {
                // Déterminer le type par défaut via ses transactions
                $hasFcp = \App\Models\Transaction::where('user_id', $u->id)
                    ->whereHas('product', function($q){ $q->where('products_category_id', 1); })
                    ->exists();
                
                $type = $hasFcp ? 'FCP' : 'PMG';

                // Générer une référence
                $lastRef = \App\Models\CustomerPortfolio::where('type', $type)->orderBy('reference', 'desc')->first();
                if ($lastRef) {
                    $number = (int) preg_replace('/[^0-9]/', '', $lastRef->reference);
                    $newRef = $type . str_pad($number + 1, 4, '0', STR_PAD_LEFT);
                } else {
                    $newRef = $type . '0001';
                }

                \App\Models\CustomerPortfolio::create([
                    'user_id'   => $u->id,
                    'type'      => $type,
                    'reference' => $newRef,
                    'status'    => 'active',
                ]);
                $count++;
            }
        }

        $usersCount = \App\Models\User::where('role_id', 2)->count();
        $portfolios = \App\Models\CustomerPortfolio::with('user')->get();
        
        echo "DEBUG: $usersCount clients (role_id=2) trouvés. " . $portfolios->count() . " portfolios existent déjà.<br><br>";
        
        foreach($portfolios->take(10) as $p) {
            echo "PORTFOLIO: " . $p->reference . " | USER: " . ($p->user ? $p->user->name : 'MISSING_USER') . " (ID: " . $p->user_id . ")<br>";
        }
        
        return "";
    } catch (\Exception $e) {
        return "ERROR: " . $e->getMessage();
    }
});

Route::name('login')->get('/login', function () {
    return view('front-end.home');
});

Route::get('/connexion', function () {
    return view('front-end.home');
})->name('connexion');



Route::get('/test-mail', function () {

    Mail::raw('Test email KORI', function ($message) {
        $message->to('ernestjabea@gmail.com')
            ->subject('Test Mail');
    });

    return 'Mail envoyé';
});
Route::name('login-user')->post('/login-user', [VerificationOtpController::class, 'login']);


Route::middleware('auth')->group(function () {

    Route::post('change-password', [ChangePasswordController::class, 'changePassword'])
        ->name('change-password');



    Route::post('/achat-action', [AchatActionController::class, 'acheterAction'])->name('achat-action-fcp');
    Route::post('/achat-action-pmg', [AchatActionController::class, 'acheterActionPmg'])->name('achat-action-pmg');

    Route::post('/placement-fcp', [AchatActionCustomerController::class, 'acheterAction'])->name('achat-action-customer-fcp');
    Route::post('/placement-pmg', [AchatActionCustomerController::class, 'acheterActionPmg'])->name('achat-action-customer-pmg');

    Route::name('code-verification')->post('/code-verification', [VerificationOtpController::class, 'verifyCode']);

    Route::get('/code-otp', function () {
        return view('front-end.checkCode');
    })->name('otp.form');


    Route::get('/dashboard', [UserController::class, 'show'])->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | ASSET MANAGER
    |--------------------------------------------------------------------------
    |
    */
    Route::prefix('asset-manager')->middleware(['auth'])->group(function () {
            //////////// TESTS ////////////


            Route::get('/test-releve/{id}', function ($id) {
                $service = new InvestmentService();

                // 1. Récupérer la transaction avec ses relations
                $transaction = Transaction::with(['user', 'product'])->findOrFail($id);

                // 2. S'assurer que les mouvements (souscription initiale/capitalisation) existent
                $service->refreshCapitalization($transaction);

                // 3. Récupérer l'historique
                $movements = FinancialMovement::where('transaction_id', $transaction->id)
                    ->orderBy('date_operation', 'asc')
                    ->get();

                // 4. Déterminer la vue selon la catégorie (2 = PMG, le reste = FCP)
                $view = ($transaction->product->products_category_id == 2)
                    ? 'front-end.releves.releve-history'
                    : 'front-end.releves.releve-history-fcp';

                // 5. Générer et afficher le PDF
                $pdf = Pdf::loadView($view, [
                    'transaction' => $transaction,
                    'movements' => $movements,
                    'client' => $transaction->user
                ]);

                return $pdf->stream("releve_test_{$transaction->ref}.pdf");
            });

            Route::get('/test-capitalization', function () {
                $service = new InvestmentService();

                // 1. Chercher la transaction de test
                $transaction = Transaction::where('amount', 1000000)->where('status', 'Succès')->first();

                if (!$transaction) {
                    $user = User::first();
                    $product = Product::where('products_category_id', 2)->first();

                    if (!$user || !$product) {
                        return "Erreur : Créez au moins un utilisateur et un produit PMG.";
                    }

                    // Création avec TOUS les champs obligatoires de votre image
                    $transaction = Transaction::create([
                        'title'           => 'Test Capitalisation', // Obligatoire
                        'ref'             => 'REF-' . Str::upper(Str::random(8)), // Obligatoire
                        'payment_mode'    => 'Virement', // Obligatoire
                        'amount'          => 1000000,
                        'status'          => 'Succès',
                        'user_id'         => $user->id,
                        'product_id'      => $product->id,
                        'vl_buy'          => 10.00,
                        'date_validation' => Carbon::now()->subYears(2)->format('Y-m-d'),
                        'date_echeance'   => Carbon::now()->addYears(3)->format('Y-m-d'),
                        'duree'           => 60,
                        'nb_part'         => 0,
                        'montant_initiale' => 1000000,
                        'type'            => 1
                    ]);

                    echo "Transaction de test créée avec succès !<br>";
                }

                // 2. Exécution du service
                $service->refreshCapitalization($transaction);

                // 3. Récupération des mouvements créés
                $movements = FinancialMovement::where('transaction_id', $transaction->id)->get();

                return [
                    'status' => $movements->count() > 0 ? 'OK' : 'AUCUN MOUVEMENT',
                    'nombre_de_capitalisations' => $movements->count(),
                    'details_mouvements' => $movements
                ];
            });

            Route::get('/test-redemption', function () {
                $service = new InvestmentService();

                // 1. Chercher la transaction de test (ou la créer)
                $transaction = Transaction::where('amount', 1000000)->where('status', 'Succès')->first();

                if (!$transaction) {
                    $user = User::first();
                    $product = Product::where('products_category_id', 2)->first();

                    if (!$user || !$product) {
                        return "Erreur : Créez au moins un utilisateur et un produit PMG.";
                    }

                    $transaction = Transaction::create([
                        'title'           => 'Test Redemption',
                        'ref'             => 'REF-' . Str::upper(Str::random(8)),
                        'payment_mode'    => 'Virement',
                        'amount'          => 1000000,
                        'status'          => 'Succès',
                        'user_id'         => $user->id,
                        'product_id'      => $product->id,
                        'vl_buy'          => 10.00,
                        'date_validation' => Carbon::now()->subYears(2)->format('Y-m-d'),
                        'date_echeance'   => Carbon::now()->addYears(3)->format('Y-m-d'),
                        'duree'           => 60,
                        'nb_part'         => 0,
                        'montant_initiale' => 1000000,
                        'type'            => 1
                    ]);

                    echo "Transaction de test créée !<br>";
                }

                // 2. Étape A : On s'assure que le capital est à jour (Capitalisation)
                $service->refreshCapitalization($transaction);

                // 3. Étape B : On exécute un rachat de 50 000 FCFA
                try {
                    $redemption = $service->executeRedemption($transaction->id, 50000);
                    echo "Rachat de 50 000 effectué avec succès !<br>";
                } catch (\Exception $e) {
                    return "Erreur lors du rachat : " . $e->getMessage();
                }

                // 4. Récupération de tout l'historique pour vérification
                $movements = FinancialMovement::where('transaction_id', $transaction->id)
                    ->orderBy('date_operation', 'asc')
                    ->get();

                return [
                    'status' => 'OK',
                    'nombre_total_mouvements' => $movements->count(),
                    'historique_flux' => $movements->map(function ($m) {
                        return [
                            'date' => $m->date_operation,
                            'type' => $m->type,
                            'montant' => $m->amount,
                            'capital_avant' => $m->capital_before,
                            'capital_apres' => $m->capital_after,
                        ];
                    })
                ];
            });

            Route::get('/download-test-pdf/{id}', [ProductController::class, 'downloadStatement']);



            Route::get('/test-final-pdf/{id}', function ($id) {
                $service = new InvestmentService();
                $transaction = Transaction::with(['user', 'product'])->findOrFail($id);

                // 1. On s'assure que la transaction initiale est bien dans l'historique
                // (Méthode créée précédemment dans le service)
                $service->refreshCapitalization($transaction);

                // 2. On récupère les mouvements pour ce client
                $movements = FinancialMovement::where('transaction_id', $transaction->id)
                    ->orderBy('date_operation', 'asc')
                    ->get();

                // 3. Préparation des variables pour la vue (pour correspondre au template)
                $client = $transaction->user;

                // 4. Génération du PDF
                $pdf = Pdf::loadView('front-end.releves.releve-history', compact('transaction', 'movements', 'client'));

                return $pdf->stream("test_releve_kori.pdf"); // .stream pour l'afficher dans le navigateur
            });

            ////// fin test ////////

            Route::get('/', [ProductController::class, 'indexAssetManager'])->name('asset-manager');

            Route::get('/customer', [ProductController::class, 'customers'])->name('customer');
            Route::get('/customer/export', [ProductController::class, 'exportCustomers'])->name('customer.export');
            Route::post('/customer/transaction/edit', [ProductController::class, 'editTransaction'])->name('customer.transaction.edit');

            Route::get('/customer/{customer}', [ProductController::class, 'customersDetail'])->name('customer-detail');
            Route::get('/nouveau-client/{portfolio?}', [AssetManagerController::class, 'createCustomer'])->name('asset-manager.create-customer');
            Route::post('/nouveau-client', [AssetManagerController::class, 'storeCustomer'])->name('asset-manager.store-customer');
            Route::post('/modifier-client/{portfolio}', [AssetManagerController::class, 'updateCustomer'])->name('asset-manager.update-customer');
            Route::delete('/supprimer-dossier/{portfolio}', [AssetManagerController::class, 'deletePortfolio'])->name('asset-manager.delete-portfolio');
            Route::post('/store/transaction-manager', [MovementController::class, 'storeFinancialMovement'])->name('save-transactions-client');
            Route::get('/customer/{customer}/transaction-manager', [MovementController::class, 'indexFinancialMovement'])->name('transactions-client');

            Route::get('/customer/releve-client/liste/{type?}', [ListeClientReleveController::class, 'index'])->name('releve-client');


            Route::get('/test-calculs', function() {
                return (new ProductController)->debugClientPortfolios('2026-01-31');
            });


            Route::post('/rachat-partiel', [MovementController::class, 'rachatPartiel'])->name('transactions.rachat-partiel');
    
            Route::post('/rachat-fcp', [MovementController::class, 'rachatFcp'])->name('transactions.rachat-fcp');
            // Route pour les intérêts précomptés
            Route::post('/precompte', [MovementController::class, 'verserPrecompte'])->name('transactions.precompte');

            // Route pour le remboursement des intérêts
            Route::post('/remboursement-interets', [MovementController::class, 'rembourserInterets'])->name('transactions.remboursement-interets');

            Route::get('/sync-anniversaries', [ProductController::class, 'syncAnniversaryMovements']);

            Route::get('/products/client/{customer}', function ($customer) {
                $customer = User::findOrFail($customer);
                $products_categories = App\Models\ProductsCategory::orderBy('created_at', 'desc')->get();
                $products = App\Models\Product::orderBy('created_at', 'desc')->where('nb_action', '>', 0)->get();

                // On attache la VL la plus récente pour chaque produit
                foreach ($products as $product) {
                    if ($product->products_category_id == 1) { // FCP
                        $lastVl = App\Models\AssetValue::where('product_id', $product->id)
                            ->orderBy('created_at', 'desc')
                            ->first();
                        $product->recent_vl = $lastVl ? $lastVl->vl : $product->vl;
                    } else {
                        $product->recent_vl = $product->vl; // PMG utilise le taux par défaut
                    }
                }

                $ownedPmgProductIds = App\Models\Transaction::where('user_id', $customer->id)
                    ->where('status', 'Succès')
                    ->whereIn('product_id', $products->where('products_category_id', 2)->pluck('id'))
                    ->pluck('product_id')
                    ->merge(
                        App\Models\TransactionSupplementaire::where('user_id', $customer->id)
                        ->where('status', 'Succès')
                        ->whereIn('product_id', $products->where('products_category_id', 2)->pluck('id'))
                        ->pluck('product_id')
                    )
                    ->unique()
                    ->values()
                    ->toArray();

                return view('front-end.products-customer')->with('customer', $customer)->with('products', $products)->with('products_categories', $products_categories)->with('ownedPmgProductIds', $ownedPmgProductIds);
            })->name('products-customer');

            Route::get('/customer/{customer}/products/{slug}/', function ($customer, $slug) {
                $product = App\Models\Product::where('slug', $slug)->first();
                $customer = User::findOrFail($customer);
                $today = Carbon::now();
                $startOfMonth = $today->startOfMonth();
                $endOfMonth = $today->endOfMonth();


                // Filtrer les valeurs liquidatives créées entre le début et la fin du mois en cours pour le produit sélectionné
                $product_vls = \App\Models\AssetValue::where('product_id', $product->id)
                    ->orderBy('created_at', 'desc')
                    ->take(8)
                    ->get();


                $product_vls2 = \App\Models\AssetValue::where('product_id', $product->id)
                    ->orderBy('id', 'desc')
                    ->take(8)
                    ->get();

                $date_ord = $product_vls2->sortBy('date_vl')->values();

                return view('front-end.product-detail-customer')->with('customer', $customer)->with('product', $product)->with('asset_value_all', $product_vls)
                    ->with('asset_value_all2', $date_ord);
            })->name('product-customer-detail');


            Route::get(
                '/releves/preview/{client}',
                [ListeClientReleveController::class, 'previewPmg']
            )->name('asset-manager.releves.preview');

            Route::get(
                '/releves/preview-fcp/{client}',
                [ListeClientReleveController::class, 'previewFcp']
            )->name('asset-manager.releves.preview-fcp');

            Route::post('/releves/send', [ListeClientReleveController::class, 'sendSelected'])->name('releves.send');

            // Gestion des Valeurs Liquidatives (VL)
            Route::get('/vls', [AssetManagerController::class, 'vlHistory'])->name('asset-manager.vls');
            Route::post('/vls', [AssetManagerController::class, 'storeVl'])->name('asset-manager.vls.store');
            Route::delete('/vls/{id}', [AssetManagerController::class, 'deleteVl'])->name('asset-manager.vl.delete');

            });

    /*
    |--------------------------------------------------------------------------
    | COMPLIANCE PORTAL (Isolated)
    |--------------------------------------------------------------------------
    */
    Route::prefix('compliance')->middleware(['auth'])->group(function () {
        Route::get('/', [ComplianceController::class, 'dashboard'])->name('compliance.dashboard');
        Route::get('/clients', [ComplianceController::class, 'clients'])->name('compliance.clients');
        Route::get('/clients/{client}/history', [ComplianceController::class, 'clientHistory'])->name('compliance.client-history');
        Route::get('/portfolio-audit', [ComplianceController::class, 'portfolioAudit'])->name('compliance.portfolio-audit');
        Route::match(['GET', 'POST'], '/portfolio-audit/export', [ComplianceController::class, 'exportAudit'])->name('compliance.portfolio-audit.export');
        Route::get('/vl-history', [ComplianceController::class, 'vlHistory'])->name('compliance.vl-history');
        Route::get('/export', [ComplianceController::class, 'export'])->name('compliance.export');
        
        // Rapports d'Envoi (Historique)
        Route::get('/statements/history', [ComplianceController::class, 'statementsHistory'])->name('compliance.statements-history');
        Route::get('/statements/download/{id}', [ComplianceController::class, 'downloadBatchReport'])->name('compliance.statements-download');
    });



    Route::get('/asset-manager/test-log', [ListeClientReleveController::class, 'testSimplePdf']);
    /*
    END ASSET MANAGER
    */
    Route::get('/my-history', function () {
        $userId = Auth::user()->id;

        // 1. Transactions officielles avec status Succès
        $transactions = App\Models\Transaction::where('user_id', $userId)
            ->where('status', 'Succès')
            ->orderBy('created_at', 'desc')
            ->get();

        $mergedMovements = collect();

        // 2. Mouvements financiers PMG (financial_movements)
        $txIds = $transactions->pluck('id');
        $financialMovements = DB::table('financial_movements')
            ->whereIn('transaction_id', $txIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->each(function ($m) use ($transactions, &$mergedMovements) {
                $tx = $transactions->firstWhere('id', $m->transaction_id);
                if(!$tx) {
                    $tx = App\Models\TransactionSupplementaire::find($m->transaction_id);
                }
                $productTitle = $tx
                    ? optional(App\Models\Product::find($tx->product_id))->title
                    : 'PMG';
                
                $mergedMovements->push((object)[
                    'date'               => $m->created_at ?? $m->date_operation,
                    'date_souscription'  => $m->date_operation,
                    'libelle'            => strtoupper(str_replace('_', ' ', $m->type)),
                    'ref'                => $tx->ref ?? '-',
                    'produit'            => $productTitle,
                    'montant'            => (float)$m->amount,
                    'fees'               => 0,
                    'sens'               => in_array($m->type, ['rachat_partiel', 'rachat_total', 'precompte_interets', 'paiement_interets', 'remboursement']) ? 'sortant' : 'entrant',
                    'source'             => 'pmg',
                    'id'                 => $m->id,
                ]);
            });

        // 3. Mouvements FCP (fcp_movements) - On exclut les souscriptions/versements déjà listés en transactions
        DB::table('fcp_movements')
            ->where('user_id', $userId)
            ->whereNull('transaction_id') // On ne prend que les mouvements manuels ou rachats sans TX liée
            ->orderBy('created_at', 'desc')
            ->get()
            ->each(function ($m) use (&$mergedMovements) {
                $productTitle = optional(App\Models\Product::find($m->product_id))->title ?? 'FCP';
                $isIncoming   = $m->nb_parts_change >= 0;
                $label        = $isIncoming ? 'SOUSCRIPTION FCP' : 'RACHAT FCP';
                
                $mergedMovements->push((object)[
                    'date'               => $m->created_at ?? $m->date_operation,
                    'date_souscription'  => $m->date_operation,
                    'libelle'            => $label,
                    'ref'                => $m->reference ?? '-',
                    'produit'            => $productTitle,
                    'montant'            => (float)($m->amount_xaf ?? $m->montant ?? 0),
                    'fees'               => 0,
                    'sens'               => $isIncoming ? 'entrant' : 'sortant',
                    'source'             => 'fcp',
                    'id'                 => $m->id,
                ]);
            });

        // 4. Transactions initiales
        foreach ($transactions as $tx) {
            $productTitle = optional(App\Models\Product::find($tx->product_id))->title ?? 'Produit';
            
            // Ligne principale (Montant Brut)
            $mergedMovements->push((object)[
                'date'               => $tx->created_at,
                'date_souscription'  => $tx->date_validation ?? $tx->created_at,
                'libelle'            => $tx->title ?? 'SOUSCRIPTION',
                'ref'                => $tx->ref,
                'produit'            => $productTitle,
                'montant'            => (float)$tx->amount,
                'fees'               => 0, // Déjà inclus dans le montant brut enregistré
                'sens'               => 'entrant',
                'source'             => 'tx',
                'id'                 => $tx->id,
            ]);

            // Ligne des frais
            if ((float)$tx->fees > 0) {
                $mergedMovements->push((object)[
                    'date'               => $tx->created_at,
                    'date_souscription'  => $tx->date_validation ?? $tx->created_at,
                    'libelle'            => 'FRAIS DE SOUSCRIPTION',
                    'ref'                => $tx->ref,
                    'produit'            => $productTitle,
                    'montant'            => (float)$tx->fees,
                    'fees'               => (float)$tx->fees,
                    'sens'               => 'sortant',
                    'source'             => 'tx_fees',
                    'id'                 => $tx->id,
                ]);
            }
        }

        App\Models\TransactionSupplementaire::where('user_id', $userId)
            ->where('status', 'Succès')
            ->get()
            ->each(function ($tx) use (&$mergedMovements) {
                $productTitle = optional(App\Models\Product::find($tx->product_id))->title ?? 'Produit';
                
                // Ligne principale (Montant Brut)
                $mergedMovements->push((object)[
                    'date'               => $tx->created_at,
                    'date_souscription'  => $tx->date_validation ?? $tx->created_at,
                    'libelle'            => $tx->title ?? 'VERSEMENT LIBRE',
                    'ref'                => $tx->ref,
                    'produit'            => $productTitle,
                    'montant'            => (float)$tx->amount,
                    'fees'               => 0,
                    'sens'               => 'entrant',
                    'source'             => 'tx_supp',
                    'id'                 => $tx->id,
                ]);

                // Ligne des frais
                if ((float)$tx->fees > 0) {
                    $mergedMovements->push((object)[
                        'date'               => $tx->created_at,
                        'date_souscription'  => $tx->date_validation ?? $tx->created_at,
                        'libelle'            => 'FRAIS DE SOUSCRIPTION',
                        'ref'                => $tx->ref,
                        'produit'            => $productTitle,
                        'montant'            => (float)$tx->fees,
                        'fees'               => (float)$tx->fees,
                        'sens'               => 'sortant',
                        'source'             => 'tx_supp_fees',
                        'id'                 => $tx->id,
                    ]);
                }
            });

        // 5. Trier par date (created_at) décroissante
        $allMovements = $mergedMovements->sortByDesc('date')->values();

        return view('front-end.my-history', compact('allMovements', 'transactions'));
    })->name('my-history');

    // PDF download de l'historique de transactions
    Route::get('/my-history/download-pdf', [ProductController::class, 'downloadHistoryStatement'])->name('my-history-pdf');
    Route::get('/customer-history/download-pdf/{customer_id}', [ProductController::class, 'downloadHistoryStatement'])->name('customer-history.pdf');


    Route::get('/my-history/{reference}', function ($reference) {
        $transaction = App\Models\Transaction::where("ref", $reference)->orderBy("created_at", 'desc')->limit(10)->first();
        $produit = App\Models\Product::where('id', $transaction->product_id)->first();
        $transactions = App\Models\TransactionSupplementaire::where('transaction_id', $transaction->id)->orderBy("created_at", 'desc')->get();
        return view('front-end.transaction-detail')->with('transactions', $transactions)->with('transaction', $transaction)->with('produit', $produit);
    })->name('transaction-detail');

    Route::get('/products', function () {
        $products_categories = App\Models\ProductsCategory::orderBy('created_at', 'desc')->get();
        $products = App\Models\Product::orderBy('created_at', 'desc')->where('nb_action', '>', 0)->get();
        return view('front-end.products')->with('products', $products)->with('products_categories', $products_categories);
    })->name('products');

    Route::get('/products/{slug}', function ($slug) {
        $product = App\Models\Product::where('slug', $slug)->first();
        $today = Carbon::now();
        $startOfMonth = $today->startOfMonth();
        $endOfMonth = $today->endOfMonth();
        // Filtrer les valeurs liquidatives créées entre le début et la fin du mois en cours pour le produit sélectionné
        $product_vls = \App\Models\AssetValue::where('product_id', $product->id)
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();


        $product_vls2 = \App\Models\AssetValue::where('product_id', $product->id)
            ->orderBy('id', 'desc')
            ->take(8)
            ->get();

        $date_ord = $product_vls2->sortBy('date_vl')->values();

        return view('front-end.product-detail')->with('product', $product)->with('asset_value_all', $product_vls)
            ->with('asset_value_all2', $date_ord);
    })->name('product-detail');

    Route::name("success-transaction")->get('/success-registration-transaction', function () {
        return view('front-end.success-achat');
    });


    Route::name("success-transaction-customer")->get('/asset-manager/success-registration-transaction', function () {
        return view('front-end.asset-manager.success-customer-achat');
    });

    Route::get('/my-products', [ProductController::class, 'showProductsWithGains'])->name('my-products');

    Route::get('/my-products/{slug}', [ProductDetailGainController::class, 'showProductGain'])->name('product-detail-gain');

    Route::get('/help', function () {
        return view('front-end.help');
    })->name('help');


    Route::get('/my-statements', [ProductController::class, 'myStatements'])->name('my-statements');
    Route::get('/my-statement/monthly/{year}/{month}/{type}', [ProductController::class, 'downloadMonthlyStatement'])->name('my-statement.monthly');
    Route::get('/customer-statements', [AssetManagerController::class, 'customersStatementsMenu'])->name('customer.statements');
    Route::get('/api/customer-available-months/{customer_id}', [ProductController::class, 'getAvailableMonthsApi'])->name('api.customer-months');
    Route::get('/api/product-vl/{product_id}/{date}', [ProductController::class, 'getVlAtDate'])->name('api.product-vl');
    Route::get('/api/product-holdings/{user_id}/{product_id}/{date}', [ProductController::class, 'getHoldingsAtDate'])->name('api.product-holdings');
    Route::get('/api/fcp-evolution/{product_id}/{customer_id}', [ProductController::class, 'getFcpEvolutionApi'])->name('api.fcp-evolution');
    Route::get('/api/pmg-evolution/{product_id}/{customer_id}', [ProductController::class, 'getPmgEvolutionApi'])->name('api.pmg-evolution');
    Route::get('/customer-statement/monthly/{year}/{month}/{type}/{customer_id}', [ProductController::class, 'downloadMonthlyStatement'])->name('customer-statement.monthly');

    Route::get('/my-statement/{id}', [ProductController::class, 'downloadStatement'])->name('my-statement');

    Route::get('/profile', function () {
        return view('front-end.reset-password');
    })->name('profile');
});






Route::get('/logout', function () {
    Auth::logout();
    return redirect('/login');
})->name('logout');

Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
    
    Route::group(['middleware' => ['admin.user']], function () {
        // Voyager routes are handled here
    });
});

// --- Dossier Compliance (Audit & Risques) ---
Route::prefix('compliance')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [ComplianceController::class, 'dashboard'])->name('compliance.dashboard');
    Route::get('/clients', [ComplianceController::class, 'clients'])->name('compliance.clients');
    Route::get('/client/{client}', [ComplianceController::class, 'clientHistory'])->name('compliance.client-detail');
    Route::get('/vls', [ComplianceController::class, 'vlHistory'])->name('compliance.vl-history');
    Route::delete('/vls/{id}', [ComplianceController::class, 'deleteVl'])->name('compliance.vl.delete');
    Route::get('/vls/export', [ComplianceController::class, 'export'])->name('compliance.vls.export');
    Route::get('/portfolio-audit', [ComplianceController::class, 'portfolioAudit'])->name('compliance.portfolio-audit');
});

// --- Dossier Backoffice ---
Route::prefix('backoffice')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [BackofficeController::class, 'dashboard'])->name('backoffice.dashboard');
    Route::get('/transactions', [BackofficeController::class, 'transactions'])->name('backoffice.transactions');
    Route::post('/validate-transaction/{id}/{type}', [BackofficeController::class, 'validateTransaction'])->name('backoffice.validate-transaction');
});

// --- Dossier Direction Générale ---
Route::prefix('dg')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DirectorGeneralController::class, 'dashboard'])->name('dg.dashboard');
    
    // Rapports d'Envoi (Historique DG)
    Route::get('/statements/history', [DirectorGeneralController::class, 'statementsHistory'])->name('dg.statements-history');
    Route::get('/statements/download/{id}', [DirectorGeneralController::class, 'downloadBatchReport'])->name('dg.statements-download');
});

// --- Administration Frontend ---
Route::prefix('admin-front')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\AdminFrontendController::class, 'dashboard'])->name('admin.front.dashboard');
    Route::get('/users', [\App\Http\Controllers\AdminFrontendController::class, 'users'])->name('admin.front.users');
    // Ajout de la route pour créer un utilisateur
    Route::post('/users/store', [\App\Http\Controllers\AdminFrontendController::class, 'storeUser'])->name('admin.front.store-user');
    Route::post('/users/{user}/role', [\App\Http\Controllers\AdminFrontendController::class, 'updateUserRole'])->name('admin.front.update-role');
    Route::get('/logs', [\App\Http\Controllers\AdminFrontendController::class, 'activityLogs'])->name('admin.front.logs');
    Route::get('/logs/export', [\App\Http\Controllers\AdminFrontendController::class, 'exportLogs'])->name('admin.front.logs.export');
    
    // Gestion des Menus
    Route::get('/menus', [\App\Http\Controllers\AdminFrontendController::class, 'menus'])->name('admin.front.menus');
    Route::post('/menus', [\App\Http\Controllers\AdminFrontendController::class, 'storeMenu'])->name('admin.front.menus.store');
    Route::post('/menus/{menu}', [\App\Http\Controllers\AdminFrontendController::class, 'updateMenu'])->name('admin.front.menus.update');
    Route::delete('/menus/{menu}', [\App\Http\Controllers\AdminFrontendController::class, 'deleteMenu'])->name('admin.front.menus.delete');
});

// --- Gestion Commerciale (CRM) ---
Route::prefix('crm')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\CrmController::class, 'index'])->name('crm.dashboard');
    Route::get('/prospects', [\App\Http\Controllers\CrmController::class, 'prospects'])->name('crm.prospects');
    Route::post('/prospects', [\App\Http\Controllers\CrmController::class, 'storeProspect'])->name('crm.prospects.store');
    Route::patch('/prospects/{id}/status', [\App\Http\Controllers\CrmController::class, 'updateStatus'])->name('crm.prospects.update-status');
    Route::get('/clients', [\App\Http\Controllers\CrmController::class, 'clients'])->name('crm.clients');
});


// Routes de prévisualisation des pages d'erreur (à supprimer en production)
Route::get('/preview/404', function () { return view('errors.404'); });
Route::get('/preview/500', function () { return view('errors.500'); });
Route::get('/preview/419', function () { return view('errors.419'); });

Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});