<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$t = DB::table('transactions')->where('amount', 10000000)->get();
foreach($t as $row) {
    $u = App\Models\User::find($row->user_id);
    echo "User: " . ($u->name ?? "ID ".$row->user_id) . " | ID: {$row->user_id} | Status: {$row->status} | Prod: {$row->product_id}\n";
}

$s = DB::table('transaction_supplementaires')->where('amount', 10000000)->get();
foreach($s as $row) {
    $u = App\Models\User::find($row->user_id);
    echo "[SUPP] User: " . ($u->name ?? "ID ".$row->user_id) . " | ID: {$row->user_id} | Status: {$row->status} | Prod: {$row->product_id}\n";
}
