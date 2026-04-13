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

class ListeClientReleveController extends Controller
{
    protected $productController;

    public function __construct(ProductController $productController)
    {
        $this->productController = $productController;
    }

    public function index($type = 'all')
    {
        $allClients = User::where('role_id', 2)->get();
        $currentDate = Carbon::now();
        $periode = $currentDate->copy()->subMonth()->translatedFormat('F Y');

        $filteredClients = collect();

        foreach ($allClients as $client) {
            $totalValorisationFcp = 0;
            $totalValorisationPmg = 0;
            $client->has_fcp = false;
            $client->has_pmg = false;

            // 1. Identifier tous les produits impliqués (Transactions principales + supplémentaires)
            $productIds = DB::table('transactions')
                ->where('user_id', $client->id)
                ->where('status', 'Succès')
                ->distinct()->pluck('product_id')
                ->merge(
                    DB::table('transaction_supplementaires')
                    ->where('user_id', $client->id)
                    ->where('status', 'Succès')
                    ->distinct()->pluck('product_id')
                )->unique();

            $processedFcpProducts = [];

            foreach ($productIds as $pid) {
                $product = \App\Models\Product::find($pid);
                if (!$product) continue;

                if ($product->products_category_id == 1) {
                    $client->has_fcp = true;
                    if (!in_array($pid, $processedFcpProducts)) {
                        $fcpData = $this->productController->getFcpPortfolioValue($client->id, $pid, $currentDate);
                        $totalValorisationFcp += $fcpData['valorisation'];
                        $processedFcpProducts[] = $pid;
                    }
                } elseif ($product->products_category_id == 2) {
                    // Pour PMG, on récupère toutes les transactions actives à cette date
                    $pmgTrans = Transaction::where('user_id', $client->id)
                        ->where('product_id', $pid)
                        ->where('status', 'Succès')
                        ->where(function($q) use ($currentDate) {
                            $q->whereNull('date_echeance')
                              ->orWhere('date_echeance', '>=', $currentDate->format('Y-m-d'));
                        })->get();
                    
                    $pmgSupp = \App\Models\TransactionSupplementaire::where('user_id', $client->id)
                        ->where('product_id', $pid)
                        ->where('status', 'Succès')
                        ->where(function($q) use ($currentDate) {
                            $q->whereNull('date_echeance')
                              ->orWhere('date_echeance', '>=', $currentDate->format('Y-m-d'));
                        })->get();

                    $allPmg = $pmgTrans->merge($pmgSupp);
                    if ($allPmg->isNotEmpty()) {
                        $client->has_pmg = true;
                        foreach ($allPmg as $pt) {
                            $totalValorisationPmg += $this->productController->calculatePMGValorization($pt, $currentDate);
                        }
                    }
                }
            }

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
    
    $dateN  = Carbon::now()->subMonth()->endOfMonth(); // 31/01/2026
    $dateN1 = Carbon::now()->subMonths(2)->endOfMonth(); // 31/12/2025 

    $transactions = Transaction::where('user_id', $client->id)
        ->where('status', 'Succès')
        ->where('date_echeance', '>=', Carbon::now()->format('Y-m-d'))
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

        foreach ($productTrans as $trans) {
            $vN = $productController->calculatePMGValorization($trans, $dateN);
            $vN1 = $productController->calculatePMGValorization($trans, $dateN1);

            // Sorties du mois (Rachats partiels, Paiement intérêts)
            $mensualOutflows = DB::table('financial_movements')
                ->where('transaction_id', $trans->id)
                ->whereIn('type', ['rachat_partiel', 'paiement_interets', 'precompte_interets', 'dividende_interets'])
                ->whereBetween('date_operation', [$dateN1->copy()->addDay()->toDateString(), $dateN->toDateString()])
                ->sum('amount') ?? 0;

            // Calcul du gain mensuel de cette transaction
            $mvtCap = DB::table('financial_movements')
                ->where('transaction_id', $trans->id)
                ->where('type', 'capitalisation_interets')
                ->whereBetween('date_operation', [$dateN1->copy()->addDay()->toDateString(), $dateN->toDateString()])
                ->first();

            $currentTransGain = 0;
            if ($mvtCap) {
                $dateCap = Carbon::parse($mvtCap->date_operation);
                $joursA = $dateN1->diffInDays($dateCap->copy()->subDay());
                $joursB = $dateCap->diffInDays($dateN);
                $gA = ($mvtCap->capital_before * ($trans->vl_buy/100) * $joursA) / 360;
                $gB = ($mvtCap->capital_after * ($trans->vl_buy/100) * $joursB) / 360;
                $currentTransGain = ($gA + $gB);
            } else {
                $currentTransGain = ($vN + $mensualOutflows) - $vN1;
                
                // Si c'est un nouveau produit (N-1 = 0), on déduit le capital pour ne montrer que les intérêts
                if ($vN1 <= 0 && $vN > 0) {
                    $currentTransGain -= (float)$trans->amount;
                }
            }

            // --- FILTRE D'ACTIVITÉ ---
            // Si le produit est échu avant le début du mois précédent (N-1) ET que son gain est nul
            // cela signifie qu'il est inactif ou a été basculé. On l'ignore.
            $expiryDate = Carbon::parse($trans->date_echeance);
            if ($expiryDate->lt($dateN1->copy()->startOfMonth()) && round($currentTransGain, 0) <= 0) {
                continue;
            }

            $prec = DB::table('financial_movements')
                ->where('transaction_id', $trans->id)
                ->where('type', 'precompte_interets')
                ->value('amount') ?? 0;

            $productValoN += $vN;
            $productValoN1 += $vN1;
            $productCapitalTotal += (float)$trans->amount;
            $productPrecompteTotal += (float)$prec;
            $productPertesMensuelles += $mensualOutflows;
            $productGainMensuelTotal += $currentTransGain;
        }

        $capNetTotal = $productCapitalTotal - $productPrecompteTotal;
        $totalValoN += $productValoN;
        $totalValoN1 += $productValoN1;

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
            'produit_jeune' => $firstDateVal->gt($dateN1) ? 1 : 0,
        ];
    }

