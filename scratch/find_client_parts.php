<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$product = DB::table('products')->where('title', 'LIKE', '%FCP KORI SERENITE%')->first();

if (!$product) {
    echo "Produit non trouvé.\n";
    exit;
}

echo "Produit trouvé : " . $product->title . " (ID: " . $product->id . ")\n";

$movements = DB::table('fcp_movements')
    ->select('user_id', DB::raw('SUM(nb_parts_change) as total_parts'))
    ->where('product_id', $product->id)
    ->groupBy('user_id')
    ->get();

foreach ($movements as $m) {
    if (round($m->total_parts, 1) == 18589.9) {
        $user = DB::table('users')->where('id', $m->user_id)->first();
        echo "Client identifié : " . ($user ? $user->name : "Inconnu") . " (ID: " . $m->user_id . ")\n";
        echo "Nombre de parts précis en base : " . number_format($m->total_parts, 10, '.', '') . "\n";
        
        $details = DB::table('fcp_movements')
            ->where('user_id', $m->user_id)
            ->where('product_id', $product->id)
            ->get();
            
        echo "\nDétail des mouvements :\n";
        foreach ($details as $d) {
            echo "- Date: {$d->date_operation} | Type: {$d->type} | Parts: " . number_format($d->nb_parts_change, 6, '.', '') . " | Montant: {$d->amount_xaf}\n";
        }
    }
}
