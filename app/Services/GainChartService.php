<?php
use App\Models\User;
use App\Product;
use App\Transaction;
use App\AssetValue;
use App\TransactionSupplementaire;
use Carbon\Carbon;

class GainChartService
{
    public function calculateUserGain(User $user)
    {
        // Initialiser le total des gains de l'utilisateur
        $totalGain = 0;

        // Récupérer toutes les transactions réussies de l'utilisateur, à la fois dans la table principale et dans la table supplémentaire
        $transactions = Transaction::where('user_id', $user->id)
            ->where('status', 'Succès')
            ->get();

        $additionalTransactions = TransactionSupplementaire::where('user_id', $user->id)
            ->where('status', 'Succès')
            ->get();



        // Parcourir les transactions principales
        foreach ($transactions as $transaction) {
            $product = Product::find($transaction->product_id);
            if ($transaction->date_validation && Carbon::parse($transaction->date_validation)->isPast()) {
                // Calculer le gain en fonction de la formule appropriée pour ce type de produit et ajouter au total
                $totalGain += $this->calculateTransactionGain($product->products_category_id, $transaction);
            }
        }

        // Parcourir les transactions supplémentaires
        foreach ($additionalTransactions as $additionalTransaction) {
            $product_ = Product::find($transaction->product_id);
            if ($additionalTransaction->date_validation && Carbon::parse($additionalTransaction->date_validation)->isPast()) {
                // Calculer le gain en fonction de la formule appropriée pour ce type de produit et ajouter au total
                $totalGain += $this->calculateTransactionGain($product_->products_category_id, $additionalTransaction);
            }
        }

        return $totalGain;
    }

    private function calculateTransactionGain($productType, $transaction)
    {
        // Calculer le gain en fonction du type de produit
        switch ($productType) {
            case '2':
                // Formule pour les produits PMG
                $gain = ($transaction->amount * (1 + ($transaction->vl_buy / 100)) * $this->calculateDays($transaction)) / 360;
                break;
            case '1':
                // Formule pour les produits FCP
                $currentNAV = $this->getCurrentNAV($transaction->product_id);
                $gain = $transaction->nb_part * $currentNAV;

                break;
            default:
                // Si le type de produit n'est pas défini, le gain est de 0
                $gain = 0;
        }

        dd($gain);

        return $gain;
    }

    private function calculateDays($transaction)
    {
        if ($transaction->date_validation) {
            // Calculer le nombre de jours entre la date de validation et la date actuelle
            $validationDate = Carbon::parse($transaction->date_validation);
            dd($validationDate);
            $currentDate = Carbon::now();
            return $validationDate->diffInDays($currentDate);
        } else {
            // Si la date de validation n'est pas définie, retourner null ou une autre valeur par défaut
            return null; // Ou return 0; ou toute autre valeur par défaut selon vos besoins
        }
    }

    public function getCurrentNAV($productId)
    {
        // Implémentez la logique pour obtenir la valeur liquidative actuelle du produit avec l'ID $productId
        $currentNAV = AssetValue::where('product_id', $productId)->latest()->value('vl');
        return $currentNAV;
    }
}
