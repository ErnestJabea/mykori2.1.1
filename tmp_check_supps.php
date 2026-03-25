<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransactionSupplementaire;
use App\Models\User;

$supps = TransactionSupplementaire::with('product')->get();
foreach($supps as $s) {
    if ($s->product && $s->product->products_category_id == 1) {
        $user = User::find($s->user_id);
        echo "ID: " . $s->user_id . " | Name: " . ($user ? $user->name : "Unknown") . " (Supp FCP)\n";
    }
}
