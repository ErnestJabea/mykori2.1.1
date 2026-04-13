<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionSupplementaire;
use Illuminate\Support\Facades\DB;

$userName = "ESONO OYANA ANGELICA M.";
$user = User::where('name', 'LIKE', '%' . $userName . '%')->first();

if (!$user) {
    echo "Utilisateur '$userName' non trouvé.\n";
    exit(1);
}

echo "Utilisateur trouvé : " . $user->name . " (ID: " . $user->id . ")\n";

DB::beginTransaction();

try {
    // 1. Supprimer les mouvements financiers (PMG) liés aux transactions de l'utilisateur
    $txIds = Transaction::where('user_id', $user->id)->pluck('id')->toArray();
    $suppTxIds = TransactionSupplementaire::where('user_id', $user->id)->pluck('id')->toArray();
    $allTxIds = array_merge($txIds, $suppTxIds);

    if (!empty($allTxIds)) {
        $fmDeleted = DB::table('financial_movements')->whereIn('transaction_id', $allTxIds)->delete();
        echo "- $fmDeleted Mouvements Financiers (PMG) supprimés.\n";
    }

    // 2. Supprimer les mouvements FCP (fcp_movements)
    $fcpDeleted = DB::table('fcp_movements')->where('user_id', $user->id)->delete();
    echo "- $fcpDeleted Mouvements FCP supprimés.\n";

    // 3. Supprimer les transactions supplémentaires
    $suppDeleted = TransactionSupplementaire::where('user_id', $user->id)->delete();
    echo "- $suppDeleted Transactions Supplémentaires supprimées.\n";

    // 4. Supprimer les transactions principales
    $mainDeleted = Transaction::where('user_id', $user->id)->delete();
    echo "- $mainDeleted Transactions Principales supprimées.\n";

    DB::commit();
    echo "Toutes les transactions de $userName ont été supprimées avec succès.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "Erreur lors de la suppression : " . $e->getMessage() . "\n";
}
