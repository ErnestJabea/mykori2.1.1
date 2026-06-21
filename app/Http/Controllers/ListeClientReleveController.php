<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\ProductController;
use App\Mail\ReleveClientMail;
use App\Support\FinancialDecimal;

class ListeClientReleveController extends Controller
{
    protected $productController;

    public function __construct(ProductController $productController)
    {
        $this->productController = $productController;
    }

    private function getPositiveFcpStatementValue(int $clientId, Carbon $date): float
    {
        $productIds = DB::table('transactions')
            ->where('user_id', $clientId)
            ->where('status', 'Succès')
            ->distinct()->pluck('product_id')
            ->merge(
                DB::table('transaction_supplementaires')
                    ->where('user_id', $clientId)
                    ->where('status', 'Succès')
                    ->distinct()->pluck('product_id')
            )->unique();

        $total = 0;
        foreach ($productIds as $pid) {
            $product = \App\Models\Product::find($pid);
            if (!$product || (int)$product->products_category_id !== 1) {
                continue;
            }

            $fcpData = $this->productController->getFcpPortfolioValue($clientId, $pid, $date);
            if (($fcpData['parts'] ?? 0) > 0 && ($fcpData['valorisation'] ?? 0) > 0) {
                $total += (float)$fcpData['valorisation'];
            }
        }

        return $total;
    }

    private function getPositivePmgStatementValue(int $clientId, Carbon $date): float
    {
        $periodStart = $date->copy()->startOfMonth();

        $mainTransactions = Transaction::where('user_id', $clientId)
            ->where('status', 'Succès')
            ->where('date_validation', '<=', $date->format('Y-m-d'))
            ->where(function($q) use ($periodStart) {
                $q->whereNull('date_echeance')
                    ->orWhere('date_echeance', '>=', $periodStart->format('Y-m-d'));
            })
            ->whereHas('product', function($q) {
                $q->where('products_category_id', 2);
            })->get();

        $suppTransactions = \App\Models\TransactionSupplementaire::where('user_id', $clientId)
            ->where('status', 'Succès')
            ->where('date_validation', '<=', $date->format('Y-m-d'))
            ->where(function($q) use ($periodStart) {
                $q->whereNull('date_echeance')
                    ->orWhere('date_echeance', '>=', $periodStart->format('Y-m-d'));
            })
            ->whereHas('product', function($q) {
                $q->where('products_category_id', 2);
            })->get();

        $total = 0;
        foreach ($mainTransactions->merge($suppTransactions) as $transaction) {
            $value = (float)$this->productController->calculatePMGValorization($transaction, $date);
            if ($value > 0) {
                $total += $value;
            }
        }

        return $total;
    }

    public function index($type = 'all')
    {
        $allClients = User::where('role_id', 2)->get();
        $currentDate = Carbon::now();
        $statementDate = $currentDate->copy()->startOfMonth()->subDay();
        $periode = $currentDate->copy()->subMonth()->translatedFormat('F Y');

        $filteredClients = collect();

        foreach ($allClients as $client) {
            $totalValorisationFcp = 0;
            $totalValorisationPmg = 0;
            $client->has_fcp = false;
            $client->has_pmg = false;

            $totalValorisationFcp = $this->getPositiveFcpStatementValue($client->id, $statementDate);
            $totalValorisationPmg = $this->getPositivePmgStatementValue($client->id, $statementDate);
            $client->has_fcp = $totalValorisationFcp > 0;
            $client->has_pmg = $totalValorisationPmg > 0;

            if ($type === 'fcp') {
                $client->portefeuille_total = $totalValorisationFcp;
            } elseif ($type === 'pmg') {
                $client->portefeuille_total = $totalValorisationPmg;
            } else {
                $client->portefeuille_total = $totalValorisationFcp + $totalValorisationPmg;
            }

            // Filtrage selon le type demandé
            if ($type === 'fcp' && $client->has_fcp) {
                $filteredClients->push($client);
            } elseif ($type === 'pmg' && $client->has_pmg) {
                $filteredClients->push($client);
            } elseif ($type === 'all') {
                $filteredClients->push($client);
            }
        }

        $clients = $filteredClients;

        return view('front-end.liste-client', compact('clients', 'periode', 'type'));
    }

