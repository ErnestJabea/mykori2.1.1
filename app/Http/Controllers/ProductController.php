<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionSupplementaire;
use App\Models\AssetValue;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use DateTime;
use Illuminate\Support\Facades\Session;
use App\Models\FinancialMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;


class ProductController extends Controller
{
    public function calculateFCPGain($vl_actuel, $transaction)
    {
        $totalInvested = $transaction->amount;
        $montantTotal = $transaction->nb_part * $vl_actuel;
        $gain = max(0, round($montantTotal - $totalInvested, 2));
        return $gain;
    }



    /**
     * Calcule la valorisation FCP basée sur les mouvements réels (parts)
     * Prend en compte les rachats et rajouts via la table fcp_movements
     */
    public function getFcpPortfolioValue($userId, $productId, $dateReference)
    {
        $nbPartsTotal = DB::table('fcp_movements')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('date_operation', '<=', $dateReference)
            ->sum('nb_parts_change') ?? 0;

        $latestVl = AssetValue::where('product_id', $productId)
            ->where('date_vl', '<=', $dateReference)
            ->orderBy('date_vl', 'desc')
            ->first();

        $vl = $latestVl ? (float)$latestVl->vl : 0;

        return [
            'parts' => (float)$nbPartsTotal,
            'vl' => $vl,
            'valorisation' => (float)$nbPartsTotal * $vl
        ];
    }

    public function calculateFCPGainWeek($vl_actuel, $transaction)
    {
        $totalInvested = $transaction->amount;
        $montantTotal = $transaction->nb_part * $vl_actuel;
        $gain = max(0, round($montantTotal - $totalInvested, 2));
        return $gain / 7;
    }


    public function calculatePMGGain($vl_buy, $transaction)
    {
        $totalInvested = $transaction->amount;
        $currentDate = Carbon::now();
        $daysDifference = Carbon::parse($transaction->date_validation)->diffInDays($currentDate) - 1;
        $rate = ($vl_buy / 100) / 360; // Supposons que vl_buy est le taux d'intérêt annuel
        $rate_invested = $totalInvested * $rate;
        //dd($rate_invested_without_days = $totalInvested + $rate_invested);
        return $totalInvested + $rate_invested;
    }

    public function calculatePMGGainWeek($vl_buy, $transaction)
    {
        $totalInvested = $transaction->amount;
        $currentDate = Carbon::now();
        $daysDifference = Carbon::parse($transaction->date_validation)->diffInDays($currentDate);
        $rate = $vl_buy / 100 / 52; // Supposons que vl_buy est le taux d'intérêt annuel
        $gain = ($totalInvested + $totalInvested * $rate) * $daysDifference;
        return $gain / 7;
    }

    public function getProductsWithGains()
    {
        return $this->getProductsWithGainsUserClient(Auth::id());
    }


    
    /**
     * Version spécifique Client pour l'affichage Dashboard
     * Utilise un calcul d'intérêt linéaire (Base 360) pour s'aligner sur l'existant
     */
    public static function getProductsWithGainsUserClientStatic($userId)
    {
        return (new self())->getProductsWithGainsUserClient($userId);
    }

    public function getProductsWithGainsUserClient($userId)
    {
        $currentDate = Carbon::now();
        $productIds = Transaction::where('user_id', $userId)
            ->where('status', 'Succès')
            ->distinct()
            ->pluck('product_id')
            ->toArray();

        $pmgResult = [];

        foreach ($productIds as $productId) {
            $product = Product::find($productId);
            if (!$product || $product->products_category_id != 2) continue;

            $transactions = Transaction::where('user_id', $userId)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            $additionalTransactions = TransactionSupplementaire::where('user_id', $userId)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            $allPmgTrans = $transactions->merge($additionalTransactions);

            foreach ($allPmgTrans as $transaction) {
                // Pour les transactions supplémentaires, la date d'échéance et de validation 
                // peuvent venir de la transaction parente si non définies sur la ligne.
                $dateEcheanceRaw = $transaction->date_echeance ?? ($transaction->transaction ? $transaction->transaction->date_echeance : null);
                
                if (!$dateEcheanceRaw) continue;

                $dateEcheance = Carbon::parse($dateEcheanceRaw);
                if ($dateEcheance->lt($currentDate)) continue;

                $amount = (float)$transaction->amount;
                // $soulte was historically montant_initiale, which holds garbage/interest values!
                // We must use $amount for the initial investment.
                
                $totalPaidOut = DB::table('financial_movements')
                    ->where('transaction_id', $transaction->id)
                    ->whereIn('type', ['rachat_partiel', 'precompte_interets'])
                    ->sum('amount');
                    
                // Find effective base capital for compound interest calculation
                $lastMovement = DB::table('financial_movements')
                    ->where('transaction_id', $transaction->id)
                    ->whereIn('type', ['capitalisation_interets', 'rachat_partiel'])
                    ->where('date_operation', '<=', $currentDate->toDateString())
                    ->orderBy('date_operation', 'desc')
                    ->first();
                $baseCapital = $lastMovement ? (float)$lastMovement->capital_after : $amount;

                // Utilisation de la version Client simplifiée (Linéaire)
                $totalValo = $this->calculatePMGValorizationClient($transaction, $currentDate);

                $pmgResult[] = [
                    'id' => $product->id,
                    'product_id' => $product->id,
                    'product_name' => $product->title,
                    'type_product' => 2,
                    'capital_investi' => $amount, // Vrai capital de départ
                    'capital_actuel' => $baseCapital, // Capital après capitalisation
                    'montant_transaction' => $amount, // Vrai capital pour les calculs de portfolio
                    'interets_generes' => (float)($totalValo + $totalPaidOut) - $amount,
                    'gain_month' => (float)$totalValo - $baseCapital, // Intérêts générés DEPUIS la dernière capitalisation
                    'soulte' => $baseCapital, // Capital capitalisé
                    'portfolio_valeur' => (float)$totalValo,
                    'total_payouts' => (float)$totalPaidOut,
                    'vl_actuel' => $transaction->vl_buy,
                    'vl_achat' => $transaction->vl_buy,
                    'date_echeance' => $dateEcheanceRaw,
                    'souscription' => $transaction->date_validation ?? $transaction->created_at->toDateString(),
                    'slug' => $product->slug,
                    'days_months' => $this->calculateMonthsAndDaysBetweenDates($transaction->date_validation ?? $transaction->created_at->toDateString(), $dateEcheanceRaw),
                    'gain_mensuel' => ($baseCapital * (($transaction->vl_buy / 100) / 12)), // Intérêt basé sur capital composé
                ];
            }
        }

        // Transformer le format FCP pour la compatibilité avec les vues existantes
        // (Identique à getProductsWithGainsUser)
        $service = new \App\Services\InvestmentService();
        $fcpPortfolio = $service->getConsolidatedFcpPortfolio($userId);
        
        $fcpResult = array_map(function($p) {
            return [
                'id' => $p['product_id'],
                'product_id' => $p['product_id'],
                'product_name' => $p['name'],
                'type_product' => 1,
                'montant_transaction' => $p['total_invested'],
                'capital_investi' => $p['total_invested'],
                'total_gains_fcp' => $p['total_gain'],
                'gain_semaine_fcp' => $p['weekly_gain'],
                'portfolio_valeur' => $p['valuation'],
                'nb_part' => $p['total_parts'],
                'pru' => $p['pru'],
                'vl_achat' => $p['current_vl'],
                'vl_actuel' => $p['current_vl'],
                'slug' => $p['slug'],
                'date_echeance' => Carbon::now()->addYears(10)->toDateString(),
                'souscription' => Carbon::now()->toDateString()
            ];
        }, $fcpPortfolio);

        return array_merge($fcpResult, $pmgResult);
    }