    return view('front-end.releves.releve-preview', [
        'client' => $client,
        'produits' => $produitsAffiches,
        'valorisation_courante' => $totalValoN,
        'valorisation_precedente' => $totalValoN1,
        'date_releve' => $dateN->format('d/m/Y'),
        'date_releve_precedent' => $dateN1->format('d/m/Y'),
        'periode' => ucfirst($dateN->translatedFormat('F Y')),
    ]);
}

public function previewFcp(int $clientId)
{
    $client = User::findOrFail($clientId);
    $service = new \App\Services\InvestmentService();
    
    $dateN  = Carbon::now()->subMonth()->endOfMonth(); // Fin du mois dernier
    $dateN1 = Carbon::now()->subMonths(2)->endOfMonth(); // Fin du mois d'avant

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

        $valoN = (float)$partsN * (float)$vlN;
        $valoN1 = (float)$partsN1 * (float)$vlN1;

        // Logic de cumul BRUT
        // Logic de cumul BRUT
        $mainAmount = DB::table('transactions')
            ->where('user_id', $client->id)
            ->where('product_id', $productId)
            ->where('status', 'Succès')
            ->whereDate('date_validation', '<=', $dateN->toDateString())
            ->sum('amount');

        $suppAmount = DB::table('transaction_supplementaires')
            ->where('user_id', $client->id)
            ->where('product_id', $productId)
            ->where('status', 'Succès')
            ->whereDate('date_validation', '<=', $dateN->toDateString())
            ->sum('amount');

        $cumulInvestiBrut = (float)$mainAmount + (float)$suppAmount;
        
        $mainFees = DB::table('transactions')
            ->where('user_id', $client->id)
            ->where('product_id', $productId)
            ->where('status', 'Succès')
            ->whereDate('date_validation', '<=', $dateN->toDateString())
            ->sum('fees');
        $suppFees = DB::table('transaction_supplementaires')
            ->where('user_id', $client->id)
            ->where('product_id', $productId)
            ->where('status', 'Succès')
            ->whereDate('date_validation', '<=', $dateN->toDateString())
            ->sum('fees');
        $cumulInvestiNet = $cumulInvestiBrut - ((float)$mainFees + (float)$suppFees);

        $totalValoN += $valoN;
        $totalValoN1 += $valoN1;

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
            'gain_mensuel'      => (float)($valoN - $valoN1),
        ];
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
        
        $reportData = [];

        foreach ($clientIds as $clientId) {
            try {
                $client = User::findOrFail($clientId);
                
                // On calcule quels types de PDF générer
                $has_pmg = Transaction::where('user_id', $client->id)
                    ->where('status', 'Succès')
                    ->where('date_echeance', '>=', Carbon::now()->format('Y-m-d'))
                    ->whereHas('product', function($q) {
                        $q->where('products_category_id', 2);
                    })->exists();

                $has_fcp = Transaction::where('user_id', $client->id)
                    ->where('status', 'Succès')
                    ->whereNull('date_echeance')
                    ->whereHas('product', function($q) {
                        $q->where('products_category_id', 1);
                    })->exists();
                
                // On check aussi les supps pour le FCP
                if (!$has_fcp) {
                    $has_fcp = \App\Models\TransactionSupplementaire::where('user_id', $client->id)
                        ->where('status', 'Succès')
                        ->whereHas('product', function($q) {
                            $q->where('products_category_id', 1);
                        })->exists();
                }

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
    $dateN  = now()->subMonth()->endOfMonth(); // Ex: 31 Janvier 2026
    $dateN1 = now()->subMonths(2)->endOfMonth(); // Ex: 31 Décembre 2025

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

        foreach ($productTrans as $trans) {
            $vN = $productController->calculatePMGValorization($trans, $dateN);
            $vN1 = $productController->calculatePMGValorization($trans, $dateN1);

            // Sorties du mois (Rachats partiels, Paiement intérêts)
            $mensualOutflows = DB::table('financial_movements')
                ->where('transaction_id', $trans->id)
                ->whereIn('type', ['rachat_partiel', 'paiement_interets', 'precompte_interets', 'dividende_interets'])
                ->whereBetween('date_operation', [$dateN1->copy()->addDay()->toDateString(), $dateN->toDateString()])
                ->sum('amount') ?? 0;

            // Calcul du gain mensuel de cette transaction
            $mvtCap = DB::table('financial_movements')
                ->where('transaction_id', $trans->id)
                ->where('type', 'capitalisation_interets')
                ->whereBetween('date_operation', [$dateN1->copy()->addDay()->toDateString(), $dateN->toDateString()])
                ->first();

            $currentTransGain = 0;
            if ($mvtCap) {
                $dateCap = Carbon::parse($mvtCap->date_operation);
                $joursA = $dateN1->diffInDays($dateCap->copy()->subDay());
                $joursB = $dateCap->diffInDays($dateN);
                $gA = ($mvtCap->capital_before * ($trans->vl_buy/100) * $joursA) / 360;
                $gB = ($mvtCap->capital_after * ($trans->vl_buy/100) * $joursB) / 360;
                $currentTransGain = ($gA + $gB);
            } else {
                $currentTransGain = ($vN + $mensualOutflows) - $vN1;

                // Si c'est un nouveau produit (N-1 = 0), on déduit le capital pour ne montrer que les intérêts
                if ($vN1 <= 0 && $vN > 0) {
                    $currentTransGain -= (float)$trans->amount;
                }
            }

            // --- FILTRE D'ACTIVITÉ ---
            // Si le produit est échu avant le début du mois précédent (N-1) ET que son gain est nul
            // cela signifie qu'il est inactif ou a été basculé. On l'ignore.
            $expiryDate = Carbon::parse($trans->date_echeance);
            if ($expiryDate->lt($dateN1->copy()->startOfMonth()) && round($currentTransGain, 0) <= 0) {
                continue;
            }

            $prec = DB::table('financial_movements')
                ->where('transaction_id', $trans->id)
                ->where('type', 'precompte_interets')
                ->value('amount') ?? 0;

            $productValoN += $vN;
            $productValoN1 += $vN1;
            $productCapitalTotal += (float)$trans->amount;
            $productPrecompteTotal += (float)$prec;
            $productPertesMensuelles += $mensualOutflows;
            $productGainMensuelTotal += $currentTransGain;
        }

        $capNetTotal = $productCapitalTotal - $productPrecompteTotal;
        $totalValoN += $productValoN;
        $totalValoN1 += $productValoN1;

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
            'produit_jeune' => $firstDateVal->gt($dateN1) ? 1 : 0,
        ];
    }

    $periode = ucfirst($dateN->translatedFormat('F Y'));

    /* ---------------- Génération du PDF ---------------- */

    try {
        $pdf = Pdf::loadView('front-end.releves.releve-preview', [
            'client' => $client,
            'produits' => $produitsPreparees,
            'valorisation_precedente' => $totalValoN1,
            'valorisation_courante' => $totalValoN,
            'date_releve_precedent' => $dateN1->format('d/m/Y'),
            'date_releve' => $dateN->format('d/m/Y'),
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

        $fileName = 'releve_pmg_' . $client->id . '_' . now()->format('His') . '.pdf';
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
    
    $dateN  = now()->subMonth()->endOfMonth(); // 31/01/2026
    $dateN1 = now()->subMonths(2)->endOfMonth(); // 31/12/2025 

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

            $valoN = (float)$partsN * (float)$vlN;
            $valoN1 = (float)$partsN1 * (float)$vlN1;
            
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
                'gain_mensuel'      => (float)($valoN - $valoN1),
            ];
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

        $fileName = 'releve_fcp_' . $client->id . '_' . now()->format('His') . '.pdf';
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