    public function sendStatement($id)
    {
        $client = User::findOrFail($id);
        $currentDate = Carbon::now();

        // Récupération des données valorisées pour le PDF
        $transactions = Transaction::where('user_id', $id)
                 ->where('date_echeance', '>=', $currentDate->format('Y-m-d'))
                 ->where('status', 'Succès')->get();

        // Logique de génération et envoi (exemple simplifié)
        $pdf = Pdf::loadView('front-end.releves.releve-preview', [
            'client' => $client,
            'date' => $currentDate,
            'controller' => $this->productController // On passe le contrôleur à la vue PDF si besoin
        ]);

        // Logique Mail::send...

        return back()->with('success', "Le relevé de {$client->name} a été envoyé.");
    }


public function previewPmg(int $clientId)
{
    $client = User::findOrFail($clientId);
    $productController = app(ProductController::class);
    
    $dateN  = Carbon::today()->startOfMonth()->subDay(); 

    $transactions = Transaction::where('user_id', $client->id)
        ->where('status', 'Succès')
        ->whereHas('product', function($q) {
            $q->where('products_category_id', 2);
        })->get();

    $allTransactions = Transaction::where('user_id', $client->id)
        ->where('status', 'Succès')
        ->where('date_validation', '<=', $dateN->toDateString())
        ->whereHas('product', function($q) {
            $q->where('products_category_id', 2);
        })->get();

    $supplemental = \App\Models\TransactionSupplementaire::where('user_id', $client->id)
        ->where('status', 'Succès')
        ->where('date_validation', '<=', $dateN->toDateString())
        ->whereHas('product', function($q) {
            $q->where('products_category_id', 2);
        })->get();

    $merged = $allTransactions->merge($supplemental);

    // Date N fixe : dernier jour du mois clos, meme si un mandat expire ensuite pendant la periode d'envoi.
    $dateN1 = $dateN->copy()->startOfMonth()->subDay(); 

    // Filtrer et exclure les transactions totalement rachetées avant le début de ce mois clos
    $merged = $merged->reject(function($trans) use ($dateN1) {
        $isSupplementaire = ($trans instanceof \App\Models\TransactionSupplementaire);
        $parentId = $isSupplementaire ? $trans->transaction_id : $trans->id;

        // 1. Si le capital nominal de la transaction principale est déjà à 0
        $parent = \App\Models\Transaction::find($parentId);
        if ($parent && (float)$parent->amount <= 0) {
            // Vérifier s'il a été racheté avant dateN1
            $hasRachatBefore = DB::table('financial_movements')
                ->where('transaction_id', $parentId)
                ->where('type', 'rachat_total')
                ->where('date_operation', '<=', $dateN1->toDateString())
                ->exists();
            if ($hasRachatBefore) {
                return true;
            }
            
            // Si le dernier mouvement avant/à dateN1 montre un capital nul
            $lastM1 = DB::table('financial_movements')
                ->where('transaction_id', $parentId)
                ->where('date_operation', '<=', $dateN1->toDateString())
                ->orderBy('date_operation', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            if ($lastM1 && (float)$lastM1->capital_after <= 0) {
                return true;
            }
            
            // Si aucun mouvement n'a eu lieu avant/à dateN1, et le capital actuel est 0
            $hasMovementsBefore = DB::table('financial_movements')
                ->where('transaction_id', $parentId)
                ->where('date_operation', '<=', $dateN1->toDateString())
                ->exists();
            if (!$hasMovementsBefore) {
                return true;
            }
        }

        // 2. Si un rachat total explicite a été enregistré avant/à dateN1
        $hasRachatTotalBefore = DB::table('financial_movements')
            ->where('transaction_id', $parentId)
            ->where('type', 'rachat_total')
            ->where('date_operation', '<=', $dateN1->toDateString())
            ->exists();
        if ($hasRachatTotalBefore) {
            return true;
        }

        return false;
    });

    $grouped = $merged->groupBy('product_id');

    $produitsAffiches = [];
    $totalValoN = 0;
    $totalValoN1 = 0;

    foreach ($grouped as $productId => $productTrans) {
        $productRecord = \App\Models\Product::find($productId);
        if (!$productRecord) continue;

        $productValoN = 0;
        $productValoN1 = 0;
        $productCapitalTotal = 0;
        $productPrecompteTotal = 0;
        $productGainMensuelTotal = 0;
        $productPertesMensuelles = 0;

        $firstDateVal = Carbon::parse($productTrans->min('date_validation') ?? $productTrans->min('created_at')->toDateString());
        $maxExpiryDate = $productTrans->max('date_echeance');
        $productValuationDate = $dateN->copy();
        if ($maxExpiryDate) {
            $expiryForValuation = Carbon::parse($maxExpiryDate);
            if ($expiryForValuation->betweenIncluded($dateN->copy()->startOfMonth(), $dateN)) {
                $productValuationDate = $expiryForValuation;
            }
        }

        foreach ($productTrans as $trans) {
            $vN = $productController->calculatePMGValorization($trans, $dateN);
            $vN1 = $productController->calculatePMGValorization($trans, $dateN1);

            // Déterminer le bon ID parent pour les requêtes de mouvements financiers
            $isSupplementaire = ($trans instanceof \App\Models\TransactionSupplementaire);
            $parentId = $isSupplementaire ? $trans->transaction_id : $trans->id;

            // Plage de dates incluant l'heure pour capturer tout le dernier jour du mois clos
            $startDatePeriod = $dateN1->copy()->addDay()->startOfDay()->toDateTimeString();
            $endDatePeriod = $dateN->copy()->endOfDay()->toDateTimeString();

            // Sorties du mois pour le calcul du gain (inclut les intérêts payés, les rachats et les frais)
            $totalOutflowsForGainQuery = DB::table('financial_movements')
                ->where('transaction_id', $parentId)
                ->whereIn('type', ['rachat_partiel', 'rachat_total', 'frais_gestion', 'paiement_interets', 'precompte_interets', 'liquidite_interets', 'dividende_interets'])
                ->whereBetween('date_operation', [$startDatePeriod, $endDatePeriod]);
            if ($isSupplementaire) {
                $totalOutflowsForGainQuery->where('comments', 'LIKE', "%versement complémentaire ID {$trans->id}%");
            } else {
                $totalOutflowsForGainQuery->where(function($q) {
                    $q->where('comments', 'NOT LIKE', "%versement complémentaire ID %")
                      ->orWhereNull('comments');
                });
            }
            $totalOutflowsForGain = $totalOutflowsForGainQuery->sum('amount') ?? 0;

            // Sorties affichées en "Pertes" (exclut les rachats et les frais de gestion selon demande)
            $displayedOutflowsQuery = DB::table('financial_movements')
                ->where('transaction_id', $parentId)
                ->whereIn('type', ['precompte_interets', 'liquidite_interets', 'dividende_interets'])
                ->whereBetween('date_operation', [$startDatePeriod, $endDatePeriod]);
            if ($isSupplementaire) {
                $displayedOutflowsQuery->where('comments', 'LIKE', "%versement complémentaire ID {$trans->id}%");
            } else {
                $displayedOutflowsQuery->where(function($q) {
                    $q->where('comments', 'NOT LIKE', "%versement complémentaire ID %")
                      ->orWhereNull('comments');
                });
            }
            $displayedOutflows = $displayedOutflowsQuery->sum('amount') ?? 0;

            // Calcul du gain mensuel de cette transaction
            $mvtCapQuery = DB::table('financial_movements')
                ->where('transaction_id', $parentId)
                ->where('type', 'capitalisation_interets')
                ->whereBetween('date_operation', [$startDatePeriod, $endDatePeriod]);
            if ($isSupplementaire) {
                $mvtCapQuery->where('comments', 'LIKE', "%versement complémentaire ID {$trans->id}%");
            } else {
                $mvtCapQuery->where(function($q) {
                    $q->where('comments', 'NOT LIKE', "%versement complémentaire ID %")
                      ->orWhereNull('comments');
                });
            }
            $mvtCap = $mvtCapQuery->first();

            $currentTransGain = 0;
            if ($mvtCap) {
                $dateCap = Carbon::parse($mvtCap->date_operation);
                $joursA = $this->productController->calculate30_360Days($dateN1, $dateCap);
                $joursB = $this->productController->calculate30_360Days($dateCap, $dateN);
                $gA = ($mvtCap->capital_before * ($trans->vl_buy/100) * $joursA) / 360;
                $gB = ($mvtCap->capital_after * ($trans->vl_buy/100) * $joursB) / 360;
                $currentTransGain = ($gA + $gB);
            } else {
                $currentTransGain = ($vN + $totalOutflowsForGain) - $vN1;
                
                // Si c'est un nouveau produit (N-1 = 0), on déduit le capital pour ne montrer que les intérêts
                if ($vN1 <= 0 && $vN > 0) {
                    $currentTransGain -= (float)$trans->amount;
                }
            }

            // --- FILTRE D'ACTIVITÉ ---
            // On enlève les placements échus avant le début du mois du relevé
            $expiryDate = Carbon::parse($trans->date_echeance);
            if ($expiryDate->lt($dateN->copy()->startOfMonth())) {
                continue;
            }

            $precQuery = DB::table('financial_movements')
                ->where('transaction_id', $parentId)
                ->where('type', 'precompte_interets');
            if ($isSupplementaire) {
                $precQuery->where('comments', 'LIKE', "%versement complémentaire ID {$trans->id}%");
            } else {
                $precQuery->where(function($q) {
                    $q->where('comments', 'NOT LIKE', "%versement complémentaire ID %")
                      ->orWhereNull('comments');
                });
            }
            $prec = $precQuery->value('amount') ?? 0;

            $productValoN += $vN;
            $productValoN1 += $vN1;

            // Calcul du capital à la date de fin du relevé (prenant en compte les capitalisations et les rachats)
            $transCapital = (float)$trans->amount;
            if ($trans->product->products_category_id == 2) { // PMG
                $lastMQuery = DB::table('financial_movements')
                    ->where('transaction_id', $parentId)
                    ->whereIn('type', ['capitalisation_interets', 'rachat_partiel', 'rachat_total'])
                    ->where('date_operation', '<=', $dateN->toDateString());
                if ($isSupplementaire) {
                    $lastMQuery->where('comments', 'LIKE', "%versement complémentaire ID {$trans->id}%");
                } else {
                    $lastMQuery->where(function($q) {
                        $q->where('comments', 'NOT LIKE', "%versement complémentaire ID %")
                          ->orWhereNull('comments');
                    });
                }
                $lastM = $lastMQuery->orderBy('date_operation', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();
                
                if ($lastM) {
                    if ($lastM->type === 'rachat_total' || (float)$lastM->capital_after <= 0) {
                        // Si racheté ce mois-ci, on garde le capital avant rachat pour affichage historique dans ce relevé
                        $startOfMonthStr = $dateN->copy()->startOfMonth()->toDateString();
                        if ($lastM->date_operation >= $startOfMonthStr) {
                            $transCapital = (float)$lastM->capital_before;
                        } else {
                            $transCapital = 0;
                        }
                    } else {
                        $transCapital = (float)$lastM->capital_after;
                    }
                }
            }

            $productCapitalTotal += $transCapital;
            $productPrecompteTotal += (float)$prec;
            $productPertesMensuelles += $displayedOutflows;
            $productGainMensuelTotal += $currentTransGain;
        }

        $capNetTotal = $productCapitalTotal - $productPrecompteTotal;
        $totalValoN += $productValoN;
        $totalValoN1 += ($productValoN - $productGainMensuelTotal);

        if ($productCapitalTotal > 0 || $productValoN > 0 || $productValoN1 > 0) {
            $produitsAffiches[] = (object)[
                'nom' => $productRecord->title,
                'capital' => $productCapitalTotal,
                'taux' => $productTrans->first()->vl_buy,
                'valo_n' => $productValoN,
                'valo_n1' => $productValoN1,
                'gain_mensuel' => max(0, round($productGainMensuelTotal, 0)),
                'perte_mensuelle' => round($productPertesMensuelles, 0),
                'gain_total' => max(0, $productValoN - $capNetTotal),
                'souscription' => $firstDateVal->format('d/m/Y'),
                'date_echeance' => $maxExpiryDate ? Carbon::parse($maxExpiryDate)->format('d/m/Y') : '-',
                'date_valorisation' => $productValuationDate->format('d/m/Y'),
                'date_valorisation_raw' => $productValuationDate->toDateString(),
                'produit_jeune' => $firstDateVal->gt($dateN1) ? 1 : 0,
            ];
        }
    }

    $dateReleveAffichee = $dateN->copy();
    if (count($produitsAffiches) === 1 && !empty($produitsAffiches[0]->date_valorisation_raw)) {
        $dateReleveAffichee = Carbon::parse($produitsAffiches[0]->date_valorisation_raw);
    }

    return view('front-end.releves.releve-preview', [
        'client' => $client,
        'produits' => $produitsAffiches,
        'valorisation_courante' => $totalValoN,
        'valorisation_precedente' => $totalValoN1,
        'date_releve' => $dateReleveAffichee->format('d/m/Y'),
        'date_releve_precedent' => $dateN1->format('d/m/Y'),
        'liquidite_pmg' => $this->productController->getPmgAvailableLiquidityBreakdownForUser($client->id, $dateReleveAffichee),
        'periode' => ucfirst($dateN->translatedFormat('F Y')),
    ]);
}

public function previewFcp(int $clientId)
{
    $client = User::findOrFail($clientId);
    $service = new \App\Services\InvestmentService();
    
    $dateN  = Carbon::today()->startOfMonth()->subDay(); 
    $dateN1 = $dateN->copy()->startOfMonth()->subDay(); 

    // Récupération des IDs des produits FCP possédés par le client
    $productIds = DB::table('fcp_movements')
        ->where('user_id', $client->id)
        ->distinct()
        ->pluck('product_id');

    $produitsAffiches = [];
    $totalValoN = 0;
    $totalValoN1 = 0;

    foreach ($productIds as $productId) {
        $product = \App\Models\Product::find($productId);
        if (!$product) continue;

        // 1. Solde TOTAL
        $partsN = DB::table('fcp_movements')
            ->where('user_id', $client->id)
            ->where('product_id', $productId)
            ->whereDate('date_operation', '<=', $dateN->toDateString())
            ->sum('nb_parts_change') ?? 0;

        $partsN1 = DB::table('fcp_movements')
            ->where('user_id', $client->id)
            ->where('product_id', $productId)
            ->whereDate('date_operation', '<=', $dateN1->toDateString())
            ->sum('nb_parts_change') ?? 0;

        $partsSouscritesMois = \DB::table('fcp_movements')
            ->where('user_id', $client->id)
            ->where('product_id', $productId)
            ->whereDate('date_operation', '>=', $dateN1->copy()->addDay()->toDateString())
            ->whereDate('date_operation', '<=', $dateN->toDateString())
            ->where('nb_parts_change', '>', 0)
            ->sum('nb_parts_change') ?? 0;

        $partsRacheteesMois = abs(\DB::table('fcp_movements')
            ->where('user_id', $client->id)
            ->where('product_id', $productId)
            ->whereDate('date_operation', '>=', $dateN1->copy()->addDay()->toDateString())
            ->whereDate('date_operation', '<=', $dateN->toDateString())
            ->where('nb_parts_change', '<', 0)
            ->sum('nb_parts_change')) ?? 0;

        $montantSouscritMois = \DB::table('fcp_movements')
                ->where('user_id', $client->id)
                ->where('product_id', $productId)
                ->whereDate('date_operation', '>=', $dateN1->copy()->addDay()->toDateString())
                ->whereDate('date_operation', '<=', $dateN->toDateString())
                ->where('nb_parts_change', '>', 0)
                ->select(DB::raw('SUM(amount_xaf + fees) as total_gross'))
                ->value('total_gross') ?? 0;

        $fraisSouscriptionMois = \DB::table('fcp_movements')
                ->where('user_id', $client->id)
                ->where('product_id', $productId)
                ->whereDate('date_operation', '>=', $dateN1->copy()->addDay()->toDateString())
                ->whereDate('date_operation', '<=', $dateN->toDateString())
                ->where('nb_parts_change', '>', 0)
                ->sum('fees') ?? 0;

        $vlN = \App\Models\AssetValue::where('product_id', $productId)->where('date_vl', '<=', $dateN->toDateString())->orderBy('date_vl', 'desc')->value('vl') ?? (float)$product->vl;
        $vlN1 = \App\Models\AssetValue::where('product_id', $productId)->where('date_vl', '<=', $dateN1->toDateString())->orderBy('date_vl', 'desc')->value('vl') ?? (float)$product->vl;

        $montantRacheteeMois = abs(\DB::table('fcp_movements')
            ->where('user_id', $client->id)
            ->where('product_id', $productId)
            ->whereDate('date_operation', '>=', $dateN1->copy()->addDay()->toDateString())
            ->whereDate('date_operation', '<=', $dateN->toDateString())
            ->where('nb_parts_change', '<', 0)
            ->sum('amount_xaf')) ?? 0;

        // --- FILTRE D'ACTIVITÉ FCP ---
        if ($partsN <= 0.0001 && $partsN1 <= 0.0001 && $partsSouscritesMois <= 0.0001 && $partsRacheteesMois <= 0.0001) {
            continue;
        }

        $valoN = FinancialDecimal::toFloat(FinancialDecimal::fcpValuation($partsN, $vlN));
        $valoN1 = FinancialDecimal::toFloat(FinancialDecimal::fcpValuation($partsN1, $vlN1));

        // Logic de cumul BRUT
        // Logic de cumul BRUT
        $cumulInvestiBrut = DB::table('fcp_movements')
            ->where('user_id', $client->id)
            ->where('product_id', $productId)
            ->where('nb_parts_change', '>', 0)
            ->whereDate('date_operation', '<=', $dateN->toDateString())
            ->sum('amount_xaf');
            
        $totalRachats = abs(DB::table('fcp_movements')
            ->where('user_id', $client->id)
            ->where('product_id', $productId)
            ->where('nb_parts_change', '<', 0)
            ->whereDate('date_operation', '<=', $dateN->toDateString())
            ->sum('amount_xaf'));

        $cumulInvestiBrut = (float)$cumulInvestiBrut; // Gross is sum of all subscriptions
        $cumulInvestiNet = $cumulInvestiBrut - $totalRachats; // Net is Gross - Rachats
        
        $cumulFees = DB::table('fcp_movements')
            ->where('user_id', $client->id)
            ->where('product_id', $productId)
            ->where('nb_parts_change', '>', 0)
            ->whereDate('date_operation', '<=', $dateN->toDateString())
            ->sum('fees');

        $cumulInvestiNet = $cumulInvestiBrut - (float)$cumulFees - $totalRachats;

        $totalValoN += $valoN;
        $totalValoN1 += $valoN1;

        if ($partsN > 0 || $partsSouscritesMois > 0 || $partsRacheteesMois > 0) {
            $produitsAffiches[] = [
                'nom'               => $product->title,
                'parts_n'           => (float)$partsN,
                'parts_n_1'         => (float)$partsN1,
                'parts_achetees'    => (float)$partsSouscritesMois,
                'parts_rachetees'   => (float)$partsRacheteesMois,
                'total_montant_brut'=> (float)$montantSouscritMois,
                'total_frais'       => (float)$fraisSouscriptionMois,
                'vl_n'              => (float)$vlN,
                'vl_n1'             => (float)$vlN1,
                'valo_n'            => (float)$valoN,
                'valo_n1'           => (float)$valoN1,
                'cumul_investi'     => (float)$cumulInvestiBrut,
                'plus_value'        => (float)($valoN - $cumulInvestiBrut),
                'gain_mensuel'      => (float)(($valoN + $montantRacheteeMois) - ($valoN1 + $montantSouscritMois)),
            ];
        }
    }

    return view('front-end.releves.releve-preview-fcp', [
        'client' => $client,
        'produits' => $produitsAffiches,
        'valorisation_courante' => $totalValoN,
        'valorisation_precedente' => $totalValoN1,
        'date_releve' => $dateN->format('d/m/Y'),
        'date_releve_precedent' => $dateN1->format('d/m/Y'),
        'periode' => ucfirst($dateN->translatedFormat('F Y')),
    ]);
}

public function sendSelected(Request $request)
{
    $clientIds = $_POST['clients'] ?? [];
    
    if (empty($clientIds)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Aucun client reçu'
        ], 400);
    }

        try {
            $periode = now()->subMonth()->locale('fr')->isoFormat('MMMM YYYY');
            $statementDate = now()->startOfMonth()->subDay();
        
        $reportData = [];

        foreach ($clientIds as $clientId) {
            try {
                $client = User::findOrFail($clientId);
                
                // On calcule quels types de PDF générer uniquement si la valorisation est positive.
                $has_pmg = $this->getPositivePmgStatementValue($client->id, $statementDate) > 0;
                $has_fcp = $this->getPositiveFcpStatementValue($client->id, $statementDate) > 0;

                $pdfFiles = [];
                $productLabels = [];
                $type = $request->type; // 'fcp' ou 'pmg' (ou null pour tout, mais on va forcer un type)
                
                if (($type === 'pmg' || empty($type)) && $has_pmg) {
                    $pdfFiles[] = $this->genererPdfPmg($client->id);
                    $productLabels[] = "PMG";
                }
                if (($type === 'fcp' || empty($type)) && $has_fcp) {
                    $pdfFiles[] = $this->genererPdfFcp($client->id);
                    $productLabels[] = "FCP";
                }

                if (empty($pdfFiles)) {
                    $reportData[] = [
                        'Client' => $client->name,
                        'Email' => $client->email,
                        'Date d\'envoi' => now()->format('d/m/Y H:i'),
                        'Opérateur' => auth()->user()->name ?? 'Système',
                        'Produits' => 'Aucun actif',
                        'Statut' => 'Ignoré',
                        'Détails' => 'Pas de transactions actives trouvées'
                    ];
                    continue;
                }

                $emailsCopie = [
                    'ejabea@koriassetmanagement.com',
                ];

                // ✅ Envoyer à releves@ avec l'email client dans le sujet
                Mail::to('onboarding@koriassetmanagement.com')
                    ->bcc($emailsCopie) 
                    ->send(new ReleveClientMail($client, $pdfFiles, $periode));

                $reportData[] = [
                    'Client' => $client->name,
                    'Email' => $client->email,
                    'Date d\'envoi' => now()->format('d/m/Y H:i'),
                    'Opérateur' => auth()->user()->name ?? 'Système',
                    'Produits' => implode(' + ', $productLabels),
                    'Statut' => 'Succès',
                    'Détails' => 'Email(s) envoyé(s) avec ' . count($pdfFiles) . ' PJ'
                ];

            } catch (\Exception $e) {
                Log::error("Erreur globale sendSelected pour client {$clientId}: " . $e->getMessage());
                $reportData[] = [
                    'Client' => isset($client) ? $client->name : "ID: $clientId",
                    'Email' => isset($client) ? $client->email : "N/A",
                    'Date d\'envoi' => now()->format('d/m/Y H:i'),
                    'Opérateur' => auth()->user()->name ?? 'Système',
                    'Produits' => 'N/A',
                    'Statut' => 'Erreur',
                    'Détails' => $e->getMessage()
                ];
            }
        }

        if (!empty($reportData)) {
            $reportPath = $this->genererRapportSynthese($reportData);
            
            // ✅ Enregistrement en base pour Compliance/DG
            \App\Models\StatementBatch::create([
                'user_id' => auth()->id() ?? 1,
                'periode' => $periode,
                'client_count' => count($clientIds),
                'success_count' => collect($reportData)->where('Statut', 'Succès')->count(),
                'error_count' => collect($reportData)->where('Statut', 'Erreur')->count(),
                'report_path' => str_replace(storage_path('app/public/'), '', $reportPath)
            ]);

            // Envoi du rapport à l'admin
            Mail::raw("Synthèse de l'envoi manuel des relevés du " . now()->format('d/m/Y H:i') . ". Veuillez trouver le rapport Excel ci-joint.", function($message) use ($reportPath) {
                $message->to('admin@koriassetmanagement.com')
                        ->subject("📊 RAPPORT D'ENVOI RELEVÉS - " . now()->format('d/m/Y'))
                        ->attach($reportPath, [
                            'as' => 'rapport_envoi_releves_' . now()->format('Ymd_His') . '.csv'
                        ]);
            });
        }

        return response()->json([
            'status' => 'ok',
            'message' => count($clientIds) . ' relevé(s) envoyé(s) avec succès'
        ]);
        
    } catch (\Exception $e) {
        Log::error('Erreur envoi relevés', [
            'error' => $e->getMessage(),
        ]);
        
        return response()->json([
            'status' => 'error',
            'message' => 'Erreur : ' . $e->getMessage()
        ], 500);
    }
}

private function genererPdfPmg(int $clientId): string
{
    $client = User::findOrFail($clientId);
    $productController = app(ProductController::class);
    $currentDate = Carbon::now();

    // 📅 Dates de calcul (Arrêté au mois clos)
    $dateN  = now()->startOfMonth()->subDay(); 

    // 🔍 Récupération des transactions PMG (Catégorie 2)
    $allTransactions = Transaction::where('user_id', $client->id)
        ->where('status', 'Succès')
        ->where('date_validation', '<=', $dateN->toDateString())
        ->whereHas('product', function($q) {
            $q->where('products_category_id', 2);
        })->get();

    $supplemental = \App\Models\TransactionSupplementaire::where('user_id', $client->id)
        ->where('status', 'Succès')
        ->where('date_validation', '<=', $dateN->toDateString())
        ->whereHas('product', function($q) {
            $q->where('products_category_id', 2);
        })->get();

    $merged = $allTransactions->merge($supplemental);

    // Date N fixe : dernier jour du mois clos, meme si un mandat expire ensuite pendant la periode d'envoi.
    $dateN1 = $dateN->copy()->startOfMonth()->subDay();

    // Filtrer et exclure les transactions totalement rachetées avant le début de ce mois clos
    $merged = $merged->reject(function($trans) use ($dateN1) {
        $isSupplementaire = ($trans instanceof \App\Models\TransactionSupplementaire);
        $parentId = $isSupplementaire ? $trans->transaction_id : $trans->id;

        // 1. Si le capital nominal de la transaction principale est déjà à 0
        $parent = \App\Models\Transaction::find($parentId);
        if ($parent && (float)$parent->amount <= 0) {
            // Vérifier s'il a été racheté avant dateN1
            $hasRachatBefore = DB::table('financial_movements')
                ->where('transaction_id', $parentId)
                ->where('type', 'rachat_total')
                ->where('date_operation', '<=', $dateN1->toDateString())
                ->exists();
            if ($hasRachatBefore) {
                return true;
            }
            
            // Si le dernier mouvement avant/à dateN1 montre un capital nul
            $lastM1 = DB::table('financial_movements')
                ->where('transaction_id', $parentId)
                ->where('date_operation', '<=', $dateN1->toDateString())
                ->orderBy('date_operation', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            if ($lastM1 && (float)$lastM1->capital_after <= 0) {
                return true;
            }
            
            // Si aucun mouvement n'a eu lieu avant/à dateN1, et le capital actuel est 0
            $hasMovementsBefore = DB::table('financial_movements')
                ->where('transaction_id', $parentId)
                ->where('date_operation', '<=', $dateN1->toDateString())
                ->exists();
            if (!$hasMovementsBefore) {
                return true;
            }
        }

        // 2. Si un rachat total explicite a été enregistré avant/à dateN1
        $hasRachatTotalBefore = DB::table('financial_movements')
            ->where('transaction_id', $parentId)
            ->where('type', 'rachat_total')
            ->where('date_operation', '<=', $dateN1->toDateString())
            ->exists();
        if ($hasRachatTotalBefore) {
            return true;
        }

        return false;
    });

    $grouped = $merged->groupBy('product_id');

    $totalValoN = 0;
    $totalValoN1 = 0;
    $produitsPreparees = [];

    foreach ($grouped as $productId => $productTrans) {
        $productRecord = \App\Models\Product::find($productId);
        if (!$productRecord) continue;

        $productValoN = 0;
        $productValoN1 = 0;
        $productCapitalTotal = 0;
        $productPrecompteTotal = 0;
        $productGainMensuelTotal = 0;
        $productPertesMensuelles = 0;

        $firstDateVal = Carbon::parse($productTrans->min('date_validation') ?? $productTrans->min('created_at')->toDateString());
        $maxExpiryDate = $productTrans->max('date_echeance');
        $productValuationDate = $dateN->copy();
        if ($maxExpiryDate) {
            $expiryForValuation = Carbon::parse($maxExpiryDate);
            if ($expiryForValuation->betweenIncluded($dateN->copy()->startOfMonth(), $dateN)) {
                $productValuationDate = $expiryForValuation;
            }
        }

        foreach ($productTrans as $trans) {
            $vN = $productController->calculatePMGValorization($trans, $dateN);
            $vN1 = $productController->calculatePMGValorization($trans, $dateN1);

            // Déterminer le bon ID parent pour les requêtes de mouvements financiers
            $isSupplementaire = ($trans instanceof \App\Models\TransactionSupplementaire);
            $parentId = $isSupplementaire ? $trans->transaction_id : $trans->id;

            // Plage de dates incluant l'heure pour capturer tout le dernier jour du mois clos
            $startDatePeriod = $dateN1->copy()->addDay()->startOfDay()->toDateTimeString();
            $endDatePeriod = $dateN->copy()->endOfDay()->toDateTimeString();

            // Sorties du mois pour le calcul du gain (inclut les intérêts payés, les rachats et les frais)
            $totalOutflowsForGainQuery = DB::table('financial_movements')
                ->where('transaction_id', $parentId)
                ->whereIn('type', ['rachat_partiel', 'rachat_total', 'frais_gestion', 'paiement_interets', 'precompte_interets', 'liquidite_interets', 'dividende_interets'])
                ->whereBetween('date_operation', [$startDatePeriod, $endDatePeriod]);
            if ($isSupplementaire) {
                $totalOutflowsForGainQuery->where('comments', 'LIKE', "%versement complémentaire ID {$trans->id}%");
            } else {
                $totalOutflowsForGainQuery->where(function($q) {
                    $q->where('comments', 'NOT LIKE', "%versement complémentaire ID %")
                      ->orWhereNull('comments');
                });
            }
            $totalOutflowsForGain = $totalOutflowsForGainQuery->sum('amount') ?? 0;

            // Sorties affichées en "Pertes" (exclut les rachats et les frais de gestion selon demande)
            $displayedOutflowsQuery = DB::table('financial_movements')
                ->where('transaction_id', $parentId)
                ->whereIn('type', ['precompte_interets', 'liquidite_interets', 'dividende_interets'])
                ->whereBetween('date_operation', [$startDatePeriod, $endDatePeriod]);
            if ($isSupplementaire) {
                $displayedOutflowsQuery->where('comments', 'LIKE', "%versement complémentaire ID {$trans->id}%");
            } else {
                $displayedOutflowsQuery->where(function($q) {
                    $q->where('comments', 'NOT LIKE', "%versement complémentaire ID %")
                      ->orWhereNull('comments');
                });
            }
            $displayedOutflows = $displayedOutflowsQuery->sum('amount') ?? 0;

            // Calcul du gain mensuel de cette transaction
            $mvtCapQuery = DB::table('financial_movements')
                ->where('transaction_id', $parentId)
                ->where('type', 'capitalisation_interets')
                ->whereBetween('date_operation', [$startDatePeriod, $endDatePeriod]);
            if ($isSupplementaire) {
                $mvtCapQuery->where('comments', 'LIKE', "%versement complémentaire ID {$trans->id}%");
            } else {
                $mvtCapQuery->where(function($q) {
                    $q->where('comments', 'NOT LIKE', "%versement complémentaire ID %")
                      ->orWhereNull('comments');
                });
            }
            $mvtCap = $mvtCapQuery->first();

            $currentTransGain = 0;
            if ($mvtCap) {
                $dateCap = Carbon::parse($mvtCap->date_operation);
                $joursA = $this->productController->calculate30_360Days($dateN1, $dateCap);
                $joursB = $this->productController->calculate30_360Days($dateCap, $dateN);
                $gA = ($mvtCap->capital_before * ($trans->vl_buy/100) * $joursA) / 360;
                $gB = ($mvtCap->capital_after * ($trans->vl_buy/100) * $joursB) / 360;
                $currentTransGain = ($gA + $gB);
            } else {
                $currentTransGain = ($vN + $totalOutflowsForGain) - $vN1;
                
                // Si c'est un nouveau produit (N-1 = 0), on déduit le capital pour ne montrer que les intérêts
                if ($vN1 <= 0 && $vN > 0) {
                    $currentTransGain -= (float)$trans->amount;
                }
            }

            // --- FILTRE D'ACTIVITÉ ---
            // On enlève les placements échus avant le début du mois du relevé
            $expiryDate = Carbon::parse($trans->date_echeance);
            if ($expiryDate->lt($dateN->copy()->startOfMonth())) {
                continue;
            }

            $precQuery = DB::table('financial_movements')
                ->where('transaction_id', $parentId)
                ->where('type', 'precompte_interets');
            if ($isSupplementaire) {
                $precQuery->where('comments', 'LIKE', "%versement complémentaire ID {$trans->id}%");
            } else {
                $precQuery->where(function($q) {
                    $q->where('comments', 'NOT LIKE', "%versement complémentaire ID %")
                      ->orWhereNull('comments');
                });
            }
            $prec = $precQuery->value('amount') ?? 0;

            $productValoN += $vN;
            $productValoN1 += $vN1;

            // Calcul du capital à la date de fin du relevé (prenant en compte les capitalisations et les rachats)
            $transCapital = (float)$trans->amount;
            if ($trans->product->products_category_id == 2) { // PMG
                $lastMQuery = DB::table('financial_movements')
                    ->where('transaction_id', $parentId)
                    ->whereIn('type', ['capitalisation_interets', 'rachat_partiel', 'rachat_total'])
                    ->where('date_operation', '<=', $dateN->toDateString());
                if ($isSupplementaire) {
                    $lastMQuery->where('comments', 'LIKE', "%versement complémentaire ID {$trans->id}%");
                } else {
                    $lastMQuery->where(function($q) {
                        $q->where('comments', 'NOT LIKE', "%versement complémentaire ID %")
                          ->orWhereNull('comments');
                    });
                }
                $lastM = $lastMQuery->orderBy('date_operation', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();
                
                if ($lastM) {
                    if ($lastM->type === 'rachat_total' || (float)$lastM->capital_after <= 0) {
                        // Si racheté ce mois-ci, on garde le capital avant rachat pour affichage historique dans ce relevé
                        $startOfMonthStr = $dateN->copy()->startOfMonth()->toDateString();
                        if ($lastM->date_operation >= $startOfMonthStr) {
                            $transCapital = (float)$lastM->capital_before;
                        } else {
                            $transCapital = 0;
                        }
                    } else {
                        $transCapital = (float)$lastM->capital_after;
                    }
                }
            }

            $productCapitalTotal += $transCapital;
            $productPrecompteTotal += (float)$prec;
            $productPertesMensuelles += $displayedOutflows;
            $productGainMensuelTotal += $currentTransGain;
        }

        $capNetTotal = $productCapitalTotal - $productPrecompteTotal;
        $totalValoN += $productValoN;
        $totalValoN1 += ($productValoN - $productGainMensuelTotal);

        if ($productCapitalTotal > 0 || $productValoN > 0 || $productValoN1 > 0) {
            $produitsPreparees[] = (object)[
                'nom' => $productRecord->title,
                'capital' => $productCapitalTotal,
                'taux' => $productTrans->first()->vl_buy,
                'valo_n' => $productValoN,
                'valo_n1' => $productValoN1,
                'gain_mensuel' => max(0, round($productGainMensuelTotal, 0)),
                'perte_mensuelle' => round($productPertesMensuelles, 0),
                'gain_total' => max(0, $productValoN - $capNetTotal),
                'souscription' => $firstDateVal->format('d/m/Y'),
                'date_echeance' => $maxExpiryDate ? Carbon::parse($maxExpiryDate)->format('d/m/Y') : '-',
                'date_valorisation' => $productValuationDate->format('d/m/Y'),
                'date_valorisation_raw' => $productValuationDate->toDateString(),
                'produit_jeune' => $firstDateVal->gt($dateN1) ? 1 : 0,
            ];
        }
    }

    $periode = ucfirst($dateN->translatedFormat('F Y'));
    $dateReleveAffichee = $dateN->copy();
    if (count($produitsPreparees) === 1 && !empty($produitsPreparees[0]->date_valorisation_raw)) {
        $dateReleveAffichee = Carbon::parse($produitsPreparees[0]->date_valorisation_raw);
    }

    /* ---------------- Génération du PDF ---------------- */

    try {
        $pdf = Pdf::loadView('front-end.releves.releve-preview', [
            'client' => $client,
            'produits' => $produitsPreparees,
            'valorisation_precedente' => $totalValoN1,
            'valorisation_courante' => $totalValoN,
            'date_releve_precedent' => $dateN1->format('d/m/Y'),
            'date_releve' => $dateReleveAffichee->format('d/m/Y'),
            'liquidite_pmg' => $this->productController->getPmgAvailableLiquidityBreakdownForUser($client->id, $dateReleveAffichee),
            'periode' => $periode
        ])->setPaper('a4', 'portrait')
          ->setOption('isPhpEnabled', true) 
          ->setOption('isRemoteEnabled', true);

        // Organisation des dossiers
        $subFolder = now()->year . '/' . ucfirst($dateN->translatedFormat('F'));
        $path = storage_path('app/public/releves/' . $subFolder . '/' . str_replace(' ', '_', $client->name));

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $clientSlug = str_replace(' ', '_', strtolower($client->name));
        $monthName = strtolower($dateN->translatedFormat('F'));
        $year = $dateN->format('Y');
        $fileName = "rdc_{$clientSlug}_{$monthName}_{$year}.pdf";
        $filePath = $path . '/' . $fileName;

        $pdf->save($filePath);

        return $filePath;
        
    } catch (\Exception $e) {
        Log::error("❌ Erreur génération PDF client {$clientId}: " . $e->getMessage());
        throw $e;
    }
}

private function genererPdfFcp(int $clientId): string
{
    $client = User::findOrFail($clientId);
    $service = new \App\Services\InvestmentService();
    
    $dateN  = now()->startOfMonth()->subDay(); 
    $dateN1 = $dateN->copy()->startOfMonth()->subDay(); 

    $productIds = DB::table('fcp_movements')
        ->where('user_id', $client->id)
        ->distinct()
        ->pluck('product_id');

    $produitsAffiches = [];
    $totalValoN = 0;
    $totalValoN1 = 0;

    foreach ($productIds as $productId) {
        $product = \App\Models\Product::find($productId);
        if (!$product) continue;

            // 1. Solde TOTAL
            $partsN = \DB::table('fcp_movements')
                ->where('user_id', $client->id)
                ->where('product_id', $productId)
                ->where('date_operation', '<=', $dateN->toDateString())
                ->sum('nb_parts_change') ?? 0;

            $partsN1 = \DB::table('fcp_movements')
                ->where('user_id', $client->id)
                ->where('product_id', $productId)
                ->where('date_operation', '<=', $dateN1->toDateString())
                ->sum('nb_parts_change') ?? 0;

            $partsSouscritesMois = \DB::table('fcp_movements')
                    ->where('user_id', $client->id)
                    ->where('product_id', $productId)
                    ->whereDate('date_operation', '>=', $dateN1->copy()->addDay()->toDateString())
                    ->whereDate('date_operation', '<=', $dateN->toDateString())
                    ->where('nb_parts_change', '>', 0)
                    ->sum('nb_parts_change') ?? 0;

            $partsRacheteesMois = abs(\DB::table('fcp_movements')
                    ->where('user_id', $client->id)
                    ->where('product_id', $productId)
                    ->whereDate('date_operation', '>=', $dateN1->copy()->addDay()->toDateString())
                    ->whereDate('date_operation', '<=', $dateN->toDateString())
                    ->where('nb_parts_change', '<', 0)
                    ->sum('nb_parts_change')) ?? 0;

            $montantSouscritMois = \DB::table('fcp_movements')
                    ->where('user_id', $client->id)
                    ->where('product_id', $productId)
                    ->whereDate('date_operation', '>=', $dateN1->copy()->addDay()->toDateString())
                    ->whereDate('date_operation', '<=', $dateN->toDateString())
                    ->where('nb_parts_change', '>', 0)
                    ->select(DB::raw('SUM(amount_xaf + fees) as total_gross'))
                    ->value('total_gross') ?? 0;

            $fraisSouscriptionMois = \DB::table('fcp_movements')
                    ->where('user_id', $client->id)
                    ->where('product_id', $productId)
                    ->whereDate('date_operation', '>=', $dateN1->copy()->addDay()->toDateString())
                    ->whereDate('date_operation', '<=', $dateN->toDateString())
                    ->where('nb_parts_change', '>', 0)
                    ->sum('fees') ?? 0;

            $vlN = \App\Models\AssetValue::where('product_id', $productId)->where('date_vl', '<=', $dateN->toDateString())->orderBy('date_vl', 'desc')->value('vl') ?? $product->vl;
            $vlN1 = \App\Models\AssetValue::where('product_id', $productId)->where('date_vl', '<=', $dateN1->toDateString())->orderBy('date_vl', 'desc')->value('vl') ?? $product->vl;

            $montantRacheteeMois = abs(\DB::table('fcp_movements')
                ->where('user_id', $client->id)
                ->where('product_id', $productId)
                ->whereDate('date_operation', '>=', $dateN1->copy()->addDay()->toDateString())
                ->whereDate('date_operation', '<=', $dateN->toDateString())
                ->where('nb_parts_change', '<', 0)
                ->sum('amount_xaf')) ?? 0;

            // --- FILTRE D'ACTIVITÉ FCP ---
            if ($partsN <= 0.0001 && $partsN1 <= 0.0001 && $partsSouscritesMois <= 0.0001 && $partsRacheteesMois <= 0.0001) {
                continue;
            }

            $valoN = FinancialDecimal::toFloat(FinancialDecimal::fcpValuation($partsN, $vlN));
            $valoN1 = FinancialDecimal::toFloat(FinancialDecimal::fcpValuation($partsN1, $vlN1));
            
            // Calcul du Cumul BRUT
            $mainAmount = DB::table('transactions')
                ->where('user_id', $client->id)
                ->where('product_id', $productId)
                ->where('status', 'Succès')
                ->where('date_validation', '<=', $dateN->toDateString())
                ->sum('amount');

            $suppAmount = DB::table('transaction_supplementaires')
                ->where('user_id', $client->id)
                ->where('product_id', $productId)
                ->where('status', 'Succès')
                ->where('date_validation', '<=', $dateN->toDateString())
                ->sum('amount');

            $cumulInvestiBrut = (float)$mainAmount + (float)$suppAmount;
            
            $mainFees = DB::table('transactions')
                ->where('user_id', $client->id)
                ->where('product_id', $productId)
                ->where('status', 'Succès')
                ->where('date_validation', '<=', $dateN->toDateString())
                ->sum('fees');
            $suppFees = DB::table('transaction_supplementaires')
                ->where('user_id', $client->id)
                ->where('product_id', $productId)
                ->where('status', 'Succès')
                ->where('date_validation', '<=', $dateN->toDateString())
                ->sum('fees');
            $cumulInvestiNet = $cumulInvestiBrut - ((float)$mainFees + (float)$suppFees);

            $totalValoN += $valoN;
            $totalValoN1 += $valoN1;

            if ($partsN > 0 || $partsSouscritesMois > 0 || $partsRacheteesMois > 0) {
                $produitsAffiches[] = [
                    'nom'               => $product->title,
                    'parts_n'           => (float)$partsN,
                    'parts_n1'          => (float)$partsN1,
                    'parts_souscrites'  => (float)$partsSouscritesMois,
                    'parts_rachetees'    => (float)$partsRacheteesMois,
                    'montant_souscrit'  => (float)$montantSouscritMois,
                    'frais_souscription' => (float)$fraisSouscriptionMois,
                    'vl_n'              => (float)$vlN,
                    'vl_n1'             => (float)$vlN1,
                    'valo_n'            => (float)$valoN,
                    'valo_n1'           => (float)$valoN1,
                    'cumul_investi'     => (float)$cumulInvestiBrut,
                    'plus_value'        => (float)($valoN - $cumulInvestiBrut),
                    'gain_mensuel'      => (float)(($valoN + $montantRacheteeMois) - ($valoN1 + $montantSouscritMois)),
                ];
            }
    }

    $periode = ucfirst($dateN->translatedFormat('F Y'));

    try {
        $pdf = Pdf::loadView('front-end.releves.releve-preview-fcp', [
            'client' => $client,
            'produits' => $produitsAffiches,
            'valorisation_precedente' => $totalValoN1,
            'valorisation_courante' => $totalValoN,
            'date_releve_precedent' => $dateN1->format('d/m/Y'),
            'date_releve' => $dateN->format('d/m/Y'),
            'periode' => $periode
        ])->setPaper('a4', 'portrait');

        $subFolder = now()->year . '/' . ucfirst($dateN->translatedFormat('F'));
        $path = storage_path('app/public/releves/' . $subFolder . '/' . str_replace(' ', '_', $client->name));

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $clientSlug = str_replace(' ', '_', strtolower($client->name));
        $monthName = strtolower($dateN->translatedFormat('F'));
        $year = $dateN->format('Y');
        $fileName = "rdc_{$clientSlug}_{$monthName}_{$year}.pdf";
        $filePath = $path . '/' . $fileName;
        $pdf->save($filePath);

        return $filePath;
        
    } catch (\Exception $e) {
        Log::error("❌ Erreur génération PDF FCP client {$clientId}: " . $e->getMessage());
        throw $e;
    }
}

private function genererRapportSynthese(array $data): string
{
    $fileName = 'rapport_synthese_' . now()->format('Ymd_His') . '.csv';
    $path = storage_path('app/public/releves/rapports/' . $fileName);

    if (!file_exists(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }

    $handle = fopen($path, 'w');
    
    // ✅ Ajout du BOM UTF-8 pour Excel
    fputs($handle, (chr(0xEF) . chr(0xBB) . chr(0xBF)));

    // En-têtes (séparateur point-virgule pour Excel français)
    fputcsv($handle, ['Client', 'Email', 'Date d\'envoi', 'Opérateur', 'Produits', 'Statut', 'Détails'], ';');

    foreach ($data as $line) {
        fputcsv($handle, $line, ';');
    }

    fclose($handle);

    return $path;
}
}