    /**
     * Calcul de valorisation PMG Client
     * Réplique EXCTEMENT la logique Asset Manager pour éviter les écarts
     */
    public function calculatePMGValorizationClient($trans, $refDate)
    {
        $targetDate = Carbon::parse($refDate)->min(Carbon::parse($trans->date_echeance));
        $rate = (float)$trans->vl_buy / 100;

        // 1. On cherche le capital effectif à la date cible (ignore les capitalisations futures)
        $lastMovement = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->whereIn('type', ['capitalisation_interets', 'rachat_partiel'])
            ->where('date_operation', '<=', $targetDate->toDateString())
            ->orderBy('date_operation', 'desc')
            ->first();

        $baseCapital = $lastMovement ? (float)$lastMovement->capital_after : (float)$trans->amount;
        $startDate = $lastMovement ? Carbon::parse($lastMovement->date_operation) : Carbon::parse($trans->date_validation);

        // 2. Calcul des intérêts courus (Base 360 avec prorata début/fin de mois)
        $totalInterest = 0;
        if ($targetDate->gt($startDate)) {
            $nextMonth = $startDate->copy()->addMonthNoOverflow()->startOfMonth();

            if ($targetDate->lt($nextMonth)) {
                $totalInterest = ($baseCapital * $rate * $startDate->diffInDays($targetDate)) / 360;
            } else {
                $totalInterest = ($baseCapital * $rate * $startDate->diffInDays($startDate->copy()->endOfMonth())) / 360;
                $fullMonths = $nextMonth->diffInMonths($targetDate->copy()->addDay());
                $totalInterest += ($baseCapital * ($rate / 12)) * $fullMonths;
                $lastMonthStart = $nextMonth->copy()->addMonths($fullMonths);
                if ($lastMonthStart->lt($targetDate)) {
                    $totalInterest += ($baseCapital * $rate * $lastMonthStart->diffInDays($targetDate)) / 360;
                }
            }
        }

        $precompte = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->where('type', 'precompte_interets')
            ->value('amount') ?? 0;

        return round(($baseCapital - $precompte) + $totalInterest, 0);
    }

    /**
     * Ancienne version linéaire conservée pour référence
     */
    public function calculatePMGValorizationClientLinear($trans, $refDate)
    {
        $targetDate = Carbon::parse($refDate)->min(Carbon::parse($trans->date_echeance));
        $rate = (float)$trans->vl_buy / 100;
        
        $lastMovement = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->whereIn('type', ['capitalisation_interets', 'rachat_partiel'])
            ->where('date_operation', '<=', $targetDate->toDateString())
            ->orderBy('date_operation', 'desc')
            ->first();

        $baseCapital = $lastMovement ? (float)$lastMovement->capital_after : (float)$trans->amount;
        $startDate = $lastMovement ? Carbon::parse($lastMovement->date_operation) : Carbon::parse($trans->date_validation);

        $totalInterest = 0;
        if ($targetDate->gt($startDate)) {
            $days = $startDate->diffInDays($targetDate);
            $totalInterest = ($baseCapital * $rate * $days) / 360;
        }

        $precompte = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->where('type', 'precompte_interets')
            ->value('amount') ?? 0;

        return round(($baseCapital - $precompte) + $totalInterest, 0);
    }

    public function getProductsWithGainsUser($user_id)
    {
        $service = new \App\Services\InvestmentService();
        
        // Synchroniser si nécessaire (optionnel, mais utile lors de la transition)
        $service->syncFcpMovements();
        
        $fcpPortfolio = $service->getConsolidatedFcpPortfolio($user_id);
        
        // On récupère aussi les PMG car cette méthode semble être utilisée par GainCalculationService pour les deux
        $products = Product::where('products_category_id', 2)->get();
        $pmgResult = [];
        $currentDate = Carbon::now();

        foreach ($products as $product) {
            $transactions = Transaction::where('user_id', $user_id)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            $additionalTransactions = TransactionSupplementaire::where('user_id', $user_id)
                ->where('status', 'Succès')
                ->where('product_id', $product->id)
                ->get();

            $allPmgTrans = $transactions->merge($additionalTransactions);

            foreach ($allPmgTrans as $transaction) {
                // Pour les transactions supplémentaires, la date d'échéance et de validation 
                // peuvent venir de la transaction parente si non définies sur la ligne.
                // Note: TransactionSupplementaire n'a pas forcément date_echeance en base.
                $dateEcheanceRaw = $transaction->date_echeance ?? ($transaction->transaction ? $transaction->transaction->date_echeance : null);
                
                if (!$dateEcheanceRaw) continue;

                $dateEcheance = Carbon::parse($dateEcheanceRaw);
                if ($dateEcheance->lt($currentDate)) continue;

                $amount = (float)$transaction->amount;
                
                $totalPaidOut = DB::table('financial_movements')
                    ->where('transaction_id', $transaction->id)
                    ->whereIn('type', ['rachat_partiel', 'precompte_interets'])
                    ->sum('amount');
                    
                // Find effective base capital for compound interest calculation
                $lastMovement = DB::table('financial_movements')
                    ->where('transaction_id', $transaction->id)
                    ->whereIn('type', ['capitalisation_interets', 'rachat_partiel'])
                    ->where('date_operation', '<=', $currentDate->toDateString())
                    ->orderBy('date_operation', 'desc')
                    ->first();
                $baseCapital = $lastMovement ? (float)$lastMovement->capital_after : $amount;

                // calculatePMGValorization gère les deux types d'objets car ils ont les champs amount, vl_buy, etc.
                $totalValo = $this->calculatePMGValorization($transaction, $currentDate);

                $pmgResult[] = [
                    'id' => $product->id,
                    'product_id' => $product->id,
                    'product_name' => $product->title,
                    'type_product' => 2,
                    'capital_investi' => $amount, // Vrai capital de départ (amount direct de transaction)
                    'capital_actuel' => $baseCapital, // Capital composé actuel
                    'montant_transaction' => $amount, // Legacy support pour calcul gains totaux
                    'interets_generes' => (float)($totalValo + $totalPaidOut) - $amount,
                    'gain_month' => (float)$totalValo - $baseCapital, // Intérêts du cycle actuel
                    'soulte' => $baseCapital, // Capital capitalisé
                    'portfolio_valeur' => (float)$totalValo,
                    'total_payouts' => (float)$totalPaidOut,
                    'vl_actuel' => $transaction->vl_buy,
                    'vl_achat' => $transaction->vl_buy,
                    'date_echeance' => $dateEcheanceRaw,
                    'souscription' => $transaction->date_validation ?? $transaction->created_at->toDateString(),
                    'slug' => $product->slug,
                    'days_months' => $this->calculateMonthsAndDaysBetweenDates($transaction->date_validation ?? $transaction->created_at->toDateString(), $dateEcheanceRaw),
                    'gain_mensuel' => ($baseCapital * (($transaction->vl_buy / 100) / 12)), // Intérêt basé sur capital composé
                ];
            }
        }

        // Transformer le format FCP pour la compatibilité avec les vues existantes
        $fcpResult = array_map(function($p) {
            return [
                'id' => $p['product_id'],
                'product_id' => $p['product_id'],
                'product_name' => $p['name'],
                'type_product' => 1,
                'montant_transaction' => $p['total_invested'],
                'capital_investi' => $p['total_invested'],
                'total_gains_fcp' => $p['total_gain'],
                'gain_semaine_fcp' => $p['weekly_gain'],
                'portfolio_valeur' => $p['valuation'], // Nouveau nom
                'valorisation_portefeuille_fcp' => $p['valuation'], // Legacy
                'nb_part' => $p['total_parts'],
                'pru' => $p['pru'], // Nouveau: Prix de Revient Unitaire
                'vl_achat' => $p['first_vl'], // Utiliser la VL initiale effective
                'vl_actuel' => $p['current_vl'],
                'slug' => $p['slug'],
                'date_echeance' => Carbon::now()->addYears(10)->toDateString(),
                'souscription' => $p['first_subscription_date'] ?? Carbon::now()->toDateString()
            ];
        }, $fcpPortfolio);

        return array_merge($fcpResult, $pmgResult);
    }

