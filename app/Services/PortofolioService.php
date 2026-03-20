<?php

namespace App\Services;

use App\AssetValue;
use App\Models\User;
use App\Transaction;
use Carbon\Carbon;

class PortofolioService
{
    public function calculatePortfolio(User $user)
    {

        $portfolio = 0;

        // Récupérer toutes les transactions réussies de l'utilisateur
        $transactions = $user->transactions()->where('status', 'Succès')->get();

        foreach ($transactions as $transaction) {
            // Calculer le montant en fonction du type de produit
            if ($transaction->product_id === 2) {
                $portfolio += $this->calculatePMGAmount($transaction);
            } elseif ($transaction->product_id === 1) {
                $portfolio += $this->calculateFCPAmount($transaction);
            }
        }

        return $portfolio;
    }



    public function calculatePMGAmount(Transaction $transaction)
    {
        $days = Carbon::now()->diffInDays($transaction->date_validation);

        // Calculer le montant pour le produit PMG
        $amount = ($transaction->amount * (1 + ($transaction->nb_part / 100)) * $days) / 360;

        return $amount;
    }

    public function calculateFCPAmount(Transaction $transaction)
    {
        // Récupérer la valeur liquidative actuelle
        $currentNAV = $this->getCurrentNAV($transaction->product_id);

        // Calculer le montant pour le produit FCP
        $amount = $transaction->nb_part * $currentNAV;

        return $amount;
    }

    private function calculatePMGMonthlyGain($initialAmount, $interestRate, $months)
    {
        // Calcul du taux d'intérêt mensuel à partir du taux annuel en pourcentage
        $monthlyRate = ($interestRate / 100);


        $taux_mensuel = $monthlyRate / 12;

        // Initialisation du portefeuille avec le montant initial investi
        $portfolio = $initialAmount;
        //dd($taux_mensuel);

        // Boucle pour chaque mois
        for ($i = 0; $i < $months; $i++) {
            // Calcul de l'intérêt pour le mois en cours
            $interest = $portfolio * $taux_mensuel;
            // Ajout de l'intérêt au portefeuille (intérêt composé)
            $portfolio += $interest;
        }


        //dd($portfolio);

        // Calcul du gain total
        $gain = $portfolio - $initialAmount;

        // Retour du gain arrondi à deux décimales
        return round($gain, 2);
    }

    // Méthode pour obtenir la valeur liquidative actuelle
    public function getCurrentNAV($productId)
    {
        // Implémentez la logique pour obtenir la valeur liquidative actuelle du produit avec l'ID $productId
        $currentNAV = AssetValue::where('product_id', $productId)->latest()->value('vl');
        return $currentNAV;
    }


    public function calculateGain(User $user)
    {
        $totalInvestment = 0;

        // Récupérer toutes les transactions réussies de l'utilisateur
        $transactions = $user->transactions()->where('status', 'Succès')->get();

        // Calculer la somme totale des montants investis dans toutes les transactions réussies
        foreach ($transactions as $transaction) {
            $totalInvestment += $transaction->amount;
        }

        // Calculer le portefeuille actuel
        $portfolio = $this->calculatePortfolio($user);

        // Calculer le gain
        $gain = $portfolio - $totalInvestment;

        return $gain;
    }
}
