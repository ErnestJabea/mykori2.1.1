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
        $daysDifference = Carbon::parse($transaction->date_validation)->diffInDays($currentDate) + 1;
        $rate = ($vl_buy / 100) / 360; // Supposons que vl_buy est le taux d'intérêt annuel
        $rate_invested = $totalInvested * $rate;
        //dd($rate_invested_without_days = $totalInvested + $rate_invested);
        return $totalInvested + $rate_invested;
    }

    public function calculatePMGGainWeek($vl_buy, $transaction)
    {
        $totalInvested = $transaction->amount;
        $currentDate = Carbon::now();
        $daysDifference = Carbon::parse($transaction->date_validation)->diffInDays($currentDate) + 1;
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
        // On renvoie exactement la même chose que l'Asset Manager pour garantir la parité
        return $this->getProductsWithGainsUser($userId);
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
            
            // FILTRE : On ne prend que les transactions non échues (ou sans date d'échéance)
            $allPmgTrans = $allPmgTrans->filter(function($t) use ($currentDate) {
                if (!$t->date_echeance) return true;
                return Carbon::parse($t->date_echeance)->startOfDay()->gte($currentDate->copy()->startOfDay());
            });

            if ($allPmgTrans->isEmpty()) continue;

            // Consolidation : Trouver la date de placement initial (la plus ancienne)
            $firstDate = $allPmgTrans->min('date_validation') ?? $allPmgTrans->min('created_at')->toDateString();
            $maxExpiry = $allPmgTrans->max('date_echeance'); // On prend l'échéance la plus lointaine du bloc
            
            $totalCapitalInvested = 0;
            $totalCurrentValuation = 0;
            $totalPayoutsSum = 0;
            $totalCapitalizedBonus = 0;
            
            foreach ($allPmgTrans as $transaction) {
                // Montant initial
                $amount = (float)$transaction->amount;
                $totalCapitalInvested += $amount;
                
                // Mouvements sur cette transaction spécifique (Paiements, Rachats, Précomptes)
                // IMPORTANT : Les transactions supplémentaires n'ont pas de mouvements financiers directs dans cette table
                $isSupplementaire = ($transaction instanceof \App\Models\TransactionSupplementaire);
                
                $payouts = 0;
                $capitalized = 0;

                if (!$isSupplementaire) {
                    $payouts = DB::table('financial_movements')
                        ->where('transaction_id', $transaction->id)
                        ->whereIn('type', ['rachat_partiel', 'precompte_interets', 'paiement_interets'])
                        ->sum('amount');

                    $capitalized = DB::table('financial_movements')
                        ->where('transaction_id', $transaction->id)
                        ->where('type', 'capitalisation_interets')
                        ->sum('amount');
                }
                
                $totalPayoutsSum += $payouts;
                $totalCapitalizedBonus += $capitalized;
                    
                // Valorisation individuelle incluant déjà capitalisation et rachats dans sa logique
                $totalValo = $this->calculatePMGValorization($transaction, $currentDate);
                $totalCurrentValuation += (float)$totalValo;
            }

            // Calcul global des gains (Différence entre valo actuelle + sorties et capital versé)
            $totalInterestsGenerated = ($totalCurrentValuation + $totalPayoutsSum) - $totalCapitalInvested;
            $consolidatedCapital = $totalCapitalInvested + $totalCapitalizedBonus;  
            $rate = (float)$allPmgTrans->first()->vl_buy / 100;

            $pmgResult[] = [
                'id' => $product->id,
                'product_id' => $product->id,
                'product_name' => $product->title,
                'type_product' => 2,
                'capital_investi' => $totalCapitalInvested, 
                'capital_actuel' => $consolidatedCapital, 
                'montant_transaction' => $totalCapitalInvested, 
                'interets_generes' => $totalInterestsGenerated,
                'gain_month' => $totalInterestsGenerated,
                'gain_mensuel' => ($consolidatedCapital * ($rate / 12)),
                'soulte' => $consolidatedCapital,
                'portfolio_valeur' => $totalCurrentValuation,
                'total_payouts' => $totalPayoutsSum,
                'vl_achat' => $allPmgTrans->first()->vl_buy,
                'vl_actuel' => $allPmgTrans->first()->vl_buy,
                'date_echeance' => $maxExpiry,
                'souscription' => $firstDate, // Date visuelle stable
                'slug' => $product->slug,
                'days_months' => $this->calculateMonthsAndDaysBetweenDates($firstDate, $maxExpiry),
            ];
        }

        // Transformer le format FCP pour la compatibilité avec les vues existantes
        $fcpResult = array_map(function($p) use ($user_id) {
            // Pour l'affichage BRUT (Net + Fees) cohérent avec la demande
            $prodId = $p['product_id'];
            $mainGross = DB::table('transactions')
                ->where('user_id', $user_id)->where('product_id', $prodId)->where('status', 'Succès')->sum('amount');
            $suppGross = DB::table('transaction_supplementaires')
                ->where('user_id', $user_id)->where('product_id', $prodId)->where('status', 'Succès')->sum('amount');
            $totalGross = (float)$mainGross + (float)$suppGross;

            return [
                'id' => $p['product_id'],
                'product_id' => $p['product_id'],
                'product_name' => $p['product_name'],
                'type_product' => 1,
                'montant_transaction' => $totalGross,
                'capital_investi' => $totalGross,
                'total_gains_fcp' => $p['total_gain'] ?? 0,
                'gain_semaine_fcp' => $p['weekly_gain'] ?? 0,
                'gain_month' => $p['total_gain'] ?? 0,
                'gain_mensuel' => $p['weekly_gain'] ?? 0,
                'portfolio_valeur' => $p['current_valuation'] ?? 0, // Nom normalisé
                'valorisation_portefeuille_fcp' => $p['current_valuation'] ?? 0, // Legacy
                'nb_part' => $p['total_parts'] ?? 0,
                'pru' => $p['pru'] ?? 0,
                'vl_achat' => $p['first_vl'] ?? 0,
                'vl_actuel' => $p['current_vl'] ?? 0,
                'date_vl_actuel' => $p['latest_vl_date'] ?? null,
                'slug' => $p['slug'] ?? '',
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
        if (!$start || !$end) return ['months' => 0, 'days' => 0];

        $interval = $start->diff($end);
        $months = ($interval->y * 12) + $interval->m;
        $days = $interval->d;

        // Human-friendly adjustment: if days are >= 28, it's effectively a full month for PMG duration display
        if ($days >= 28) {
            $months++;
            $days = 0;
        }

        return ['months' => $months, 'days' => $days];
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
            $userTotalInvestiGross = 0;
            $userTotalInvestiNet = 0;
            $totalValorization = 0;
            $allTrans = $customer->transactions->concat($customer->transactionssupplementaires);
            $processedFcpProducts = [];

            foreach ($allTrans as $trans) {
                if ($trans->status != 'Succès') continue;
                $dateEcheance = Carbon::parse($trans->date_echeance);
                if ($dateEcheance->gte($currentDate)) {
                    $principalInitial = (float)($trans->amount);
                    $fees = (float)($trans->fees ?? 0);
                    
                    $userTotalInvestiGross += $principalInitial;
                    $userTotalInvestiNet += ($principalInitial - $fees);

                    if ($trans->product->products_category_id == 2) {
                        $valo = (float)$this->calculatePMGValorization($trans, $currentDate);
                        $totalValorization += $valo;
                        $totalPmgAum += $valo;
                    } else {
                        if (!in_array($trans->product_id, $processedFcpProducts)) {
                            $fcpData = $this->getFcpPortfolioValue($customer->id, $trans->product_id, $currentDate);
                            $totalValorization += (float)$fcpData['valorisation'];
                            $totalFcpAum += (float)$fcpData['valorisation'];
                            $processedFcpProducts[] = $trans->product_id;
                        }
                    }
                }
            }

            if ($userTotalInvestiGross > 0) {
                $activeClientsCount++;
            }
            $globalTotalInvested += $userTotalInvestiGross;
            $globalAum += $totalValorization;

            $customer->total_capital = $userTotalInvestiGross;
            $customer->total_capital_net = $userTotalInvestiNet;
            $customer->portefeuille_total = $totalValorization;
            $customer->total_interets = max(0, $totalValorization - $userTotalInvestiNet);
            $customer->product_count = $allTrans->count();
            $customer->has_fcp = $customer->transactions->where('product.products_category_id', 1)->count() > 0;
        }

        $globalTotalInterests = 0;
        foreach ($customers as $customer) {
            $globalTotalInterests += $customer->total_interets;
        }

        $fcpProductsList = Product::where('products_category_id', 1)
            ->where('status', 1)
            ->get();
        foreach ($fcpProductsList as $product) {
            $product->vl_history = AssetValue::where('product_id', $product->id)
                ->orderBy('date_vl', 'desc')
                ->take(8)
                ->get()
                ->sortBy('date_vl');
        }
        $startOfMonth = $currentDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $currentDate->copy()->endOfMonth()->toDateString();

        $expiringPmgCount = Transaction::where('status', 'Succès')
            ->whereHas('product', fn($q) => $q->where('products_category_id', 2))
            ->whereBetween('date_echeance', [$startOfMonth, $endOfMonth])
            ->count();
            
        $expiringPmgSuppCount = TransactionSupplementaire::where('status', 'Succès')
            ->whereHas('product', fn($q) => $q->where('products_category_id', 2))
            ->whereBetween('date_echeance', [$startOfMonth, $endOfMonth])
            ->count();

        $totalExpiringPmgThisMonth = $expiringPmgCount + $expiringPmgSuppCount;

        $anniversaryPmgCount = Transaction::where('status', 'Succès')
            ->whereHas('product', fn($q) => $q->where('products_category_id', 2))
            ->whereMonth('date_validation', $currentDate->month)
            ->whereYear('date_validation', '<', $currentDate->year)
            ->count();
            
        $anniversaryPmgSuppCount = TransactionSupplementaire::where('status', 'Succès')
            ->whereHas('product', fn($q) => $q->where('products_category_id', 2))
            ->whereMonth('date_validation', $currentDate->month)
            ->whereYear('date_validation', '<', $currentDate->year)
            ->count();

        $totalAnniversariesThisMonth = $anniversaryPmgCount + $anniversaryPmgSuppCount;

        return view('front-end.asset-manager', [
            'customers' => $customers,
            'globalAum' => $globalAum,
            'globalTotalInvested' => $globalTotalInvested,
            'globalTotalInterests' => $globalTotalInterests,
            'activeClientsCount' => $activeClientsCount,
            'fcpProducts' => $fcpProductsList,
            'totalFcpAum' => $totalFcpAum,
            'totalPmgAum' => $totalPmgAum,
            'totalExpiringPmgThisMonth' => $totalExpiringPmgThisMonth,
            'totalAnniversariesThisMonth' => $totalAnniversariesThisMonth
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

    // 0. Sécurité Rachat Total
    // NOTE : Les transactions supplémentaires n'ont pas de mouvements financiers dans cette table
    $isSupplementaire = ($trans instanceof \App\Models\TransactionSupplementaire);
    
    $totalRedemption = false;
    $lastMovement = null;

    if (!$isSupplementaire) {
        $totalRedemption = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->where('type', 'rachat_total')
            ->where('date_operation', '<=', $targetDate->toDateString())
            ->exists();

        if ($totalRedemption) return 0;

        // 1. On cherche le capital effectif à la date cible (ignore les capitalisations futures)
        $lastMovement = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->whereIn('type', ['capitalisation_interets', 'rachat_partiel'])
            ->where('date_operation', '<=', $targetDate->toDateString())
            ->orderBy('date_operation', 'desc')
            ->first();
    }

    $baseCapital = $lastMovement ? (float)$lastMovement->capital_after : (float)$trans->amount;
    $startDate = $lastMovement ? Carbon::parse($lastMovement->date_operation) : Carbon::parse($trans->date_validation);

    // 2. Calcul des intérêts courus (Base 360)
    $totalInterest = 0;
    if ($targetDate->gt($startDate)) {
        $nextMonth = $startDate->copy()->addMonthNoOverflow()->startOfMonth();

        if ($targetDate->lt($nextMonth)) {
            // Jour de dépôt exclu
            $totalInterest = ($baseCapital * $rate * ($startDate->diffInDays($targetDate))) / 360;
        } else {
            // Jour de dépôt exclu pour le premier mois incomplet
            $totalInterest = ($baseCapital * $rate * ($startDate->diffInDays($startDate->copy()->endOfMonth()))) / 360;
            $fullMonths = $nextMonth->diffInMonths($targetDate->copy()->addDay());
            $totalInterest += ($baseCapital * ($rate / 12)) * $fullMonths;
            $lastMonthStart = $nextMonth->copy()->addMonths($fullMonths);
            if ($lastMonthStart->lt($targetDate)) {
                // Jour de retrait inclus pour le dernier mois
                $totalInterest += ($baseCapital * $rate * ($lastMonthStart->diffInDays($targetDate) + 1)) / 360;
            }
        }
    }

    $payouts = DB::table('financial_movements')
        ->where('transaction_id', $trans->id)
        ->whereIn('type', ['precompte_interets', 'paiement_interets'])
        ->where('date_operation', '<=', $targetDate->toDateString())
        ->sum('amount') ?? 0;

    // Valorisation = (Capital à l'instant T - (Somme des intérêts déjà payés/précomptés)) + Intérêts courus du cycle
    return round(($baseCapital - $payouts) + $totalInterest, 0);
}

    /**
     * Version simplifiée pour le Client (Calcul linéaire Base 360)
     * Utilisée pour l'affichage Dashboard pour garantir une réactivité maximale
     */
    public function calculatePMGValorizationClient($trans, $refDate)
    {
        $targetDate = Carbon::parse($refDate)->min(Carbon::parse($trans->date_echeance));
        $rate = (float)$trans->vl_buy / 100;
        
        if ($targetDate->lt(Carbon::parse($trans->date_validation))) {
            return 0;
        }

        $amount = (float)$trans->amount;
        $startDate = Carbon::parse($trans->date_validation);
        
        $days = $startDate->diffInDays($targetDate) + 1;
        $interest = ($amount * $rate * $days) / 360;
        
        return round($amount + $interest, 0);
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
     * Supporte le tri et le filtrage par catégorie (FCP/PMG)
     */
    public function customers(Request $request)
    {
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'name');
        $order = $request->input('order', 'asc');
        $categoryFilter = $request->input('category', 'all'); // 'all', '1' (FCP), '2' (PMG)
        $currentDate = Carbon::now();

        // 1. Base query pour les clients (Role 2)
        $usersQuery = User::where('role_id', '2')
            ->with(['transactions' => function($q) {
                $q->where('status', 'Succès');
            }, 'transactionssupplementaires' => function($q) {
                $q->where('status', 'Succès');
            }, 'transactions.product', 'transactionssupplementaires.product']);

        // 2. Recherche par nom ou email
        if (!empty($search)) {
            $usersQuery->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();
        $filter = $request->input('filter');

        // 3. Récupération de tous les clients correspondants pour calcul et tri
        $allMatchedUsers = $usersQuery->get();

        $processedUsers = collect();
        $globalTotalAum = 0;
        $globalTotalInvesti = 0;
        $globalTotalInterests = 0;
        $globalTotalInterestsFcp = 0;
        $globalTotalInterestsPmg = 0;
        $activeClientsCount = 0;
        $inactiveClientsCount = 0;

        foreach ($allMatchedUsers as $user) {
            $userTotalInvestiGross = 0;
            $userTotalInvestiNet = 0;
            $userTotalValorisation = 0;
            $userFcpValorisation = 0;
            $userPmgValorisation = 0;
            $userFcpInvestiGross = 0;
            $userPmgInvestiGross = 0;
            $activeContractsCount = 0;
            $hasFcp = false;
            $hasPmg = false;
            $hasExpiringPmg = false;
            $hasAnniversary = false;

            $processedFcpProducts = [];
            $allTrans = $user->transactions->concat($user->transactionssupplementaires);

            foreach ($allTrans as $trans) {
                if ($trans->status != 'Succès') continue;
                
                $dateEcheance = Carbon::parse($trans->date_echeance);
                $dateValidation = Carbon::parse($trans->date_validation);
                $isPmg = ($trans->product->products_category_id == 2);
                $isFcp = ($trans->product->products_category_id == 1);

                if ($isPmg) {
                    $hasPmg = true;
                    if ($dateEcheance->between($startOfMonth, $endOfMonth)) {
                        $hasExpiringPmg = true;
                    }
                    if ($dateValidation->month == $currentDate->month && $dateValidation->year < $currentDate->year) {
                        $hasAnniversary = true;
                    }
                }
                if ($isFcp) $hasFcp = true;

                if ($categoryFilter == '1' && !$isFcp) continue;
                if ($categoryFilter == '2' && !$isPmg) continue;

                if ($dateEcheance->gte($currentDate)) {
                    $activeContractsCount++;
                    $principalInitial = (float)($trans->amount);
                    $fees = (float)($trans->fees ?? 0);
                    
                    $userTotalInvestiGross += $principalInitial;
                    $userTotalInvestiNet += ($principalInitial - $fees);

                    if ($isPmg) {
                        $userPmgInvestiGross += $principalInitial;
                        $valo = $this->calculatePMGValorization($trans, $currentDate);
                        $userTotalValorisation += $valo;
                        $userPmgValorisation += $valo;
                    } else {
                        $userFcpInvestiGross += $principalInitial;
                        if (!in_array($trans->product_id, $processedFcpProducts)) {
                            $fcpData = $this->getFcpPortfolioValue($user->id, $trans->product_id, $currentDate);
                            $userTotalValorisation += $fcpData['valorisation'];
                            $userFcpValorisation += $fcpData['valorisation'];
                            $processedFcpProducts[] = $trans->product_id;
                        }
                    }
                }
            }

            $user->total_capital = $userTotalInvestiGross;
            $user->total_capital_net = $userTotalInvestiNet;
            $user->portefeuille_total = $userTotalValorisation;
            // Gain calculated against Gross so that Capital Brut + Gain = Portefeuille Total
            $user->total_interets = max(0, $userTotalValorisation - $userTotalInvestiGross);
            
            $user->total_interets_fcp = max(0, $userFcpValorisation - $userFcpInvestiGross);
            $user->total_interets_pmg = max(0, $userPmgValorisation - $userPmgInvestiGross);

            $user->product_count = $activeContractsCount;
            $user->has_fcp = $hasFcp;
            $user->has_pmg = $hasPmg;
            $user->has_expiring_pmg = $hasExpiringPmg;
            $user->has_anniversary = $hasAnniversary;

            // Filtration par catégorie et par échéance
            $keep = true;
            if ($categoryFilter == '1' && !$hasFcp) $keep = false;
            if ($categoryFilter == '2' && !$hasPmg) $keep = false;
            if ($filter == 'expiring_pmg' && !$hasExpiringPmg) $keep = false;
            if ($filter == 'anniversaries' && !$hasAnniversary) $keep = false;

            if ($keep) {
                $processedUsers->push($user);
                $globalTotalInvesti += $userTotalInvestiGross;
                $globalTotalAum += $userTotalValorisation;
                $globalTotalInterests += $user->total_interets; // Somme des intérêts positifs individuels
                $globalTotalInterestsFcp += $user->total_interets_fcp;
                $globalTotalInterestsPmg += $user->total_interets_pmg;
                if ($userTotalInvestiGross > 0) {
                    $activeClientsCount++;
                } else {
                    $inactiveClientsCount++;
                }
            }
        }

        // $globalTotalInterets est déjà accumulé dans la boucle ci-dessus

        // 4. Tri de la collection
        if ($order == 'desc') {
            $processedUsers = $processedUsers->sortByDesc($sortBy);
        } else {
            $processedUsers = $processedUsers->sortBy($sortBy);
        }

        // 5. Pagination manuelle
        $page = $request->input('page', 1);
        $perPage = 10;
        $pagedData = $processedUsers->slice(($page - 1) * $perPage, $perPage)->values();
        
        $customers = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedData,
            $processedUsers->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        if ($request->ajax()) {
            return view('front-end.partials.customer-table', compact(
                'customers', 
                'globalTotalAum', 
                'globalTotalInvesti', 
                'globalTotalInterests', 
                'globalTotalInterestsFcp',
                'globalTotalInterestsPmg',
                'search', 
                'activeClientsCount', 
                'inactiveClientsCount',
                'categoryFilter',
                'filter',
                'sortBy',
                'order'
            ));
        }

        return view('front-end.customer', compact(
            'customers', 
            'globalTotalAum', 
            'globalTotalInvesti', 
            'globalTotalInterests', 
            'globalTotalInterestsFcp',
            'globalTotalInterestsPmg',
            'search', 
            'activeClientsCount', 
            'inactiveClientsCount',
            'categoryFilter',
            'filter',
            'sortBy',
            'order'
        ));
    }

    /**
     * Exportation des clients en CSV (Asset Manager)
     * Reprend la même logique de calcul que customers()
     */
    public function exportCustomers(Request $request)
    {
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'name');
        $order = $request->input('order', 'asc');
        $categoryFilter = $request->input('category', 'all');
        $exportStatus = $request->input('export_status', 'active');
        $selectedFields = $request->input('fields', ['name', 'email']);
        $currentDate = Carbon::now();

        $usersQuery = User::where('role_id', '2');
        if (!empty($search)) {
            $usersQuery->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
        $allMatchedUsers = $usersQuery->get();

        $processedUsers = collect();
        $globalTotalInteretsExport = 0;

        foreach ($allMatchedUsers as $user) {
            $totalInvestedActive = 0;
            $totalInvestedInactive = 0;
            $totalValorisation = 0;
            $activeCount = 0;
            $inactiveCount = 0;
            $hasFcp = false;
            $hasPmg = false;
            $firstPlacementDate = null;
            $processedFcpProducts = [];

            $allTrans = $user->transactions()->where('status', 'Succès')->orderBy('date_validation', 'asc')->get()
                ->concat($user->transactionssupplementaires()->where('status', 'Succès')->orderBy('date_validation', 'asc')->get())
                ->sortBy('date_validation');

            if ($allTrans->isNotEmpty()) {
                $firstPlacementDate = $allTrans->first()->date_validation;
            }

            foreach ($allTrans as $trans) {
                $dateEcheance = Carbon::parse($trans->date_echeance);
                $isPmg = ($trans->product->products_category_id == 2);
                $isFcp = ($trans->product->products_category_id == 1);
                
                if ($isPmg) $hasPmg = true;
                if ($isFcp) $hasFcp = true;

                // Category filter check
                if ($categoryFilter == '1' && !$isFcp) continue;
                if ($categoryFilter == '2' && !$isPmg) continue;

                $amount = ((float)$trans->amount + (float)($trans->fees ?? 0));

                if ($dateEcheance->gte($currentDate)) {
                    $activeCount++;
                    $totalInvestedActive += $amount;
                    if ($isPmg) {
                        $totalValorisation += $this->calculatePMGValorization($trans, $currentDate);
                    } else {
                        if (!in_array($trans->product_id, $processedFcpProducts)) {
                            $fcpData = $this->getFcpPortfolioValue($user->id, $trans->product_id, $currentDate);
                            $totalValorisation += $fcpData['valorisation'];
                            $processedFcpProducts[] = $trans->product_id;
                        }
                    }
                } else {
                    $inactiveCount++;
                    $totalInvestedInactive += $amount;
                }
            }

            // Client type determination
            $clientType = 'Aucun';
            if ($hasFcp && $hasPmg) $clientType = 'FCP & PMG';
            elseif ($hasFcp) $clientType = 'FCP';
            elseif ($hasPmg) $clientType = 'PMG';

            // Filter by export status
            if ($exportStatus == 'active' && $activeCount == 0) continue;
            if ($exportStatus == 'inactive' && $activeCount > 0) continue;

            $user->total_capital_active = $totalInvestedActive;
            $user->total_capital_inactive = $totalInvestedInactive;
            $user->portefeuille_total = $totalValorisation;
            $user->total_interets = max(0, $totalValorisation - $totalInvestedActive);
            $user->inactive_count = $inactiveCount;
            $user->first_placement = $firstPlacementDate ? Carbon::parse($firstPlacementDate)->format('d/m/Y') : '-';
            $user->client_type_label = $clientType;

            $processedUsers->push($user);
            $globalTotalInteretsExport += $user->total_interets;
        }

        // Sorting
        $processedUsers = ($order == 'desc') ? $processedUsers->sortByDesc($sortBy) : $processedUsers->sortBy($sortBy);

        $fileName = 'export_clients_' . $exportStatus . '_' . date('Y-m-d_H-i') . '.csv';
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        ];

        $callback = function() use ($processedUsers, $selectedFields) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel UTF-8
            
            // Generate headers based on selection
            $headerRows = [];
            if (in_array('name', $selectedFields)) $headerRows[] = 'Nom';
            if (in_array('email', $selectedFields)) $headerRows[] = 'Email';
            if (in_array('first_placement', $selectedFields)) $headerRows[] = '1er Placement';
            if (in_array('placements_count', $selectedFields)) {
                 $headerRows[] = 'Placements Actifs';
                 $headerRows[] = 'Placements Inactifs';
            }
            if (in_array('total_invested', $selectedFields)) {
                $headerRows[] = 'Total Investi Actif';
                $headerRows[] = 'Total Investi Inactif';
            }
            if (in_array('client_type', $selectedFields)) $headerRows[] = 'Type de Client';
            if (in_array('portfolio_valo', $selectedFields)) $headerRows[] = 'Valorisation Globale';
            if (in_array('total_gains', $selectedFields)) $headerRows[] = 'Total Intérêts';

            fputcsv($file, $headerRows);

            foreach ($processedUsers as $u) {
                $row = [];
                if (in_array('name', $selectedFields)) $row[] = $u->name;
                if (in_array('email', $selectedFields)) $row[] = $u->email;
                if (in_array('first_placement', $selectedFields)) $row[] = $u->first_placement;
                if (in_array('placements_count', $selectedFields)) {
                    $row[] = $u->active_count;
                    $row[] = $u->inactive_count;
                }
                if (in_array('total_invested', $selectedFields)) {
                    $row[] = number_format($u->total_capital_active, 0, '.', '');
                    $row[] = number_format($u->total_capital_inactive, 0, '.', '');
                }
                if (in_array('client_type', $selectedFields)) $row[] = $u->client_type_label;
                if (in_array('portfolio_valo', $selectedFields)) $row[] = number_format($u->portefeuille_total, 0, '.', '');
                if (in_array('total_gains', $selectedFields)) $row[] = number_format($u->total_interets, 0, '.', '');

                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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
     * Modification d'une transaction existante
     * Sécurisé : Si la transaction était validée, elle repasse en attente et perd ses mouvements
     */
    public function editTransaction(Request $request)
    {
        $id = $request->input('trans_id');
        $isSupp = $request->input('is_supp') == 'true';
        $opType = $request->input('op_type') ?? 'souscription';

        // Si c'est un rachat qui vient d'un mouvement direct (ID virtuel), on cherche la transaction liée
        if ($opType == 'rachat') {
            // On essaie de trouver une transaction de type rachat ou liée au mouvement
            $item = Transaction::where('id', $id)->first();
            if (!$item) {
                // Si on ne trouve pas par ID direct, c'est peut-être un mouvement FCP/PMG qui n'a pas encore de transaction
                // Dans ce cas, on crée une transaction de rachat "fantôme" pour porter la validation
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ce rachat est un mouvement historique direct et ne peut pas être modifié via ce formulaire. Contactez l\'administrateur.'
                ], 422);
            }
        } else {
            $item = $isSupp ? TransactionSupplementaire::findOrFail($id) : Transaction::findOrFail($id);
        }

        $oldAmount = $item->amount;
        $wasValidated = $item->is_compliance_validated == 1;

        // Mise à jour des valeurs
        $item->amount = $request->input('amount');
        $item->vl_buy = $request->input('vl_buy');
        $item->date_validation = $request->input('date_validation');
        
        // SÉCURITÉ : Reset des validations
        $item->status = 'En attente';
        $item->is_compliance_validated = 0;
        $item->is_backoffice_validated = 0;
        $item->is_dg_validated = 0;
        $item->compliance_validated_at = null;
        $item->backoffice_validated_at = null;
        $item->dg_validated_at = null;
        
        $item->save();

        // On supprime les mouvements financiers liés pour forcer la re-validation
        $product = Product::find($item->product_id);
        if ($product->products_category_id == 1) {
            DB::table('fcp_movements')->where('transaction_id', $item->id)->delete();
        } else {
            DB::table('financial_movements')->where('transaction_id', $item->id)->delete();
        }

        \App\Models\UserActivityLog::log(
            "MODIFICATION_OPERATION",
            $item->user,
            "Modification de l'opération #{$item->ref} (Type: $opType). En attente de validation Compliance."
        );

        return response()->json([
            'status' => 'success', 
            'message' => 'Opération mise à jour. Elle doit être à nouveau validée par la Compliance.'
        ]);
    }
    public function customersDetail($customer_id)
    {
        $customer = User::findOrFail($customer_id);
        $currentDate = Carbon::now();

        // 1. Récupération des produits consolides avec gains
        $allProducts = $this->getProductsWithGainsUser($customer_id);

        // 2. Historique brut de TOUTES les transactions (Souscriptions & Versements Libres)
        $officialTrans = Transaction::where('user_id', $customer_id)
            ->where('status', 'Succès')
            ->with('product')
            ->get()
            ->map(function($t) { 
                $t->is_supp = false; 
                $t->op_type = 'souscription';
                return $t; 
            });

        $supplementalTrans = TransactionSupplementaire::where('user_id', $customer_id)
            ->where('status', 'Succès')
            ->with('product')
            ->get()
            ->map(function($t) { 
                $t->is_supp = true; 
                $t->op_type = 'souscription';
                return $t; 
            });

        // 2b. Historique des Rachats (FCP & PMG)
        $fcpRachats = DB::table('fcp_movements')
            ->where('user_id', $customer_id)
            ->where('type', 'rachat')
            ->get()
            ->map(function($r) {
                return (object)[
                    'id' => $r->transaction_id ?? $r->id,
                    'ref' => 'RACHAT-FCP-'.$r->id,
                    'amount' => abs($r->amount_xaf),
                    'vl_buy' => $r->vl_applied,
                    'date_validation' => $r->date_operation,
                    'created_at' => $r->created_at,
                    'product_id' => $r->product_id,
                    'product' => \App\Models\Product::find($r->product_id),
                    'is_supp' => false,
                    'op_type' => 'rachat',
                    'is_rachat_virtual' => ($r->transaction_id == null)
                ];
            });

        $pmgRachats = DB::table('financial_movements')
            ->join('transactions', 'financial_movements.transaction_id', '=', 'transactions.id')
            ->where('transactions.user_id', $customer_id)
            ->whereIn('financial_movements.type', ['rachat_partiel', 'rachat_total', 'payout'])
            ->select('financial_movements.*', 'transactions.product_id', 'transactions.id as trans_id')
            ->get()
            ->map(function($r) {
                return (object)[
                    'id' => $r->trans_id ?? $r->id,
                    'ref' => 'RACHAT-PMG-'.$r->id,
                    'amount' => abs($r->amount),
                    'vl_buy' => 0, 
                    'date_validation' => $r->date_operation,
                    'created_at' => $r->created_at,
                    'product_id' => $r->product_id,
                    'product' => \App\Models\Product::find($r->product_id),
                    'is_supp' => false,
                    'op_type' => 'rachat',
                    'is_rachat_virtual' => false
                ];
            });

        $allTransactionsHistory = $officialTrans->concat($supplementalTrans)
            ->concat($fcpRachats)
            ->concat($pmgRachats)
            ->sortByDesc(function($t) {
                return $t->date_validation ?? $t->created_at;
            });

        // 3. Historique des Operations Financieres (PMG)
        $financialMovements = DB::table('financial_movements')
            ->join('transactions', 'financial_movements.transaction_id', '=', 'transactions.id')
            ->where('transactions.user_id', $customer_id)
            ->select('financial_movements.*', 'transactions.ref as trans_ref')
            ->orderBy('date_operation', 'desc')
            ->get();

        // 4. Historique des Operations FCP (Mouvements de parts)
        $fcpMovements = DB::table('fcp_movements')
            ->where('user_id', $customer_id)
            ->orderBy('date_operation', 'desc')
            ->get();

        $totalInvestiActive = 0;
        $portefeuillePMG = 0;
        $portefeuilleFCP = 0;
        $totalPlusValueFCP = 0;

        foreach ($allProducts as $item) {
            // On ne cumule que le capital des produits actifs
            $totalInvestiActive += $item['capital_investi'];

            if ($item['type_product'] == 2) { // PMG
                $portefeuillePMG += $item['portfolio_valeur'];
            } else { // FCP
                $portefeuilleFCP += $item['portfolio_valeur'];
                $totalPlusValueFCP += (float)($item['total_gains_fcp'] ?? 0);
            }
        }

        $portefeuilleTotal = $portefeuillePMG + $portefeuilleFCP;

        // ✅ Calcul des intérêts : (Valeur actuelle + Sorties) - Capital initial total
        $totalPmgPayouts = collect($allProducts)->where('type_product', 2)->sum('total_payouts');
        $totalInterets = max(0, ($portefeuilleTotal + $totalPmgPayouts) - $totalInvestiActive);

        // Récupérer les IDs des produits PMG auxquels le client a déjà souscrit
        $ownedPmgProductIds = collect($allProducts)
            ->where('type_product', 2)
            ->pluck('id_product')
            ->unique()
            ->values()
            ->toArray();

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
            'total_plus_value_fcp' => $totalPlusValueFCP,
            'total_interets' => $totalInterets,
            'total_investi' => $totalInvestiActive,
            'products' => $products,
            'categories' => $categories,
            'availableMonths' => $availableMonths,
            'ownedPmgProductIds' => $ownedPmgProductIds,
            'allTransactionsHistory' => $allTransactionsHistory,
            'financialMovements' => $financialMovements,
            'fcpMovements' => $fcpMovements,
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
                ->where('type', '!=', 'paiement_interets') // Exclure les paiements d'intérêts du relevé
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

        // On utilise stream() pour permettre l'aperçu dans le navigateur
        return $pdf->stream("releve_{$transaction->ref}.pdf");
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

            // Règle de conformité : Pas de relevé FCP pour un mois qui n'est pas encore terminé
            if ($current->month == \Carbon\Carbon::now()->month && $current->year == \Carbon\Carbon::now()->year) {
                $has_fcp = false;
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

    public function getHoldingsAtDate($userId, $productId, $date)
    {
        $service = new \App\Services\InvestmentService();
        $status = $service->getCurrentStatusAtDate($userId, $productId, $date);
        
        // On récupère aussi la VL
        $vlEntry = \DB::table('asset_values')
            ->where('product_id', $productId)
            ->where('date_vl', '<=', $date)
            ->orderBy('date_vl', 'desc')
            ->first();
            
        $product = Product::find($productId);
        $vl = $vlEntry ? (float)$vlEntry->vl : (float)($product->vl ?? 100);

        return response()->json([
            'parts' => $status['parts'],
            'valuation' => $status['parts'] * $vl,
            'vl' => $vl,
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
            $allTransactions = Transaction::where('user_id', $client->id)
                ->where('status', 'Succès')
                ->where('date_validation', '<=', $dateN->toDateString())
                ->whereHas('product', function($q) {
                    $q->where('products_category_id', 2);
                })->get();

            $supplemental = TransactionSupplementaire::where('user_id', $client->id)
                ->where('status', 'Succès')
                ->where('date_validation', '<=', $dateN->toDateString())
                ->whereHas('product', function($q) {
                    $q->where('products_category_id', 2);
                })->get();

            $merged = $allTransactions->merge($supplemental);

            // Ajustement de la date du relevé si tous les mandats sont échus dans le mois
            $maxExpiryInMonth = $merged->where('date_echeance', '<=', $dateN->toDateString())
                                       ->where('date_echeance', '>=', $dateN->copy()->startOfMonth()->toDateString())
                                       ->max('date_echeance');
            $anyActivePastMonth = $merged->where('date_echeance', '>', $dateN->toDateString())->isNotEmpty();
            
            if (!$anyActivePastMonth && $maxExpiryInMonth) {
                $dateN = Carbon::parse($maxExpiryInMonth);
            }

            $grouped = $merged->groupBy('product_id');

            $produitsAffiches = [];
            $totalValoN = 0;
            $totalValoN1 = 0;

            foreach ($grouped as $productId => $productTrans) {
                $productRecord = Product::find($productId);
                if (!$productRecord) continue;

                $productValoN = 0;
                $productValoN1 = 0;
                $productCapitalTotal = 0;
                $productPrecompteTotal = 0;
                $productGainMensuelTotal = 0;

                // Date de placement initial (la plus ancienne)
                $firstDateVal = Carbon::parse($productTrans->min('date_validation') ?? $productTrans->min('created_at')->toDateString());
                $maxExpiryDate = $productTrans->max('date_echeance');

                foreach ($productTrans as $trans) {
                    // --- FILTRE D'ACTIVITÉ ---
                    // On enlève les placements échus avant le début du mois du relevé
                    $expiryDate = Carbon::parse($trans->date_echeance);
                    if ($expiryDate->lt($dateN->copy()->startOfMonth())) {
                        continue;
                    }

                    $vN = $this->calculatePMGValorization($trans, $dateN);
                    $vN1 = $this->calculatePMGValorization($trans, $dateN1);

                    $prec = DB::table('financial_movements')
                        ->where('transaction_id', $trans->id)
                        ->where('type', 'precompte_interets')
                        ->value('amount') ?? 0;

                    $productValoN += $vN;
                    $productValoN1 += $vN1;
                    $productCapitalTotal += (float)$trans->amount;
                    $productPrecompteTotal += (float)$prec;

                    // Gain mensuel local pour cette transaction
                    $mvtCap = DB::table('financial_movements')
                        ->where('transaction_id', $trans->id)
                        ->where('type', 'capitalisation_interets')
                        ->whereBetween('date_operation', [$dateN1->copy()->addDay()->toDateString(), $dateN->toDateString()])
                        ->first();

                    // Sorties du mois (Rachats partiels, Paiement intérêts)
                    $mensualOutflows = DB::table('financial_movements')
                        ->where('transaction_id', $trans->id)
                        ->whereIn('type', ['rachat_partiel', 'paiement_interets', 'precompte_interets', 'dividende_interets'])
                        ->whereBetween('date_operation', [$dateN1->copy()->addDay()->toDateString(), $dateN->toDateString()])
                        ->sum('amount') ?? 0;

                    if ($mvtCap) {
                        $dateCap = Carbon::parse($mvtCap->date_operation);
                        $joursAvant = $dateN1->diffInDays($dateCap->copy()->subDay()) + 1;
                        $joursApres = $dateCap->diffInDays($dateN) + 1;
                        $gainA = ($mvtCap->capital_before * ($trans->vl_buy/100) * $joursAvant) / 360;
                        $gainB = ($mvtCap->capital_after * ($trans->vl_buy/100) * $joursApres) / 360;
                        $productGainMensuelTotal += ($gainA + $gainB);
                    } else {
                        // Cas produit jeune ou normal
                        if (Carbon::parse($trans->date_validation)->gt($dateN1)) {
                             $productGainMensuelTotal += (($vN + $mensualOutflows) - ((float)$trans->amount - (float)$prec));
                        } else {
                             $productGainMensuelTotal += (($vN + $mensualOutflows) - $vN1);
                        }
                    }
                }

                $capNetTotal = $productCapitalTotal - $productPrecompteTotal;
                $totalValoN += $productValoN;
                $totalValoN1 += ($productValoN - $productGainMensuelTotal);

                if ($productCapitalTotal > 0) {
                    $produitsAffiches[] = (object)[
                        'nom' => $productRecord->title,
                        'capital' => $productCapitalTotal,
                        'taux' => $productTrans->first()->vl_buy, // On prend le taux du bloc (généralement identique)
                        'valo_n' => $productValoN,
                        'valo_n1' => ($productValoN - $productGainMensuelTotal),
                        'gain_mensuel' => max(0, round($productGainMensuelTotal, 0)),
                        'perte_mensuelle' => 0, // Les mandats PMG n'ont généralement pas de perte de capital sauf rachat
                        'gain_total' => max(0, $productValoN - $capNetTotal),
                        'souscription' => $firstDateVal->format('d/m/Y'),
                        'date_echeance' => $maxExpiryDate ? Carbon::parse($maxExpiryDate)->format('d/m/Y') : '-',
                        'produit_jeune' => $firstDateVal->gt($dateN1) ? 1 : 0,
                    ];
                }
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

            \App\Models\UserActivityLog::log(
                "TELECHARGEMENT_RELEVE_PMG",
                $client,
                "Téléchargement du relevé PMG pour {$periodeLabel}"
            );

            $clientSlug = str_replace(' ', '_', strtolower($client->name));
            $monthName = strtolower($dateN->translatedFormat('F'));
            $yearStr = $dateN->format('Y');
            $fileName = "rdc_{$clientSlug}_{$monthName}_{$yearStr}.pdf";

            if (request('action') === 'view') {
                return $pdf->stream($fileName);
            }
            return $pdf->download($fileName);

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

                // 1. Solde TOTAL actuel des parts (pour éviter les erreurs de flux cumulés)
                $partsN = DB::table('fcp_movements')
                    ->where('user_id', $client->id)
                    ->where('product_id', $productId)
                    ->where('date_operation', '<=', $dateN->toDateString())
                    ->sum('nb_parts_change') ?? 0;

                // 2. Solde parts à la fin du mois précédent
                $partsN1 = DB::table('fcp_movements')
                    ->where('user_id', $client->id)
                    ->where('product_id', $productId)
                    ->where('date_operation', '<=', $dateN1->toDateString())
                    ->sum('nb_parts_change') ?? 0;

                // 3. Mouvements précis du mois courant
                $sumsMois = DB::table('fcp_movements')
                        ->where('user_id', $client->id)
                        ->where('product_id', $productId)
                        ->whereBetween('date_operation', [$dateN1->copy()->addDay()->toDateString(), $dateN->toDateString()])
                        ->where('nb_parts_change', '>', 1e-9) // Utilisation d'un petit seuil pour le positif
                        ->selectRaw('SUM(amount_xaf) as net, SUM(fees) as total_fees, SUM(nb_parts_change) as parts')
                        ->first();

                $partsSouscritesMois = (float)($sumsMois->parts ?? 0);
                $montantSouscritMois = (float)($sumsMois->net ?? 0) + (float)($sumsMois->total_fees ?? 0);
                $fraisSouscriptionMois = (float)($sumsMois->total_fees ?? 0);

                $partsRacheteesMois = abs(DB::table('fcp_movements')
                        ->where('user_id', $client->id)
                        ->where('product_id', $productId)
                        ->whereBetween('date_operation', [$dateN1->copy()->addDay()->toDateString(), $dateN->toDateString()])
                        ->where('nb_parts_change', '<', -1e-9)
                        ->sum('nb_parts_change')) ?? 0;

                // 4. VL
                $vlN = AssetValue::where('product_id', $productId)
                    ->where('date_vl', '<=', $dateN->toDateString())
                    ->orderBy('date_vl', 'desc')
                    ->value('vl') ?? (float)$product->vl;

                $vlN1 = AssetValue::where('product_id', $productId)
                    ->where('date_vl', '<=', $dateN1->toDateString())
                    ->orderBy('date_vl', 'desc')
                    ->value('vl') ?? (float)$product->vl;

                // 5. Montant investi (Cumul historique des apports BRUT)
                $sumsHistorique = DB::table('fcp_movements')
                    ->where('user_id', $client->id)
                    ->where('product_id', $productId)
                    ->where('date_operation', '<=', $dateN->toDateString())
                    ->where('nb_parts_change', '>', 0)
                    ->selectRaw('SUM(amount_xaf) as net, SUM(fees) as total_fees')
                    ->first();
                
                $cumulInvestiNet = (float)($sumsHistorique->net ?? 0);
                $cumulInvestiBrut = $cumulInvestiNet + (float)($sumsHistorique->total_fees ?? 0);

                // Si cumulInvestiBrut est à 0 (migration), on tente de récupérer via les transactions liées
                if ($cumulInvestiBrut <= 0) {
                     $cumulInvestiBrut = DB::table('transactions')
                        ->where('user_id', $client->id)
                        ->where('product_id', $productId)
                        ->where('status', 'Succès')
                        ->where('date_validation', '<=', $dateN->toDateString())
                        ->sum('amount'); // amount est déjà Brut pour transactions
                     
                     $totalFees = DB::table('transactions')
                        ->where('user_id', $client->id)
                        ->where('product_id', $productId)
                        ->where('status', 'Succès')
                        ->where('date_validation', '<=', $dateN->toDateString())
                        ->sum('fees');
                     $cumulInvestiNet = $cumulInvestiBrut - (float)$totalFees;
                }

                // --- FILTRE D'ACTIVITÉ FCP ---
                if ($partsN <= 0.0001 && $partsN1 <= 0.0001 && $partsSouscritesMois <= 0.0001 && $partsRacheteesMois <= 0.0001) {
                    continue;
                }

                $valoN = (float)$partsN * (float)$vlN;
                $valoN1 = (float)$partsN1 * (float)$vlN1;
                
                $totalValoN += $valoN;
                $totalValoN1 += $valoN1;

                if ($partsN > 0 || $partsSouscritesMois > 0 || $partsRacheteesMois > 0) {
                    $produitsAffiches[] = [
                        'nom' => $product->title,
                        'parts_n' => (float)$partsN,
                        'parts_n1' => (float)$partsN1,
                        'parts_souscrites' => (float)$partsSouscritesMois,
                        'parts_rachetees' => (float)$partsRacheteesMois,
                        'montant_souscrit' => (float)$montantSouscritMois,
                        'frais_souscription' => (float)$fraisSouscriptionMois,
                        'vl_n' => (float)$vlN,
                        'vl_n1' => (float)$vlN1,
                        'valo_n' => (float)$valoN,
                        'valo_n1' => (float)$valoN1,
                        'cumul_investi' => (float)$cumulInvestiBrut,
                        'plus_value' => (float)($valoN - $cumulInvestiBrut),
                        'gain_mensuel' => (float)($valoN + (float)$partsRacheteesMois * (float)$vlN) - ((float)$valoN1 + (float)$montantSouscritMois),
                    ];
                }
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

            \App\Models\UserActivityLog::log(
                "TELECHARGEMENT_RELEVE_FCP",
                $client,
                "Téléchargement du relevé FCP pour {$periodeLabel}"
            );

            $clientSlug = str_replace(' ', '_', strtolower($client->name));
            $monthName = strtolower($dateN->translatedFormat('F'));
            $yearStr = $dateN->format('Y');
            $fileName = "rdc_{$clientSlug}_{$monthName}_{$yearStr}.pdf";

            if (request('action') === 'view') {
                return $pdf->stream($fileName);
            }
            return $pdf->download($fileName);
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

        $supplementalTx = \App\Models\TransactionSupplementaire::where('user_id', $userId)
            ->where('status', 'Succès')
            ->orderBy('date_validation', 'desc')
            ->get();

        $mergedMovements = collect();

        // 2. Mouvements financiers PMG (financial_movements)
        // On JOIN avec la table transactions pour filtrer STRICTEMENT par user_id
        // Cela évite les fuites de données si des IDs de transactions supplémentaires (non gérées ici)
        // entrent en collision avec des IDs de transactions régulières d'autres clients.
        $movements = DB::table('financial_movements')
            ->join('transactions', 'financial_movements.transaction_id', '=', 'transactions.id')
            ->where('transactions.user_id', $userId)
            ->where('financial_movements.type', '!=', 'paiement_interets') // Exclure les paiements d'intérêts
            ->select('financial_movements.*')
            ->orderBy('date_operation', 'asc')
            ->get();
            
        $movements->each(function ($m) use ($transactions, $supplementalTx, &$mergedMovements) {
            $tx = $transactions->firstWhere('id', $m->transaction_id) 
                  ?? $supplementalTx->firstWhere('id', $m->transaction_id);
            if (!$tx) return;
            $productTitle = $tx
                ? optional(Product::find($tx->product_id))->title
                : 'PMG';
            $mergedMovements->push((object)[
                'date'               => $m->created_at ?? $m->date_operation,
                'date_souscription'  => $m->date_operation,
                'libelle'            => strtoupper(str_replace('_', ' ', $m->type)),
                'ref'                => $tx->ref ?? '-',
                'produit'            => $productTitle,
                'montant'            => (float)$m->amount,
                'sens'               => in_array($m->type, ['rachat_partiel', 'rachat_total', 'precompte_interets', 'paiement_interets', 'remboursement']) ? 'sortant' : 'entrant',
            ]);
        });

        // 3. Mouvements FCP (fcp_movements)
        DB::table('fcp_movements')
            ->where('user_id', $userId)
            ->whereNull('transaction_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->each(function ($m) use (&$mergedMovements) {
                $productTitle = optional(Product::find($m->product_id))->title ?? 'FCP';
                $isIncoming   = $m->nb_parts_change >= 0;
                $mergedMovements->push((object)[
                    'date'               => $m->created_at ?? $m->date_operation,
                    'date_souscription'  => $m->date_operation,
                    'libelle'            => strtoupper($m->type) . ' FCP',
                    'ref'                => $m->reference ?? '-',
                    'produit'            => $productTitle,
                    'montant'            => (float)($m->amount_xaf ?? $m->montant ?? 0),
                    'sens'               => $isIncoming ? 'entrant' : 'sortant',
                ]);
            });

        // 4. Transactions initiales
        foreach ($transactions as $tx) {
            $productTitle = optional(Product::find($tx->product_id))->title ?? 'Produit';
            
            // Ligne principale (Brut)
            $mergedMovements->push((object)[
                'date'               => $tx->created_at,
                'date_souscription'  => $tx->date_validation ?? $tx->created_at,
                'libelle'            => "Souscription de \"{$productTitle}\"",
                'ref'                => $tx->ref,
                'produit'            => $productTitle,
                'montant'            => (float)$tx->amount,
                'sens'               => 'entrant',
            ]);

            // Ligne Frais
            if ((float)$tx->fees > 0) {
                $mergedMovements->push((object)[
                    'date'               => $tx->created_at,
                    'date_souscription'  => $tx->date_validation ?? $tx->created_at,
                    'libelle'            => 'FRAIS DE SOUSCRIPTION',
                    'ref'                => $tx->ref,
                    'produit'            => $productTitle,
                    'montant'            => (float)$tx->fees,
                    'sens'               => 'frais',
                ]);
            }
        }

        foreach ($supplementalTx as $tx) {
            $productTitle = optional(Product::find($tx->product_id))->title ?? 'Produit';
            
             // Ligne principale (Brut)
             $mergedMovements->push((object)[
                'date'               => $tx->created_at,
                'date_souscription'  => $tx->date_validation ?? $tx->created_at,
                'libelle'            => "Souscription de \"{$productTitle}\"",
                'ref'                => $tx->ref,
                'produit'            => $productTitle,
                'montant'            => (float)$tx->amount,
                'sens'               => 'entrant',
            ]);

            // Ligne Frais
            if ((float)$tx->fees > 0) {
                $mergedMovements->push((object)[
                    'date'               => $tx->created_at,
                    'date_souscription'  => $tx->date_validation ?? $tx->created_at,
                    'libelle'            => 'FRAIS DE SOUSCRIPTION',
                    'ref'                => $tx->ref,
                    'produit'            => $productTitle,
                    'montant'            => (float)$tx->fees,
                    'sens'               => 'frais',
                ]);
            }
        }

        // 5. Calcul de la valorisation actuelle (Portfolio Value)
        $valuation = 0;
        $refDate = Carbon::now();

        // PMG
        foreach ($transactions as $tx) {
            if ($tx->type == 2) {
                $valuation += $this->calculatePMGValorization($tx, $refDate);
            }
        }
        foreach ($supplementalTx as $tx) {
            if ($tx->type == 2) {
                $valuation += $this->calculatePMGValorization($tx, $refDate);
            }
        }

        // FCP
        $fcpProducts = DB::table('fcp_movements')
            ->where('user_id', $userId)
            ->select('product_id', DB::raw('SUM(nb_parts_change) as total_parts'))
            ->groupBy('product_id')
            ->get();

        foreach ($fcpProducts as $fcp) {
            // Précision à 10 décimales pour le calcul
            if ((double)$fcp->total_parts > 0.0000000001) {
                $lastVl = AssetValue::where('product_id', $fcp->product_id)->orderBy('date_vl', 'desc')->value('vl') 
                         ?? Product::where('id', $fcp->product_id)->value('vl');
                $valuation += ((double)$fcp->total_parts * (double)$lastVl);
            }
        }

        $allMovements = $mergedMovements->sortByDesc('date')->values();

        $pdf = Pdf::loadView('front-end.releves.historique-transactions-pdf', [
            'user'         => $user,
            'allMovements' => $allMovements,
            'valuation'    => $valuation,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ]);
        $pdf->setPaper('A4', 'portrait');

            \App\Models\UserActivityLog::log(
                "TELECHARGEMENT_HISTORIQUE",
                $user,
                "Téléchargement de l'historique complet des transactions"
            );

        // On utilise stream() au lieu de download() pour permettre l'aperçu dans un nouvel onglet
        return $pdf->stream("historique_transactions_{$user->name}.pdf");
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

        // 3. Reconstruire la chronologie des valorisations avec calcul du capital investi cumulé
        $history = $assetValues->map(function ($vl) use ($movements) {
            // Cumul des investissements (BRUT = Net + Frais) jusqu'à cette date précise
            $cumulativeInvested = (float) $movements->where('date_operation', '<=', $vl->date_vl)
                ->whereIn('type', ['souscription', 'versement_libre'])
                ->reduce(function ($carry, $m) {
                    return $carry + (float)$m->amount_xaf + (float)($m->fees ?? 0);
                }, 0);
            
            // Cumul des rachats jusqu'à cette date
            $cumulativeRedemptions = (float) $movements->where('date_operation', '<=', $vl->date_vl)
                ->whereIn('type', ['rachat', 'rachat_partiel', 'rachat_total', 'rachat'])
                ->sum('amount_xaf');

            // Le capital investi net de rachats (par rapport au brut initial)
            $remainingGrossCapital = max(0, $cumulativeInvested - abs($cumulativeRedemptions));
            
            $partsToDate = (float) $movements->where('date_operation', '<=', $vl->date_vl)->sum('nb_parts_change');
            $valuation = round($partsToDate * (float)$vl->vl, 0);
            
            return [
                'date' => \Carbon\Carbon::parse($vl->date_vl)->format('d/m/Y'),
                'vl' => (float)$vl->vl,
                'parts' => $partsToDate,
                'valuation' => $valuation,
                'plus_value' => $valuation - $remainingGrossCapital
            ];
        });

        return response()->json([
            'product_name' => $product->title,
            'customer_name' => $user->name,
            'history' => $history
        ]);
    }

    /**
     * API pour récupérer l'évolution des intérêts d'un produit PMG pour un utilisateur spécifique
     */
    public function getPmgEvolutionApi($productId, $customerId)
    {
        $user = \App\Models\User::findOrFail($customerId);
        $product = \App\Models\Product::findOrFail($productId);

        $transactions = \App\Models\Transaction::where('user_id', $customerId)
            ->where('product_id', $productId)
            ->where('status', 'Succès')
            ->get();

        $supplementals = \App\Models\TransactionSupplementaire::where('user_id', $customerId)
            ->where('product_id', $productId)
            ->where('status', 'Succès')
            ->get();

        $allTrans = $transactions->concat($supplementals);

        if ($allTrans->isEmpty()) {
            return response()->json(['history' => [], 'message' => 'Aucune transaction trouvée']);
        }

        $minDate = $allTrans->min(function($t) { return \Carbon\Carbon::parse($t->date_validation ?? $t->created_at); });
        $maxEcheance = $allTrans->max(function($t) { return \Carbon\Carbon::parse($t->date_echeance); });
        $endDate = \Carbon\Carbon::now()->min($maxEcheance);

        $datesToCalculate = collect();
        $currentDate = $minDate->copy()->startOfMonth();

        while ($currentDate->lt($endDate)) {
            $endOfM = $currentDate->copy()->endOfMonth();
            if ($endOfM->lt($endDate)) {
                $datesToCalculate->push($endOfM);
            }
            $currentDate->addMonth();
        }
        $datesToCalculate->push($endDate->copy());

        // Add exact dates of financial movements to have precise jumps
        $txIds = $allTrans->pluck('id');
        $movementDates = \Illuminate\Support\Facades\DB::table('financial_movements')
            ->whereIn('transaction_id', $txIds)
            ->where('date_operation', '<=', $endDate->toDateString())
            ->pluck('date_operation')
            ->map(function($d) { return \Carbon\Carbon::parse($d); });
        
        foreach($movementDates as $md) {
            $datesToCalculate->push($md);
        }

        $datesToCalculate = $datesToCalculate->unique(function($d) { return $d->toDateString(); })->sortByDesc(function($d) { return $d->timestamp; })->values();

        $history = [];

        foreach ($datesToCalculate as $date) {
            $totalNetCapital = 0;
            $totalValuation = 0;
            $tauxMoyenStr = [];

            foreach ($allTrans as $trans) {
                if (\Carbon\Carbon::parse($trans->date_validation ?? $trans->created_at)->gt($date)) continue;

                $val = $this->calculatePMGValorization($trans, $date->toDateString());
                
                $lastMovement = \Illuminate\Support\Facades\DB::table('financial_movements')
                    ->where('transaction_id', $trans->id)
                    ->whereIn('type', ['capitalisation_interets', 'rachat_partiel'])
                    ->where('date_operation', '<=', $date->toDateString())
                    ->orderBy('date_operation', 'desc')
                    ->first();
                $baseCapital = $lastMovement ? (float)$lastMovement->capital_after : (float)$trans->amount;

                $payouts = \Illuminate\Support\Facades\DB::table('financial_movements')
                    ->where('transaction_id', $trans->id)
                    ->whereIn('type', ['precompte_interets', 'paiement_interets'])
                    ->where('date_operation', '<=', $date->toDateString())
                    ->sum('amount') ?? 0;

                $totalRedemption = \Illuminate\Support\Facades\DB::table('financial_movements')
                    ->where('transaction_id', $trans->id)
                    ->where('type', 'rachat_total')
                    ->where('date_operation', '<=', $date->toDateString())
                    ->exists();

                $netCapital = $baseCapital - $payouts;
                if ($totalRedemption) {
                    $netCapital = 0;
                }

                $totalNetCapital += $netCapital;
                $totalValuation += $val;
                
                if (!$totalRedemption) {
                    $tauxMoyenStr[] = $trans->vl_buy . '%';
                }
            }

            if ($totalNetCapital == 0 && $totalValuation == 0) continue;

            $interests = max(0, $totalValuation - $totalNetCapital);

            $history[] = [
                'date' => $date->format('d/m/Y'),
                'capital' => $totalNetCapital,
                'taux' => implode(', ', array_unique($tauxMoyenStr)),
                'valuation' => $totalValuation,
                'interests' => $interests
            ];
        }

        return response()->json([
            'product_name' => $product->title,
            'customer_name' => $user->name,
            'history' => $history
        ]);
    }
}