    public function getProductsWithGainsPieChart()
    {
        $userId = Auth::id();
        $allProducts = $this->getProductsWithGainsUserClient($userId);
        
        $productGains = [];
        $grouped = collect($allProducts)->groupBy('product_name');

        foreach ($grouped as $name => $items) {
            $totalGain = 0;
            foreach ($items as $item) {
                if ($item['type_product'] == 2) { // PMG
                    $totalGain += $item['interets_generes'];
                } else { // FCP
                    $totalGain += $item['total_gains_fcp'];
                }
            }
            $productGains[] = [
                'product_name' => $name,
                'total_gain' => round($totalGain, 0)
            ];
        }

        return $productGains;
    }

    public function calculatePMGMonthlyGainPerDay($initialAmount, $interestRate, $specificDate, $transactionDate)
    {
        // Conversion du taux d'intérêt annuel en taux journalier
        $dailyRate_ = (float) $interestRate / 100;

        $dailyRate = $dailyRate_ / 360;
        $portfolio = $initialAmount; // Portefeuille initial
        $cumulative = 0; // Cumul des intérêts pour le mois en cours

        // Calcul des timestamps pour les dates fournies
        $transactionTimestamp = strtotime($transactionDate);
        $specificTimestamp = strtotime($specificDate);
        $currentTimestamp = strtotime(date('Y-m-d')); // Date actuelle

        $interet_ = 0;


        // Vérification si la date actuelle est avant ou à la date spécifique
        if ($currentTimestamp <= $specificTimestamp) {
            // Calcul du nombre de jours écoulés depuis la date de transaction jusqu'à la date actuelle
            $elapsedDays = max(($currentTimestamp - $transactionTimestamp) / 86400, 0);

            // Calcul du début du mois en cours
            $startOfMonthTimestamp = strtotime(date('Y-m-01', $currentTimestamp));

            // Mise à jour du portefeuille pour chaque jour écoulé
            for ($i = 0; $i < $elapsedDays; $i++) {
                $interest = $portfolio * $dailyRate; // Intérêt pour le jour en cours
                $interet_ += $interest; // Mise à jour du portefeuille

                //error_log("la valorisation jour  ".$i.": ".$interest);

                // Si le jour est dans le mois en cours, ajouter l'intérêt au cumul
                if ($transactionTimestamp + ($i * 86400) >= $startOfMonthTimestamp) {
                    $cumulative += $interest;
                }
            }


            // Préparation du résultat
            $result = [
                'valo_pf' => round($interet_, 2),
                'cummul_interet' => round($cumulative, 2),
            ];
        } else {
            // Si la date actuelle dépasse la date spécifique, retourner 0 pour le portefeuille
            $result = [
                'valo_pf' => 0,
                'cummul_interet' => 0,
            ];
        }

        return $result;
    }
    /**
     * Calcul dynamique avec capitalisation annuelle automatique
     */
    public function calculatePMGMonthlyGain($initialAmount, $annualRate, $startDate, $endDate, $currentDate)
    {
        // 1. On part du dernier mouvement réel enregistré en base
        $lastMovement = DB::table('financial_movements')
            ->where('transaction_id', $transactionId)
            ->where('date_operation', '<=', $currentDate)
            ->orderBy('date_operation', 'desc')
            ->first();

        $currentCapital = $lastMovement ? (float)$lastMovement->capital_after : (float)$initialAmount;
        $dateCursor = Carbon::parse($lastMovement ? $lastMovement->date_operation : $startDate);
        $targetDate = Carbon::parse($currentDate)->min(Carbon::parse($endDate));

        // 2. Boucle de capitalisation annuelle automatique
        while ($dateCursor->copy()->addYear()->lte($targetDate)) {
            $nextAnniversary = $dateCursor->copy()->addYear();
            $daysInYear = $dateCursor->diffInDays($nextAnniversary); // Souvent 365 ou 366

            // Calcul et ajout au capital (Capitalisation)
            $yearInterest = ($currentCapital * ($annualRate / 100) * $daysInYear) / 360;
            $currentCapital += $yearInterest;
            $dateCursor = $nextAnniversary;
        }

        // 3. Calcul des intérêts pour la période résiduelle (moins d'un an)
        $remainingDays = $dateCursor->diffInDays($targetDate);
        $finalInterest = ($currentCapital * ($annualRate / 100) * $remainingDays) / 360;

        return round($currentCapital + $finalInterest, 0);
    }




    public function countUserProducts($userId)
    {

        // Compter le nombre de produits distincts dans la table transactions
        $countFromTransactions = Transaction::where('user_id', $userId)
            ->where('status', 'Succès')
            ->distinct('product_id')
            ->count('product_id');

        // Compter le nombre de produits distincts dans la table transaction_supplementaire
        $countFromSupplementaryTransactions = TransactionSupplementaire::where('user_id', $userId)
            ->distinct('product_id')
            ->where('status', 'Succès')
            ->count('product_id');

        // Ajouter les deux comptes ensemble pour obtenir le total
        $totalProductCount = $countFromTransactions + $countFromSupplementaryTransactions;

        return $totalProductCount;
    }



    public function getTodaysDate()
    {
        return date('Y-m-d');
    }

    public function gainMonthPmg($initialAmount, $annuelInteret)
    {
        return ($initialAmount * (($annuelInteret / 100) / 12));
    }


    private function calculateDaysFromMonths($months, $transactionDate)
    {
        // Obtention du timestamp de la date de transaction
        $transactionTimestamp = strtotime($transactionDate);

        // Calcul du timestamp de la fin du mois en cours
        $currentMonthEndTimestamp = strtotime(date('Y-m-t', $transactionTimestamp));

        // Calcul du nombre de jours dans le mois de la transaction
        $daysInTransactionMonth = date('t', $transactionTimestamp);

        // Calcul du nombre de jours restants dans la période spécifiée (en mois complets)
        $remainingDays = $months * 30.4;

        // Calcul du nombre total de jours
        $totalDays = $daysInTransactionMonth + $remainingDays;

        // Si la date de transaction est après le dernier jour du mois, ajuster le nombre de jours
        if ($transactionTimestamp > $currentMonthEndTimestamp) {
            $totalDays += $daysInTransactionMonth; // Ajouter le nombre de jours du mois suivant
        }

        return $totalDays;
    }

    private function calculateMonthsAndDaysBetweenDates($startDate, $endDate)
    {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = $start->diff($end);
        return ['months' => $interval->m + ($interval->y * 12), 'days' => $interval->d];
    }

    public function showProductsWithGains()
    {

        $productsWithGains = $this->getProductsWithGains();
        $user = Auth::user();
        return view('front-end.my-products', compact('productsWithGains', 'user'));
    }


    public function showProductGain($slug)
    {

        $product = Product::where('slug', $slug)->first();
        $result = $this->getProductsWithGains();
        return view('front-end.product-detail-gain', compact('result', 'product'));
    }



    public function CalculDateEcheance($startDate, $monthsToAdd)
    {
        // Convertir la date de départ en instance de Carbon
        $startDate = Carbon::parse($startDate);

        // Ajouter les mois à la date de départ
        $endDate = $startDate->copy()->addMonths($monthsToAdd);

        // Obtenir la date actuelle
        $currentDate = Carbon::now();

        $status_duree = "";
        // Comparer la date actuelle avec la date finale
        if ($currentDate->lessThan($endDate)) {
            // Afficher l'information si la date actuelle est inférieure à la date finale
            $status_duree = 1;
            return $status_duree;
        } else {
            // Sinon, ne rien afficher ou afficher une autre information
            $status_duree = 0;
            return $status_duree;
        }
    }




