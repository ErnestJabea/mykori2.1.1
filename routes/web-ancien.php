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
use Illuminate\Support\Facades\Route;
use TCG\Voyager\Facades\Voyager;
use App\Models\User;
use App\Transaction;
use App\Product;
use App\TransactionSupplementaire;
use Carbon\Carbon;

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


    Route::get('/asset-manager', [ProductController::class, 'indexAssetManager'])->name('asset-manager');
    Route::get('/customer', [ProductController::class, 'customers'])->name('customer');
    Route::get('/customer/{customer}', [ProductController::class, 'customersDetail'])->name('customer-detail');
    /*
    END ASSET MANAGER
    */
    Route::get('/my-history', function () {
        $transactions = App\Transaction::where("user_id", Auth::user()->id)->orderBy("created_at", 'desc')->limit(10)->get();
        return view('front-end.my-history')->with('transactions', $transactions);
    })->name('my-history');

    Route::get('/my-history/{reference}', function ($reference) {
        $transaction = App\Transaction::where("ref", $reference)->orderBy("created_at", 'desc')->limit(10)->first();
        $produit = App\Product::where('id', $transaction->product_id)->first();
        $transactions = App\TransactionSupplementaire::where('transaction_id', $transaction->id)->orderBy("created_at", 'desc')->get();
        return view('front-end.transaction-detail')->with('transactions', $transactions)->with('transaction', $transaction)->with('produit', $produit);
    })->name('transaction-detail');

    Route::get('/products', function () {
        $products_categories = App\ProductsCategory::orderBy('created_at', 'desc')->get();
        $products = App\Product::orderBy('created_at', 'desc')->where('nb_action', '>', 0)->get();
        return view('front-end.products')->with('products', $products)->with('products_categories', $products_categories);
    })->name('products');

    Route::get('/products/client/{customer}', function ($customer) {
        $customer = User::findOrFail($customer);
        $products_categories = App\ProductsCategory::orderBy('created_at', 'desc')->get();
        $products = App\Product::orderBy('created_at', 'desc')->where('nb_action', '>', 0)->get();
        return view('front-end.products-customer')->with('customer', $customer)->with('products', $products)->with('products_categories', $products_categories);
    })->name('products-customer');

    Route::get('/customer/{customer}/products/{slug}/', function ($customer, $slug) {
        $product = App\Product::where('slug', $slug)->first();
        $customer = User::findOrFail($customer);
        $today = Carbon::now();
        $startOfMonth = $today->startOfMonth();
        $endOfMonth = $today->endOfMonth();
        // Filtrer les valeurs liquidatives créées entre le début et la fin du mois en cours pour le produit sélectionné
        $product_vls = \App\AssetValue::where('product_id', $product->id)
            ->orderBy('created_at', 'desc')
            ->take(4)
            ->get();


        $product_vls2 = \App\AssetValue::where('product_id', $product->id)
            ->orderBy('id', 'desc')
            ->take(4)
            ->get();

        $date_ord = $product_vls2->sortBy('created_at')->values();

        return view('front-end.product-detail-customer')->with('customer', $customer)->with('product', $product)->with('asset_value_all', $product_vls)
            ->with('asset_value_all2', $date_ord);
    })->name('product-customer-detail');


    Route::get('/products/{slug}', function ($slug) {
        $product = App\Product::where('slug', $slug)->first();
        $today = Carbon::now();
        $startOfMonth = $today->startOfMonth();
        $endOfMonth = $today->endOfMonth();
        // Filtrer les valeurs liquidatives créées entre le début et la fin du mois en cours pour le produit sélectionné
        $product_vls = \App\AssetValue::where('product_id', $product->id)
            ->orderBy('created_at', 'desc')
            ->take(4)
            ->get();


        $product_vls2 = \App\AssetValue::where('product_id', $product->id)
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

Route::fallback(function () {
    return view('errors.404');
});
