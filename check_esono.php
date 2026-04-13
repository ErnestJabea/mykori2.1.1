<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

$user = User::where('name', 'LIKE', '%ESONO%')->first();
if (!$user) {
    die("Utilisateur non trouvé\n");
}

echo "Client: " . $user->name . " (ID: " . $user->id . ")\n";
echo "--- TRANSACTIONS ---\n";
$trans = Transaction::where('user_id', $user->id)->get();
foreach ($trans as $t) {
    echo "ID: {$t->id} | Brut: {$t->amount} | Frais: {$t->fees} | Statut: {$t->status} | Ref: {$t->ref}\n";
}

echo "\n--- MOUVEMENTS FCP ---\n";
$mvts = DB::table('fcp_movements')->where('user_id', $user->id)->get();
foreach ($mvts as $m) {
    echo "Date: {$m->date_operation} | Net: {$m->amount_xaf} | Frais: {$m->fees} | Parts: {$m->nb_parts_change}\n";
}