    public function indexAssetManager()
    {
        $customers = User::where('role_id', '2')
            ->with(['transactions' => fn($q) => $q->where('status', 'Succès'), 
                    'transactionssupplementaires' => fn($q) => $q->where('status', 'Succès')])
            ->orderBy('created_at', 'desc')
            ->get();
        $currentDate = Carbon::now();
        $globalAum = 0;
        $globalTotalInvested = 0;
        $activeClientsCount = 0;
        $totalFcpAum = 0;
        $totalPmgAum = 0;

        foreach ($customers as $customer) {
            $customerTotalInvesti = 0;
            $totalValorization = 0;
            $allTrans = $customer->transactions->concat($customer->transactionssupplementaires);

            foreach ($allTrans as $trans) {
                $dateEcheance = Carbon::parse($trans->date_echeance);
                if ($dateEcheance->gte($currentDate)) {
                    $principalInitial = (float)($trans->amount);
                    $globalTotalInvested += $principalInitial;
                    $customerTotalInvesti += $principalInitial;

                    if ($trans->product->products_category_id == 2) {
                        $valo = (float)$this->calculatePMGValorization($trans, $currentDate);
                        $totalValorization += $valo;
                        $totalPmgAum += $valo;
                    } else {
                        $fcpData = $this->getFcpPortfolioValue($customer->id, $trans->product_id, $currentDate);
                        $totalValorization += (float)$fcpData['valorisation'];
                        $totalFcpAum += (float)$fcpData['valorisation'];
                    }
                }
            }

            if ($customerTotalInvesti > 0) {
                $activeClientsCount++;
            }
            $globalAum += $totalValorization;

            $customer->portefeuille_total = $totalValorization;
            $customer->product_count = $allTrans->count();
        }

        $globalTotalInterests = max(0, $globalAum - $globalTotalInvested);
        $fcpProductsList = Product::where('products_category_id', 1)
            ->where('status', 1)
            ->get();
        foreach ($fcpProductsList as $product) {
            $product->vl_history = AssetValue::where('product_id', $product->id)
                ->orderBy('created_at', 'asc')
                ->take(12)
                ->get();
        }
        return view('front-end.asset-manager', [
            'customers' => $customers,
            'globalAum' => $globalAum,
            'globalTotalInvested' => $globalTotalInvested,
            'globalTotalInterests' => $globalTotalInterests,
            'activeClientsCount' => $activeClientsCount,
            'fcpProducts' => $fcpProductsList,
            'totalFcpAum' => $totalFcpAum,
            'totalPmgAum' => $totalPmgAum
        ]);
    }

    /**
     * Calcule la valorisation PMG en intercalant mouvements réels et anniversaires théoriques
     *
     */
    public function calculatePMGValorization($trans, $refDate)
    {
    $targetDate = Carbon::parse($refDate)->min(Carbon::parse($trans->date_echeance));
    $rate = (float)$trans->vl_buy / 100;

    // 1. On cherche le capital effectif à la date cible (ignore les capitalisations futures)
    $lastMovement = DB::table('financial_movements')
        ->where('transaction_id', $trans->id)
        ->whereIn('type', ['capitalisation_interets', 'rachat_partiel'])
        ->where('date_operation', '<=', $targetDate->toDateString())
        ->orderBy('date_operation', 'desc')
        ->first();

    $baseCapital = $lastMovement ? (float)$lastMovement->capital_after : (float)$trans->amount;
    $startDate = $lastMovement ? Carbon::parse($lastMovement->date_operation) : Carbon::parse($trans->date_validation);

    // 2. Calcul des intérêts courus (Base 360)
    $totalInterest = 0;
    if ($targetDate->gt($startDate)) {
        $nextMonth = $startDate->copy()->addMonthNoOverflow()->startOfMonth();

        if ($targetDate->lt($nextMonth)) {
            $totalInterest = ($baseCapital * $rate * $startDate->diffInDays($targetDate)) / 360;
        } else {
            $totalInterest = ($baseCapital * $rate * $startDate->diffInDays($startDate->copy()->endOfMonth())) / 360;
            $fullMonths = $nextMonth->diffInMonths($targetDate->copy()->addDay());
            $totalInterest += ($baseCapital * ($rate / 12)) * $fullMonths;
            $lastMonthStart = $nextMonth->copy()->addMonths($fullMonths);
            if ($lastMonthStart->lt($targetDate)) {
                $totalInterest += ($baseCapital * $rate * $lastMonthStart->diffInDays($targetDate)) / 360;
            }
        }
    }

    $precompte = DB::table('financial_movements')
        ->where('transaction_id', $trans->id)
        ->where('type', 'precompte_interets')
        ->value('amount') ?? 0;

    // Valorisation = (Capital à l'instant T - Précompte) + Intérêts courus du cycle
    return round(($baseCapital - $precompte) + $totalInterest, 0);
}
    //backup de la fonction de valorisation PMG avant refonte complète
    /* public function calculatePMGValorization($trans, $refDate)
    {
        $dateEcheance = Carbon::parse($trans->date_echeance);
        $targetDate = Carbon::parse($refDate)->min($dateEcheance);
        $rate = (float)$trans->vl_buy / 100;

        // 1. RECHERCHE DU DERNIER MOUVEMENT (Pivot de Capitalisation)
        // On cherche si une capitalisation a eu lieu AVANT ou à la date cible
        $lastMovement = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->where('date_operation', '<=', $targetDate->toDateString())
            ->orderBy('date_operation', 'desc')
            ->first();

        if ($lastMovement) {
            // Le capital est déjà mis à jour (ex: 21 400 000)
            $baseCapital = (float)$lastMovement->capital_after;
            $startDate = Carbon::parse($lastMovement->date_operation);
        } else {
            // On est encore sur le capital initial (ex: 20 000 000)
            $baseCapital = (float)$trans->amount;
            $startDate = Carbon::parse($trans->date_validation);
        }

        if ($startDate->gt($targetDate)) return round($baseCapital, 0);

        $totalInterest = 0;
        $nextMonth = $startDate->copy()->addMonthNoOverflow()->startOfMonth();

        // 2. LOGIQUE DE CALCUL HYBRIDE (Base 360)

        // Cas A : On est dans le mois de la signature (ou le mois de la capitalisation)
        if ($targetDate->lt($nextMonth)) {
            $days = $startDate->diffInDays($targetDate);
            $totalInterest = ($baseCapital * $rate * $days) / 360;
        }
        // Cas B : On a franchi au moins un premier mois civil
        else {
            // 1. Prorata du mois de départ (ex: du 23 au 31 = 8 jours)
            $daysInFirstMonth = $startDate->diffInDays($startDate->copy()->endOfMonth());
            $totalInterest = ($baseCapital * $rate * $daysInFirstMonth) / 360;

            // 2. Mois pleins (Forfait 1/12)
            $fullMonths = $nextMonth->diffInMonths($targetDate->copy()->addDay());
            $totalInterest += ($baseCapital * ($rate / 12)) * $fullMonths;

            // 3. Prorata du mois final (si la targetDate est en cours de mois)
            $lastMonthStart = $nextMonth->copy()->addMonths($fullMonths);
            if ($lastMonthStart->lt($targetDate)) {
                $days = $lastMonthStart->diffInDays($targetDate);
                $totalInterest += ($baseCapital * $rate * $days) / 360;
            }
        }

        return round($baseCapital + $totalInterest, 0);
    }
 */
    /**
     * Prépare les données consolidées pour la vue Customer
     */
    public function customers(Request $request)
    {
        $search = $request->input('search');
        $currentDate = Carbon::now();

        // 1. Base query pour les clients (Role 2)
        $query = User::where('role_id', '2')
            ->with(['transactions' => function($q) {
                $q->where('status', 'Succès');
            }, 'transactionssupplementaires' => function($q) {
                $q->where('status', 'Succès');
            }]);

        // 2. Recherche
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // 3. Calcul des statistiques globales AVANT pagination (sur tous les résultats filtrés)
        $allMatchedCustomers = $query->get();
        $globalTotalAum = 0;
        $globalTotalInvesti = 0;
        $activeClientsCount = 0;
        $inactiveClientsCount = 0;

        foreach ($allMatchedCustomers as $cust) {
            $customerTotalInvesti = 0;
            $allTrans = $cust->transactions->concat($cust->transactionssupplementaires);
            
            foreach ($allTrans as $trans) {
                $dateEcheance = Carbon::parse($trans->date_echeance);
                if ($dateEcheance->gte($currentDate)) {
                    $principalInitial = (float)($trans->amount);
                    $globalTotalInvesti += $principalInitial;
                    $customerTotalInvesti += $principalInitial;

                    if ($trans->product->products_category_id == 2) {
                        $globalTotalAum += $this->calculatePMGValorization($trans, $currentDate);
                    } else {
                        $fcpData = $this->getFcpPortfolioValue($cust->id, $trans->product_id, $currentDate);
                        $globalTotalAum += $fcpData['valorisation'];
                    }
                }
            }

            if ($customerTotalInvesti > 0) {
                $activeClientsCount++;
            } else {
                $inactiveClientsCount++;
            }
        }

        $globalTotalInterets = max(0, $globalTotalAum - $globalTotalInvesti);

        // 4. Pagination
        $customers = $query->orderBy('name', 'asc')->paginate(10);

        // 5. Calcul des stats pour les clients de la PAGE COURANTE
        foreach ($customers as $customer) {
            $totalInvestiActive = 0;
            $totalValorisationActive = 0;
            $activeContractsCount = 0;

            $allTrans = $customer->transactions->concat($customer->transactionssupplementaires);

            foreach ($allTrans as $trans) {
                $dateEcheance = Carbon::parse($trans->date_echeance);

                if ($dateEcheance->gte($currentDate)) {
                    $activeContractsCount++;
                    $principalInitial = (float)($trans->amount);
                    $totalInvestiActive += $principalInitial;

                    if ($trans->product->products_category_id == 2) {
                        $totalValorisationActive += $this->calculatePMGValorization($trans, $currentDate);
                    } else {
                        $fcpData = $this->getFcpPortfolioValue($customer->id, $trans->product_id, $currentDate);
                        $totalValorisationActive += $fcpData['valorisation'];
                    }
                }
            }

            $customer->total_capital = $totalInvestiActive;
            $customer->portefeuille_total = $totalValorisationActive;
            $customer->total_interets = max(0, $totalValorisationActive - $totalInvestiActive);
            $customer->product_count = $activeContractsCount;
        }

        return view('front-end.customer', compact('customers', 'globalTotalAum', 'globalTotalInvesti', 'globalTotalInterets', 'search', 'activeClientsCount', 'inactiveClientsCount'));
    }
    function generateUniqueCode($user)
    {
        return strtoupper(substr(md5($user->id . $user->name . $user->created_at), 0, 10));
    }

