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

    public function index()
    {
        $clients = User::where('role_id', 2)->get();
        $currentDate = Carbon::now();
        $periode = "Janvier 2026"; // Exemple de période dynamique

        foreach ($clients as $client) {
            $totalValorisation = 0;

            // Initialisation des indicateurs de type de produit
            $client->has_fcp = false;
            $client->has_pmg = false;

            $transactions = Transaction::where('user_id', $client->id)
                ->where(function($q) use ($currentDate) {
                    $q->whereNull('date_echeance')
                      ->orWhere('date_echeance', '>=', $currentDate->format('Y-m-d'));
                })
                ->where('status', 'Succès')
                ->get();

            foreach ($transactions as $trans) {
                // Détection du type de produit pour l'affichage des colonnes
                if ($trans->product->products_category_id == 1) {
                    $client->has_fcp = true;
                    // Calcul valorisation FCP
                    $fcpData = $this->productController->getFcpPortfolioValue($client->id, $trans->product_id, $currentDate);
                    $totalValorisation += $fcpData['valorisation'];
                } elseif ($trans->product->products_category_id == 2) {
                    $client->has_pmg = true;
                    // Calcul valorisation PMG
                    $totalValorisation += $this->productController->calculatePMGValorization($trans, $currentDate);
                }
            }

            // Ajout du check pour les transactions supplémentaires FCP
            if (!$client->has_fcp) {
                $hasFcpSupp = \App\Models\TransactionSupplementaire::where('user_id', $client->id)
                    ->where('status', 'Succès')
                    ->whereHas('product', function($q) {
                        $q->where('products_category_id', 1);
                    })->exists();
                if ($hasFcpSupp) {
                    $client->has_fcp = true;
                }
            }

            $client->portefeuille_total = $totalValorisation;
        }

        return view('front-end.liste-client', compact('clients', 'periode'));
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

    $produitsAffiches = [];
    $totalValoN = 0;
    $totalValoN1 = 0;

    foreach ($transactions as $trans) {
        $valoN = $productController->calculatePMGValorization($trans, $dateN);
        $valoN1 = $productController->calculatePMGValorization($trans, $dateN1);

        $precompte = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->where('type', 'precompte_interets')
            ->value('amount') ?? 0;

        $capNetInitial = (float)$trans->amount - (float)$precompte;
        $dateVal = Carbon::parse($trans->date_validation);
        $estProduitJeune = $dateVal->gt($dateN1) ? 1 : 0;

        // ✅ CALCUL DU GAIN MENSUEL COHÉRENT
        $mvtCap = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->where('type', 'capitalisation_interets')
            ->whereBetween('date_operation', [$dateN1->copy()->addDay()->toDateString(), $dateN->toDateString()])
            ->first();

        if ($mvtCap) {
            // Si capitalisation en janvier, le gain mensuel est la production d'intérêts des deux périodes
            $dateCap = Carbon::parse($mvtCap->date_operation);
            $joursAvant = $dateN1->diffInDays($dateCap->copy()->subDay());
            $joursApres = $dateCap->diffInDays($dateN);
            
            $gainAvant = ($mvtCap->capital_before * ($trans->vl_buy/100) * $joursAvant) / 360;
            $gainApres = ($mvtCap->capital_after * ($trans->vl_buy/100) * $joursApres) / 360;
            $gainMensuel = $gainAvant + $gainApres;
            
            // On ajuste ValoN1 pour qu'elle soit le point de départ logique : ValoN - GainMensuel
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

        // Parts à N
        $partsN = DB::table('fcp_movements')
            ->where('user_id', $client->id)
            ->where('product_id', $productId)
            ->where('date_operation', '<=', $dateN->toDateString())
            ->sum('nb_parts_change');

        // Parts à N-1
        $partsN1 = DB::table('fcp_movements')
            ->where('user_id', $client->id)
            ->where('product_id', $productId)
            ->where('date_operation', '<=', $dateN1->toDateString())
            ->sum('nb_parts_change');

        // VL à N (la plus proche de dateN)
        $latestVlEntry = \App\Models\AssetValue::where('product_id', $productId)
            ->where('date_vl', '<=', $dateN->toDateString())
            ->orderBy('date_vl', 'desc')
            ->first();
        $vlN = $latestVlEntry ? (float)$latestVlEntry->vl : (float)$product->vl;

        // VL à N-1
        $prevVlEntry = \App\Models\AssetValue::where('product_id', $productId)
            ->where('date_vl', '<=', $dateN1->toDateString())
            ->orderBy('date_vl', 'desc')
            ->first();
        $vlN1 = $prevVlEntry ? (float)$prevVlEntry->vl : (float)$product->vl;

        $valoN = $partsN * $vlN;
        $valoN1 = $partsN1 * $vlN1;

        // Investissement total pour ce produit
        $status = $service->getCurrentStatus($client->id, $productId);
        $investi = $status['invested'];

        $totalValoN += $valoN;
        $totalValoN1 += $valoN1;

        $produitsAffiches[] = (object)[
            'nom' => $product->title,
            'parts' => $partsN,
            'parts_n1' => $partsN1,
            'vl_n' => $vlN,
            'vl_n1' => $vlN1,
            'vl_souscription' => \DB::table('fcp_movements')->where('user_id', $client->id)->where('product_id', $productId)->min('vl_applied') ?? $product->vl,
            'valo_n' => $valoN,
            'valo_n1' => $valoN1,
            'gain_mensuel' => $valoN - $valoN1,
            'gain_total' => $valoN - $investi,
            'investi' => $investi,
            'souscription' => DB::table('fcp_movements')->where('user_id', $client->id)->where('product_id', $productId)->min('date_operation')
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
                
                if ($has_pmg) {
                    $pdfFiles[] = $this->genererPdfPmg($client->id);
                    $productLabels[] = "PMG";
                }
                if ($has_fcp) {
                    $pdfFiles[] = $this->genererPdfFcp($client->id);
                    $productLabels[] = "FCP";
                }

                if (empty($pdfFiles)) {
                    $reportData[] = [
                        'Client' => $client->name,
                        'Email' => $client->email,
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
                    'Produits' => implode(' + ', $productLabels),
                    'Statut' => 'Succès',
                    'Détails' => 'Email(s) envoyé(s) avec ' . count($pdfFiles) . ' PJ'
                ];

            } catch (\Exception $e) {
                Log::error("Erreur globale sendSelected pour client {$clientId}: " . $e->getMessage());
                $reportData[] = [
                    'Client' => isset($client) ? $client->name : "ID: $clientId",
                    'Email' => isset($client) ? $client->email : "N/A",
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
    $transactions = Transaction::where('user_id', $client->id)
        ->where('status', 'Succès')
        ->where('date_echeance', '>=', $currentDate->format('Y-m-d'))
        ->whereHas('product', function($q) {
            $q->where('products_category_id', 2);
        })->get();

    $totalValoN = 0;
    $totalValoN1 = 0;
    $produitsPreparees = [];

    foreach ($transactions as $trans) {
        // 1. Calcul des valorisations brutes
        $valoN = $productController->calculatePMGValorization($trans, $dateN);
        $valoN1 = $productController->calculatePMGValorization($trans, $dateN1);

        // 2. Récupération du précompte (Cas Medou)
        $precompte = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->where('type', 'precompte_interets')
            ->value('amount') ?? 0;

        $capitalNetInitial = (float)$trans->amount - (float)$precompte;
        $dateVal = Carbon::parse($trans->date_validation);
        $estProduitJeune = $dateVal->gt($dateN1) ? 1 : 0;

        // 3. ✅ LOGIQUE DE GAIN MENSUEL COHÉRENT (Correctif pour la capitalisation)
        $mvtCap = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->where('type', 'capitalisation_interets')
            ->whereBetween('date_operation', [$dateN1->copy()->addDay()->toDateString(), $dateN->toDateString()])
            ->first();

        if ($mvtCap) {
            // Si capitalisation dans le mois, on calcule le gain réel au prorata
            $dateCap = Carbon::parse($mvtCap->date_operation);
            $joursAvant = $dateN1->diffInDays($dateCap->copy()->subDay());
            $joursApres = $dateCap->diffInDays($dateN);
            
            $gainAvant = ($mvtCap->capital_before * ($trans->vl_buy/100) * $joursAvant) / 360;
            $gainApres = ($mvtCap->capital_after * ($trans->vl_buy/100) * $joursApres) / 360;
            $gainMensuel = $gainAvant + $gainApres;
            
            // On force la valorisation précédente pour la cohérence visuelle
            $affichageValoN1 = $valoN - $gainMensuel;
        } else {
            $gainMensuel = $valoN - $valoN1;
            $affichageValoN1 = $valoN1;
        }

        // 4. Cas du produit jeune
        if ($estProduitJeune) {
            $gainMensuel = $valoN - $capitalNetInitial;
            $affichageValoN1 = $capitalNetInitial;
        }

        $totalValoN += $valoN;
        $totalValoN1 += $affichageValoN1;

        // Préparation des données pour le Blade
        $produitsPreparees[] = (object)[
            'nom' => $trans->product->title,
            'capital' => (float)$trans->amount,
            'taux' => $trans->vl_buy,
            'gain_mensuel' => max(0, round($gainMensuel, 0)),
            'gain_total' => max(0, $valoN - $capitalNetInitial),
            'valo_n' => $valoN,
            'valo_n1' => $affichageValoN1,
            'souscription' => $dateVal->format('d/m/Y'),
            'date_echeance' => Carbon::parse($trans->date_echeance)->format('d/m/Y'),
            'produit_jeune' => $estProduitJeune,
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

        $vlN = \App\Models\AssetValue::where('product_id', $productId)
            ->where('date_vl', '<=', $dateN->toDateString())
            ->orderBy('date_vl', 'desc')
            ->value('vl') ?? $product->vl;

        $vlN1 = \App\Models\AssetValue::where('product_id', $productId)
            ->where('date_vl', '<=', $dateN1->toDateString())
            ->orderBy('date_vl', 'desc')
            ->value('vl') ?? $product->vl;

        $valoN = $partsN * $vlN;
        $valoN1 = $partsN1 * $vlN1;
        
        $status = $service->getCurrentStatus($client->id, $productId);
        $investi = $status['invested'];

        $totalValoN += $valoN;
        $totalValoN1 += $valoN1;

        $produitsAffiches[] = (object)[
            'nom' => $product->title,
            'parts' => $partsN,
            'parts_n1' => $partsN1,
            'vl_n' => $vlN,
            'vl_n1' => $vlN1,
            'vl_souscription' => \DB::table('fcp_movements')->where('user_id', $client->id)->where('product_id', $productId)->min('vl_applied') ?? $product->vl,
            'valo_n' => $valoN,
            'valo_n1' => $valoN1,
            'gain_mensuel' => $valoN - $valoN1,
            'gain_total' => $valoN - $investi,
            'investi' => $investi,
            'souscription' => DB::table('fcp_movements')->where('user_id', $client->id)->where('product_id', $productId)->min('date_operation')
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
    fputcsv($handle, ['Client', 'Email', 'Produits', 'Statut', 'Détails'], ';');

    foreach ($data as $line) {
        fputcsv($handle, $line, ';');
    }

    fclose($handle);

    return $path;
}
}
