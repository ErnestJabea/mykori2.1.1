<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\UserActivityLog;

class TransactionAuditObserver
{
    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction)
    {
        $dirty = $transaction->getDirty();

        if (isset($dirty['status'])) {
            $oldStatus = $transaction->getOriginal('status');
            $newStatus = $transaction->status;

            UserActivityLog::log(
                "MISE_A_JOUR_STATUT_TRANSACTION",
                $transaction,
                "Changement de statut de '{$oldStatus}' vers '{$newStatus}'"
            );
        }

        if (isset($dirty['is_compliance_validated']) && $dirty['is_compliance_validated']) {
            UserActivityLog::log("VALIDATION_COMPLIANCE", $transaction, "Transaction validée par le profil Compliance");
        }

        if (isset($dirty['is_backoffice_validated']) && $dirty['is_backoffice_validated']) {
            UserActivityLog::log("VALIDATION_BACKOFFICE", $transaction, "Transaction validée par le profil Backoffice");
        }
        
        if (isset($dirty['is_dg_validated']) && $dirty['is_dg_validated']) {
            UserActivityLog::log("VALIDATION_DG", $transaction, "Transaction validée par la Direction Générale");
        }
    }
}