    public function getMaskedName($user)
    {
        return strtoupper(
            substr(md5($user->id . $user->name . $user->created_at), 0, 10)
        );
    }



    /** 
     * Détail Client : Valorisation précise et statistiques
     */
    public function customersDetail($customer_id)
    {
        $customer = User::findOrFail($customer_id);
        $currentDate = Carbon::now();

        // Récupération des produits avec gains (via la fonction que nous avons déjà harmonisée)
        $allProducts = $this->getProductsWithGainsUser($customer_id);

        // Initialisation des compteurs pour les boîtes de statistiques
        $totalInvestiActive = 0;
        $portefeuillePMG = 0;
        $portefeuilleFCP = 0;

        foreach ($allProducts as $item) {
            // On ne cumule que le capital des produits actifs
            $totalInvestiActive += $item['capital_investi'];

            if ($item['type_product'] == 2) { // PMG
                $portefeuillePMG += $item['portfolio_valeur'];
            } else { // FCP
                $portefeuilleFCP += $item['portfolio_valeur'];
            }
        }

        $portefeuilleTotal = $portefeuillePMG + $portefeuilleFCP;

        // ✅ Calcul des intérêts : (Valeur actuelle + Sorties) - Capital initial total
        $totalPmgPayouts = collect($allProducts)->where('type_product', 2)->sum('total_payouts');
        $totalInterets = max(0, ($portefeuilleTotal + $totalPmgPayouts) - $totalInvestiActive);

        // Récupération de tous les produits pour le formulaire de placement
        $products = Product::orderBy('created_at', 'desc')->where('nb_action', '>', 0)->get();
        foreach ($products as $product) {
            if ($product->products_category_id == 1) { // FCP
                $lastVl = AssetValue::where('product_id', $product->id)->orderBy('date_vl', 'desc')->first();
                $product->recent_vl = $lastVl ? $lastVl->vl : $product->vl;
            } else {
                $product->recent_vl = $product->vl;
            }
        }

        $categories = \App\Models\ProductsCategory::all();

        $availableMonthsRaw = $this->getAvailableStatementMonths($customer->id);
        
        // Pagination manuelle
        $request = request();
        $page = $request->get('page', 1);
        $perPage = 5;
        $offset = ($page * $perPage) - $perPage;
        
        $availableMonths = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($availableMonthsRaw, $offset, $perPage, true),
            count($availableMonthsRaw),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('front-end.customer-detail', [
            'customer' => $customer,
            'productsWithGains' => $allProducts,
            'portefeuille_total' => $portefeuilleTotal,
            'portefeuille_pmg' => $portefeuillePMG,
            'portefeuille_fcp' => $portefeuilleFCP,
            'total_interets' => $totalInterets,
            'total_investi' => $totalInvestiActive,
            'products' => $products,
            'categories' => $categories,
            'availableMonths' => $availableMonths
        ]);
    }
    

/*     public function downloadStatement($transaction_id)
    {
        $transaction = Transaction::with(['user', 'product'])->findOrFail($transaction_id);
        
        // Récupérer l'historique complet trié par date
        $movements = FinancialMovement::where('transaction_id', $transaction_id)
                        ->orderBy('date_operation', 'asc')
                        ->get();

        $pdf = Pdf::loadView('front-end.releves.releve-history', compact('transaction', 'movements'));
        
        return $pdf->download("releve_{$transaction->ref}.pdf");
    } */
    /**
     * Téléchargement du relevé historique
     * Distingue automatiquement le format FCP (Parts) du PMG (Cash)
     */
    public function downloadStatement($transaction_id)
    {
        $transaction = Transaction::with(['user', 'product'])->findOrFail($transaction_id);

        if ($transaction->product->products_category_id == 2) {
            // Relevé PMG : Historique des flux financiers (capitalisation/rachats)
            $movements = FinancialMovement::where('transaction_id', $transaction_id)
                ->orderBy('date_operation', 'asc')
                ->get();
            $view = 'front-end.releves.releve-history';
        } else {
            // Relevé FCP : Historique des parts
            $movements = DB::table('fcp_movements')
                ->where('transaction_id', $transaction_id)
                ->orderBy('date_operation', 'asc')
                ->get();
            $view = 'front-end.releves.releve-history-fcp';
        }

        $pdf = Pdf::loadView($view, [
            'transaction' => $transaction,
            'movements' => $movements,
            'client' => $transaction->user
        ]);

        return $pdf->download("releve_{$transaction->ref}.pdf");
    }




