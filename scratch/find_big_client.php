<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$t = DB::table('transactions')
    ->where('amount', '>', 50000000)
    ->get();

foreach($t as $row) {
    $u = App\Models\User::find($row->user_id);
    echo "Client: " . ($u->name ?? "Unknown") . " (ID: {$row->user_id}) | Amount: {$row->amount} | Date: {$row->date_validation}\n";
}
