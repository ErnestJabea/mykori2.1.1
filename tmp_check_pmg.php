<?php
error_reporting(0);
ini_set('display_errors', 0);
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Transaction;
use App\Http\Controllers\ProductController;

$pc = new ProductController();
$users = App\Models\User::where('role_id', 2)->get();
$output = "LOG PORTFOLIOS\n";

foreach ($users as $user) {
    $results = $pc->getProductsWithGainsUser($user->id);
    foreach ($results as $res) {
        if ($res['type_product'] == 2) {
            $output .= "User: {$user->id} | Product: {$res['product_name']} | Portofolio: {$res['portfolio_valeur']} | Gain: {$res['interets_generes']} | Initial: {$res['capital_investi']} | Soulte: {$res['soulte']} | GainM: {$res['gain_month']}\n";
        }
    }
}
file_put_contents('output_debug_pmg.txt', $output);
echo "Done\n";