    /**
     * Fonction de simulation et de diagnostic pour les logs
     * @param string $dateRef Format 'Y-m-d' (ex: '2026-01-31')
     */
    public function debugClientPortfolios($dateRef)
    {
        $targetDate = Carbon::parse($dateRef);
        $customers = User::where('role_id', '2')->get();

        Log::channel('single')->info("=== DÉBUT SIMULATION KORI - DATE : $dateRef ===");

        foreach ($customers as $customer) {
            $transactions = Transaction::where('user_id', $customer->id)
                ->where('status', 'Succès')
                ->where('date_validation', '<=', $targetDate)
                ->get();

            if ($transactions->isEmpty()) continue;

            Log::channel('single')->info("CLIENT : {$customer->name} (ID: {$customer->id})");

            foreach ($transactions as $trans) {
                $dateEcheance = Carbon::parse($trans->date_echeance);
                $isEchu = $dateEcheance->lt($targetDate);

                // Analyse des mouvements financiers
                $movements = DB::table('financial_movements')
                    ->where('transaction_id', $trans->id)
                    ->where('date_operation', '<=', $targetDate)
                    ->get();

                $hasRachatPartiel = $movements->where('type', 'rachat_partiel')->isNotEmpty();
                $hasRachatTotal = $movements->where('type', 'rachat_total')->isNotEmpty();
                $hasCapitalisation = $movements->where('type', 'capitalisation_interets')->isNotEmpty();
                $hasAjout = $movements->where('type', 'versement_complementaire')->isNotEmpty();
                $hasPrecompte = $movements->where('type', 'precompte_interets')->isNotEmpty();

                // ✅ CORRECTION ICI : On passe l'objet $trans entier et la date cible
                $valorisation = 0;
                if ($trans->product->products_category_id == 2) {
                    $valorisation = $this->calculatePMGValorization($trans, $targetDate);
                } else {
                    $fcp = $this->getFcpPortfolioValue($customer->id, $trans->product_id, $targetDate);
                    $valorisation = $fcp['valorisation'];
                }

                // Calcul du capital investi réel (incluant les versements/rachats historiques)
                // Pour le log, on compare à l'investissement initial de la transaction
                $interets = $valorisation - $trans->amount;

                $logMsg = sprintf(
                    "  - Produit: %s | ID Trans: %s | Status: %s\n" .
                        "    Initial: %s | Valo à Date: %s | Intérêts: %s\n" .
                        "    Détails: [Rachat Partiel: %s][Rachat Total: %s] [Capit.: %s] [Ajout: %s] [Précompte: %s]",
                    $trans->product->title,
                    $trans->id,
                    $isEchu ? "ÉCHU" : "ACTIF",
                    number_format($trans->amount, 0, '.', ' '),
                    number_format($valorisation, 0, '.', ' '),
                    number_format($interets, 0, '.', ' '),
                    $hasRachatPartiel ? "OUI" : "NON",
                    $hasRachatTotal ? "OUI" : "NON",
                    $hasCapitalisation ? "OUI" : "NON",
                    $hasAjout ? "OUI" : "NON",
                    $hasPrecompte ? "OUI" : "NON"
                );

                Log::channel('single')->info($logMsg);
            }
            Log::channel('single')->info("-------------------------------------------");
        }

        Log::channel('single')->info("=== FIN DE SIMULATION ===");
        return "Simulation terminée. Consultez storage/logs/laravel.log";
    }

    /**
     * Synchronise les capitalisations pour toutes les transactions (existantes et futures)
     *
     */
    /* public function syncAnniversaryMovements()
    {
        // 1. Récupérer TOUTES les transactions de type PMG (Catégorie 2) validées
        $transactions = Transaction::where('status', 'Succès')
            ->whereHas('product', function ($q) {
                $q->where('products_category_id', 2);
            })->get();

        $today = Carbon::now();
        $syncReport = [];

        foreach ($transactions as $trans) {
            // Date de départ pour le calcul des anniversaires
            $startDate = Carbon::parse($trans->date_validation);
            $dateEcheance = Carbon::parse($trans->date_echeance);

            // On définit la limite de calcul (soit aujourd'hui, soit l'échéance si elle est passée)
            $limitDate = $today->copy()->min($dateEcheance);

            $cursor = $startDate->copy()->addYear();

            // 2. Boucle sur chaque année possible du contrat
            while ($cursor->lte($limitDate)) {
                $dateAnniversaire = $cursor->toDateString();

                // Vérifier si une capitalisation existe déjà à cette date précise pour cette transaction
                $alreadyExists = DB::table('financial_movements')
                    ->where('transaction_id', $trans->id)
                    ->where('type', 'capitalisation_interets')
                    ->whereDate('date_operation', $dateAnniversaire)
                    ->exists();

                if (!$alreadyExists) {
                    // Calcul de la valorisation exacte à cette date anniversaire
                    $valeurPortefeuille = $this->calculatePMGValorization($trans, $cursor);

                    // Récupération du capital juste avant cet anniversaire
                    $capitalAvant = DB::table('financial_movements')
                        ->where('transaction_id', $trans->id)
                        ->where('date_operation', '<', $dateAnniversaire)
                        ->orderBy('date_operation', 'desc')
                        ->value('capital_after') ?? $trans->amount;

                    $montantInteret = $valeurPortefeuille - $capitalAvant;

                    // 3. Insertion de la ligne de capitalisation manquante
                    DB::table('financial_movements')->insert([
                        'transaction_id' => $trans->id,
                        'date_operation' => $cursor->toDateTimeString(),
                        'type' => 'capitalisation_interets',
                        'amount'         => $montantInteret,
                        'capital_before' => $capitalAvant,
                        'capital_after'  => $valeurPortefeuille,
                        'comments'       => "Capitalisation automatique - Anniversaire " . $startDate->diffInYears($cursor) . " an(s)",
                        'created_at'     => now(),
                        'updated_at'     => now()
                    ]);

                    $syncReport[] = "Transaction {$trans->id} : Anniversaire du {$dateAnniversaire} synchronisé.";
                }

                $cursor->addYear(); // Passage à l'année suivante
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => count($syncReport) . " mouvements synchronisés.",
            'details' => $syncReport
        ]);
    } */

    public function syncAnniversaryMovements()
    {
        // On ne récupère que les produits PMG actifs
        $transactions = Transaction::where('status', 'Succès')
            ->whereHas('product', function ($q) {
                $q->where('products_category_id', 2);
            })->get();

        $today = Carbon::now();

        foreach ($transactions as $trans) {
            // Point de départ : 1 an après la validation
            $anniversary = Carbon::parse($trans->date_validation)->addYear();

            while ($anniversary->lte($today)) {
                $formattedDate = $anniversary->toDateString();

                // ✅ Sécurité : Normaliser l'anniversaire à minuit pour la comparaison
                $anniversaryMidnight = $anniversary->copy()->startOfDay();

                $exists = DB::table('financial_movements')
                    ->where('transaction_id', $trans->id)
                    ->where('type', 'capitalisation_interets')
                    // On vérifie sur la date uniquement
                    ->whereDate('date_operation', $formattedDate)
                    ->exists();

                if (!$exists) {
                    try {
                        // ✅ Utilise votre nouvelle fonction de calcul hybride
                        $valeurPortefeuille = $this->calculatePMGValorization($trans, $anniversaryMidnight);

                        // Récupération du capital juste avant cet anniversaire
                        $capitalAvant = DB::table('financial_movements')
                            ->where('transaction_id', $trans->id)
                            ->where('date_operation', '<', $anniversaryMidnight)
                            ->orderBy('date_operation', 'desc')
                            ->value('capital_after') ?? (float)$trans->amount;

                        $interetAdd = $valeurPortefeuille - $capitalAvant;

                        // On ne crée un mouvement que si l'intérêt est positif
                        if ($interetAdd > 0) {
                            DB::table('financial_movements')->insert([
                                'transaction_id' => $trans->id,
                                'user_id'        => $trans->user_id,
                                'date_operation' => $anniversaryMidnight->toDateTimeString(),
                                'type'           => 'capitalisation_interets',
                                'amount'         => $interetAdd,
                                'capital_before' => $capitalAvant,
                                'capital_after'  => $valeurPortefeuille,
                                'comments'       => 'Capitalisation automatique anniversaire ' . $anniversary->diffInYears(Carbon::parse($trans->date_validation)) . ' an(s)',
                                'created_at'     => now(),
                                'updated_at'     => now()
                            ]);

                            // ✅ Optionnel : Mettre à jour le montant principal de la transaction pour le suivi rapide
                            $trans->update(['amount' => $valeurPortefeuille]);
                        }

                        Log::info("SYNC OK : Trans {$trans->id} capitalisée pour le {$formattedDate}");
                    } catch (\Exception $e) {
                        Log::error("SYNC FAIL Trans {$trans->id} : " . $e->getMessage());
                    }
                }
                $anniversary->addYear(); // Passer à l'anniversaire suivant (si contrat de 2 ans ou plus)
            }
        }
    }

