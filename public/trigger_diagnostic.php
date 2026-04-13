<?php
require '../vendor/autoload.php';
$app = require_once '../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use App\Models\TransactionSupplementaire;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$current = Carbon::now();

// 1. MÉTHODE DG ACTUELLE (Toutes transactions validées - Tout l'historique)
$dgTotalInvested = DB::table('transactions')->where('status', 'Succès')->sum(DB::raw('amount + COALESCE(fees, 0)')) + 
           DB::table('transaction_supplementaires')->where('status', 'Succès')->sum(DB::raw('amount + COALESCE(fees, 0)'));

// 2. MÉTHODE ASSET MANAGER (Dossiers non expirés uniquement)
$amActiveInvested = 0;
$transactions = Transaction::where('status', 'Succès')->get();
foreach ($transactions as $t) {
    if ($t->date_echeance && Carbon::parse($t->date_echeance)->gte($current)) {
        $amActiveInvested += ($t->amount + ($t->fees ?? 0));
    }
}
$supps = TransactionSupplementaire::where('status', 'Succès')->get();
foreach ($supps as $t) {
    $parentDate = $t->date_echeance ?? ($t->transaction ? $t->transaction->date_echeance : null);
    if ($parentDate && Carbon::parse($parentDate)->gte($current)) {
        $amActiveInvested += ($t->amount + ($t->fees ?? 0));
    }
}

// 3. NOMBRE DE CLIENTS ACTIFS (Ayant au moins un dossier non expiré)
$activeUsersCount = User::where('role_id', '2')->get()->filter(function($u) use ($current) {
    foreach($u->transactions as $t) {
        if($t->status == 'Succès' && $t->date_echeance && Carbon::parse($t->date_echeance)->gte($current)) return true;
    }
    foreach($u->transactionssupplementaires as $t) {
        $d = $t->date_echeance ?? ($t->transaction ? $t->transaction->date_echeance : null);
        if($t->status == 'Succès' && $d && Carbon::parse($d)->gte($current)) return true;
    }
    return false;
})->count();

$totalUsersCount = User::where('role_id', '2')->count();
$inactiveUsersCount = $totalUsersCount - $activeUsersCount;

echo "--- COMPARAISON DES MÉTRIQUES ---\n";
echo "MÉTHODE DG (HISTORIQUE COMPLET)     : " . number_format($dgTotalInvested, 0, '.', ' ') . " XAF\n";
echo "MÉTHODE ASSET MANAGER (ACTIFS SEULS) : " . number_format($amActiveInvested, 0, '.', ' ') . " XAF\n";
echo "CLIENTS ACTIFS                     : " . $activeUsersCount . "\n";
echo "CLIENTS INACTIFS                   : " . $inactiveUsersCount . "\n";
echo "-------------------------------\n";
