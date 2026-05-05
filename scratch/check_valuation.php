<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$userId = 154; // I'll assume this is the user from the image name or similar, wait I don't know the ID
// Let's find the user by email from the image
$email = "auramayolle2020@gmail.com";
$user = DB::table('users')->where('email', $email)->first();

if (!$user) {
    echo "User not found\n";
    exit;
}

echo "User ID: " . $user->id . "\n";

$allMovements = DB::table('fcp_movements')->where('user_id', $user->id)->get();
echo "FCP Movements:\n";
foreach ($allMovements as $m) {
    echo " - Product: {$m->product_id}, Parts: {$m->nb_parts_change}, Amount: {$m->amount_xaf}, Type: {$m->type}\n";
}

$totalParts = DB::table('fcp_movements')->where('user_id', $user->id)->sum('nb_parts_change');
echo "Total Parts: " . $totalParts . "\n";

$lastVl = DB::table('asset_values')->where('product_id', 1)->orderBy('date_vl', 'desc')->value('vl'); // Assuming product 1
echo "Last VL (P1): " . $lastVl . "\n";

$valuation = (double)$totalParts * (double)$lastVl;
echo "Valuation: " . $valuation . "\n";