    public function getAvailableStatementMonths($userId)
    {
        Carbon::setLocale('fr');
        
        $firstTransaction = Transaction::where('user_id', $userId)
            ->where('status', 'Succès')
            ->orderBy('date_validation', 'asc')
            ->first();
            
        if (!$firstTransaction) {
            return [];
        }
        
        $start = Carbon::parse($firstTransaction->date_validation)->startOfMonth();
        $end = Carbon::now()->startOfMonth();
        
        $monthsList = [];
        $current = $start->copy();
        
        while ($current->lte($end)) {
            $dateFinMois = $current->copy()->endOfMonth();
            
            $has_pmg = Transaction::where('user_id', $userId)
                ->where('status', 'Succès')
                ->where('date_validation', '<=', $dateFinMois->toDateString())
                ->where('date_echeance', '>=', $current->toDateString())
                ->whereHas('product', function($q) {
                    $q->where('products_category_id', 2);
                })->exists();

            $has_fcp = Transaction::where('user_id', $userId)
                ->where('status', 'Succès')
                ->where('date_validation', '<=', $dateFinMois->toDateString())
                ->whereHas('product', function($q) {
                    $q->where('products_category_id', 1);
                })->exists();
                
            if (!$has_fcp) {
                $has_fcp = TransactionSupplementaire::where('user_id', $userId)
                    ->where('status', 'Succès')
                    ->where('created_at', '<=', $dateFinMois->toDateTimeString())
                    ->whereHas('product', function($q) {
                        $q->where('products_category_id', 1);
                    })->exists();
            }

            if ($has_pmg || $has_fcp) {
                $monthsList[] = [
                    'year' => $current->year,
                    'month' => $current->month,
                    'label' => ucfirst($current->translatedFormat('F Y')),
                    'has_pmg' => $has_pmg,
                    'has_fcp' => $has_fcp
                ];
            }
            $current->addMonth();
        }
        
        return array_reverse($monthsList);
    }

    public function myStatements(Request $request)
    {
        $monthsRaw = $this->getAvailableStatementMonths(Auth::id());
        
        $page = $request->get('page', 1);
        $perPage = 10;
        $offset = ($page * $perPage) - $perPage;
        
        $months = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($monthsRaw, $offset, $perPage, true),
            count($monthsRaw),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        return view('front-end.my-statements', compact('months'));
    }

    public function getAvailableMonthsApi($customer_id)
    {
        if (Auth::user()->role_id == 2) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $months = $this->getAvailableStatementMonths($customer_id);
        return response()->json($months);
    }

    /**
     * Retourne la VL d'un produit à une date donnée (ou la plus proche précédente)
     */
    public function getVlAtDate($productId, $date)
    {
        $vlRecord = AssetValue::where('product_id', $productId)
            ->where('date_vl', '<=', $date)
            ->orderBy('date_vl', 'desc')
            ->first();

        if (!$vlRecord) {
            // Si aucune VL n'existe avant cette date, on prend la plus ancienne disponible
            $vlRecord = AssetValue::where('product_id', $productId)
                ->orderBy('date_vl', 'asc')
                ->first();
        }

        $vl = $vlRecord ? (float)$vlRecord->vl : (float)Product::find($productId)->vl;

        return response()->json([
            'vl' => $vl,
            'date_vl' => $vlRecord ? $vlRecord->date_vl : null,
            'status' => 'success'
        ]);
    }

    public function getUserStats($userId)
    {
        $productsWithGains = $this->getProductsWithGainsUser($userId);
        
        $portefeuille_fcp = 0;
        $portefeuille_pmg = 0;
        $total_interets = 0;
        $total_invested = 0;

        foreach ($productsWithGains as $p) {
            $total_invested += (float)($p['capital_investi'] ?? 0);
            if ($p['type_product'] == 1) { // FCP
                $portefeuille_fcp += (float)$p['portfolio_valeur'];
                $total_interets += (float)($p['total_gains_fcp'] ?? 0);
            } else { // PMG
                $portefeuille_pmg += (float)$p['portfolio_valeur'];
                $total_interets += (float)($p['interets_generes'] ?? 0);
            }
        }

        $portefeuille_total = $portefeuille_fcp + $portefeuille_pmg;

        return [
            'total_invested' => $total_invested,
            'total_portfolio' => $portefeuille_total,
            'total_gains' => $total_interets,
            'fcp_portfolio' => $portefeuille_fcp,
            'pmg_portfolio' => $portefeuille_pmg
        ];
    }

    public function downloadMonthlyStatement($year, $month, $type, $targetUserId = null)
    {
        // Si targetUserId est fourni, on vérifie que l'utilisateur connecté n'est pas un simple client
        if ($targetUserId && Auth::user()->role_id != 2) {
            $client = User::findOrFail($targetUserId);
        } else {
            $client = Auth::user();
        }
        $dateN = Carbon::create($year, $month, 1)->endOfMonth();
        $dateN1 = Carbon::create($year, $month, 1)->subMonth()->endOfMonth(); // Dernier jour du mois précédent
        Carbon::setLocale('fr');
        $periodeLabel = ucfirst($dateN->translatedFormat('F Y'));

        if ($type == 'pmg') {
            // Logique copiée de ListeClientReleveController@previewPmg
            $transactions = Transaction::where('user_id', $client->id)
                ->where('status', 'Succès')
                ->where('date_validation', '<=', $dateN->toDateString())
                ->where('date_echeance', '>=', $dateN1->toDateString())
                ->whereHas('product', function($q) {
                    $q->where('products_category_id', 2);
                })->get();

            $produitsAffiches = [];
            $totalValoN = 0;
            $totalValoN1 = 0;

            foreach ($transactions as $trans) {
                $valoN = $this->calculatePMGValorization($trans, $dateN);
                $valoN1 = $this->calculatePMGValorization($trans, $dateN1);

                $precompte = DB::table('financial_movements')
                    ->where('transaction_id', $trans->id)
                    ->where('type', 'precompte_interets')
                    ->value('amount') ?? 0;

                $capNetInitial = (float)$trans->amount - (float)$precompte;
                $dateVal = Carbon::parse($trans->date_validation);
                $estProduitJeune = $dateVal->gt($dateN1) ? 1 : 0;

                $mvtCap = DB::table('financial_movements')
                    ->where('transaction_id', $trans->id)
                    ->where('type', 'capitalisation_interets')
                    ->whereBetween('date_operation', [$dateN1->copy()->addDay()->toDateString(), $dateN->toDateString()])
                    ->first();

                if ($mvtCap) {
                    $dateCap = Carbon::parse($mvtCap->date_operation);
                    $joursAvant = $dateN1->diffInDays($dateCap->copy()->subDay());
                    $joursApres = $dateCap->diffInDays($dateN);
                    $gainAvant = ($mvtCap->capital_before * ($trans->vl_buy/100) * $joursAvant) / 360;
                    $gainApres = ($mvtCap->capital_after * ($trans->vl_buy/100) * $joursApres) / 360;
                    $gainMensuel = $gainAvant + $gainApres;
                    $affichageValoN1 = $valoN - $gainMensuel;
                } else {
                    $gainMensuel = $valoN - $valoN1;
                    $affichageValoN1 = $valoN1;
                }

                if ($estProduitJeune) {
                    $gainMensuel = $valoN - $capNetInitial;
                    $affichageValoN1 = $capNetInitial;
                }

                $totalValoN += $valoN;
                $totalValoN1 += $affichageValoN1;

                $produitsAffiches[] = (object)[
                    'nom' => $trans->product->title,
                    'capital' => (float)$trans->amount,
                    'taux' => $trans->vl_buy,
                    'valo_n' => $valoN,
                    'valo_n1' => $affichageValoN1,
                    'gain_mensuel' => max(0, round($gainMensuel, 0)),
                    'gain_total' => max(0, $valoN - $capNetInitial),
                    'souscription' => $dateVal->format('d/m/Y'),
                    'date_echeance' => Carbon::parse($trans->date_echeance)->format('d/m/Y'),
                    'produit_jeune' => $estProduitJeune,
                ];
            }

            $pdf = Pdf::loadView('front-end.releves.releve-preview', [
                'client' => $client,
                'produits' => $produitsAffiches,
                'valorisation_courante' => $totalValoN,
                'valorisation_precedente' => $totalValoN1,
                'date_releve' => $dateN->format('d/m/Y'),
                'date_releve_precedent' => $dateN1->format('d/m/Y'),
                'periode' => $periodeLabel
            ]);

            return $pdf->download("releve_pmg_{$year}_{$month}.pdf");

        } else {
            // Logique FCP copiée de ListeClientReleveController@previewFcp
            $service = new \App\Services\InvestmentService();
            $productIds = DB::table('fcp_movements')
                ->where('user_id', $client->id)
                ->distinct()
                ->pluck('product_id');

            $produitsAffiches = [];
            $totalValoN = 0;
            $totalValoN1 = 0;

            foreach ($productIds as $productId) {
                $product = Product::find($productId);
                if (!$product) continue;

                $partsN = DB::table('fcp_movements')
                    ->where('user_id', $client->id)
                    ->where('product_id', $productId)
                    ->where('date_operation', '<=', $dateN->toDateString())
                    ->sum('nb_parts_change');

                $partsN1 = DB::table('fcp_movements')
                    ->where('user_id', $client->id)
                    ->where('product_id', $productId)
                    ->where('date_operation', '<=', $dateN1->toDateString())
                    ->sum('nb_parts_change');

                $vlN = AssetValue::where('product_id', $productId)->where('date_vl', '<=', $dateN->toDateString())->orderBy('date_vl', 'desc')->value('vl') ?? (float)$product->vl;
                $vlN1 = AssetValue::where('product_id', $productId)->where('date_vl', '<=', $dateN1->toDateString())->orderBy('date_vl', 'desc')->value('vl') ?? (float)$product->vl;

                // On récupère aussi la VL d'achat initiale effective
                $firstMvt = DB::table('fcp_movements')
                    ->where('user_id', $client->id)
                    ->where('product_id', $productId)
                    ->orderBy('date_operation', 'asc')
                    ->orderBy('id', 'asc')
                    ->first();
                
                $vlSouscription = $firstMvt ? (float)$firstMvt->vl_applied : (float)$product->vl;

                $valoN = $partsN * $vlN;
                $valoN1 = $partsN1 * $vlN1;
                
                // Capital investi réel (Parts * VL d'achat)
                $investi = $partsN * $vlSouscription;

                $totalValoN += $valoN;
                $totalValoN1 += $valoN1;

                $produitsAffiches[] = (object)[
                    'nom' => $product->title,
                    'parts' => $partsN,
                    'parts_n1' => $partsN1,
                    'vl_n' => (float)$vlN,
                    'vl_n1' => (float)$vlN1,
                    'vl_souscription' => $vlSouscription,
                    'valo_n' => (float)$valoN,
                    'valo_n1' => (float)$valoN1,
                    'gain_mensuel' => $valoN - $valoN1,
                    'gain_total' => $valoN - $investi,
                    'investi' => $investi,
                    'souscription' => $firstMvt ? $firstMvt->date_operation : null
                ];
            }

            $pdf = Pdf::loadView('front-end.releves.releve-preview-fcp', [
                'client' => $client,
                'produits' => $produitsAffiches,
                'valorisation_courante' => $totalValoN,
                'valorisation_precedente' => $totalValoN1,
                'date_releve' => $dateN->format('d/m/Y'),
                'date_releve_precedent' => $dateN1->format('d/m/Y'),
                'periode' => $periodeLabel
            ]);

            return $pdf->download("releve_fcp_{$year}_{$month}.pdf");
        }
    }

