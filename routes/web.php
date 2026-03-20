<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductDetailGainController;
use App\Http\Controllers\TransactionViewController;
use App\Http\Controllers\AssetManagerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationOtpController;
use App\Http\Controllers\AchatActionController;
use App\Http\Controllers\AchatActionCustomerController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\ListeClientReleveController;
use App\Http\Controllers\MovementController;
use App\Services\InvestmentService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use TCG\Voyager\Facades\Voyager;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\TransactionSupplementaire;
use App\Models\ProductsCategory;
use App\Models\AssetValue;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\FinancialMovement;

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

    Route::post('/achat-action', [AchatActionCustomerController::class, 'acheterAction'])->name('achat-action-customer-fcp');
    Route::post('/achat-action-pmg-customer', [AchatActionCustomerController::class, 'acheterActionPmg'])->name('achat-action-customer-pmg');

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
    Route::prefix('asset-manager')
        ->middleware(['auth'])
        ->group(function () {
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

            Route::get('/customer/{customer}', [ProductController::class, 'customersDetail'])->name('customer-detail');
            Route::post('/store/transaction-manager', [MovementController::class, 'storeFinancialMovement'])->name('save-transactions-client');
            Route::get('/customer/{customer}/transaction-manager', [MovementController::class, 'indexFinancialMovement'])->name('transactions-client');

            Route::get('/customer/releve-client/test-1/2', [ListeClientReleveController::class, 'index'])->name('releve-client');


            Route::get('/test-calculs', function() {
                return (new ProductController)->debugClientPortfolios('2026-01-31');
            });


            Route::post('/rachat-partiel', [MovementController::class, 'rachatPartiel'])->name('transactions.rachat-partiel');
    
            // Route pour les intérêts précomptés
            Route::post('/precompte', [MovementController::class, 'verserPrecompte'])->name('transactions.precompte');

            Route::get('/sync-anniversaries', [ProductController::class, 'syncAnniversaryMovements']);

            Route::get('/products/client/{customer}', function ($customer) {
                $customer = User::findOrFail($customer);
                $products_categories = App\Models\ProductsCategory::orderBy('created_at', 'desc')->get();
                $products = App\Models\Product::orderBy('created_at', 'desc')->where('nb_action', '>', 0)->get();
                return view('front-end.products-customer')->with('customer', $customer)->with('products', $products)->with('products_categories', $products_categories);
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
                    ->take(4)
                    ->get();


                $product_vls2 = \App\Models\AssetValue::where('product_id', $product->id)
                    ->orderBy('id', 'desc')
                    ->take(4)
                    ->get();

                $date_ord = $product_vls2->sortBy('created_at')->values();

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
        });



    Route::get('/asset-manager/test-log', [ListeClientReleveController::class, 'testSimplePdf']);
    /*
    END ASSET MANAGER
    */
    Route::get('/my-history', function () {
        $transactions = App\Models\Transaction::where("user_id", Auth::user()->id)->orderBy("created_at", 'desc')->limit(10)->get();
        return view('front-end.my-history')->with('transactions', $transactions);
    })->name('my-history');

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
            ->take(4)
            ->get();


        $product_vls2 = \App\Models\AssetValue::where('product_id', $product->id)
            ->orderBy('id', 'desc')
            ->take(4)
            ->get();

        $date_ord = $product_vls2->sortBy('created_at')->values();

        return view('front-end.product-detail')->with('product', $product)->with('asset_value_all', $product_vls)
            ->with('asset_value_all2', $date_ord);
    })->name('product-detail');

    Route::name("success-transaction")->get('/success-registration-transaction', function () {
        return view('front-end.success-achat');
    });


    Route::name("success-transaction-customer")->get('/asset-manager/success-registration-transaction', function () {
        return view('front-end.success-customer-achat');
    });

    Route::get('/my-products', [ProductController::class, 'showProductsWithGains'])->name('my-products');

    Route::get('/my-products/{slug}', [ProductDetailGainController::class, 'showProductGain'])->name('product-detail-gain');

    Route::get('/help', function () {
        return view('front-end.help');
    })->name('help');


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

});
/* 
Route::fallback(function () {
    return view('errors.404');
});
 */