    public function downloadHistoryStatement($targetUserId = null)
    {
        if ($targetUserId && Auth::user()->role_id != 2) {
            $user = User::findOrFail($targetUserId);
        } else {
            $user = Auth::user();
        }
        $userId = $user->id;

        $transactions = Transaction::where('user_id', $userId)
            ->where('status', 'Succès')
            ->orderBy('date_validation', 'desc')
            ->get();

        $txIds = $transactions->pluck('id');
        $financialMovements = DB::table('financial_movements')
            ->whereIn('transaction_id', $txIds)
            ->orderBy('date_operation', 'desc')
            ->get()
            ->map(function ($m) use ($transactions) {
                $tx = $transactions->firstWhere('id', $m->transaction_id);
                $productTitle = $tx
                    ? optional(Product::find($tx->product_id))->title
                    : 'PMG';
                return (object)[
                    'date'    => $m->date_operation,
                    'libelle' => strtoupper(str_replace('_', ' ', $m->type)),
                    'ref'     => $tx->ref ?? '-',
                    'produit' => $productTitle,
                    'montant' => (float)$m->amount,
                    'sens'    => in_array($m->type, ['rachat_partiel', 'rachat_total', 'precompte_interets', 'paiement_interets', 'remboursement']) ? 'sortant' : 'entrant',
                ];
            });

        $fcpMovements = DB::table('fcp_movements')
            ->where('user_id', $userId)
            ->orderBy('date_operation', 'desc')
            ->get()
            ->map(function ($m) {
                $productTitle = optional(Product::find($m->product_id))->title ?? 'FCP';
                $isIncoming   = $m->nb_parts_change >= 0;
                return (object)[
                    'date'    => $m->date_operation,
                    'libelle' => $isIncoming ? 'SOUSCRIPTION FCP' : 'RACHAT FCP',
                    'ref'     => $m->reference ?? '-',
                    'produit' => $productTitle,
                    'montant' => (float)$m->montant,
                    'sens'    => $isIncoming ? 'entrant' : 'sortant',
                ];
            });

        $officialTx = $transactions->map(function ($tx) {
            $productTitle = optional(Product::find($tx->product_id))->title ?? 'Produit';
            return (object)[
                'date'    => $tx->date_validation ?? $tx->created_at,
                'libelle' => $tx->title ?? 'SOUSCRIPTION',
                'ref'     => $tx->ref,
                'produit' => $productTitle,
                'montant' => (float)$tx->amount,
                'sens'    => 'entrant',
            ];
        });

        $allMovements = collect()
            ->merge($officialTx)
            ->merge($financialMovements)
            ->merge($fcpMovements)
            ->sortByDesc('date')
            ->values();

        $pdf = Pdf::loadView('front-end.releves.historique-transactions-pdf', [
            'user'         => $user,
            'allMovements' => $allMovements,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ]);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('historique_transactions_' . Carbon::now()->format('Y-m-d') . '.pdf');
    }

    /**
     * API pour récupérer l'historique d'évolution d'un produit FCP pour un utilisateur spécifique
     */
    public function getFcpEvolutionApi($productId, $customerId)
    {
        $user = \App\Models\User::findOrFail($customerId);
        $product = \App\Models\Product::findOrFail($productId);

        // 1. Récupérer tous les mouvements de parts pour ce couple (User, Produit)
        $movements = \Illuminate\Support\Facades\DB::table('fcp_movements')
            ->where('user_id', $customerId)
            ->where('product_id', $productId)
            ->orderBy('date_operation', 'asc')
            ->get();

        if ($movements->isEmpty()) {
            return response()->json(['history' => [], 'message' => 'Aucun mouvement trouvé']);
        }

        // 2. Récupérer l'évolution des VL depuis le tout premier mouvement
        $firstDate = $movements->first()->date_operation;
        $assetValues = \Illuminate\Support\Facades\DB::table('asset_values')
            ->where('product_id', $productId)
            ->where('date_vl', '>=', $firstDate)
            ->orderBy('date_vl', 'asc')
            ->get();

        // 3. Reconstruire la chronologie des valorisations
        $history = $assetValues->map(function ($vl) use ($movements) {
            $partsToDate = $movements->where('date_operation', '<=', $vl->date_vl)->sum('nb_parts_change');
            return [
                'date' => \Carbon\Carbon::parse($vl->date_vl)->format('d/m/Y'),
                'vl' => (float)$vl->vl,
                'parts' => (float)$partsToDate,
                'valuation' => round((float)$partsToDate * (float)$vl->vl, 0)
            ];
        });

        return response()->json([
            'product_name' => $product->title,
            'customer_name' => $user->name,
            'history' => $history
        ]);
    }
}
