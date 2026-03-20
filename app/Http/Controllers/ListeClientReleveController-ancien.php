<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Controllers\ProductController;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

use illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReleveClientMail;
use App\Product;
use App\Transaction;
use App\TransactionSupplementaire;

class ListeClientReleveController extends Controller
{
    protected ProductController $productController;

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
            ->where('status', 'Succès')
            ->get();

        foreach ($transactions as $trans) {
            // Détection du type de produit pour l'affichage des colonnes
            if ($trans->product->products_category_id == 1) {
                $client->has_fcp = true;
                // Calcul valorisation FCP
                $fcpData = $this->productController->getFcpPortfolioValue($client->id, $trans->product_id, $currentDate);
                $totalValorisation += $fcpData['valorisation'];
            } 
            elseif ($trans->product->products_category_id == 2) {
                $client->has_pmg = true;
                // Calcul valorisation PMG
                $totalValorisation += $this->productController->calculatePMGValorization($trans, $currentDate);
            }
        }

        $client->portefeuille_total = $totalValorisation;
    }

    return view('front-end.liste-client', compact('clients', 'periode'));
}


    public function send(Request $request)
    {
        $clientIds = $request->input('clients', []);

        if (empty($clientIds)) {
            return response()->json([
                'message' => 'Aucun client sélectionné'
            ], 422);
        }

        // L’envoi réel sera implémenté à l’étape suivante
        // Ici, on valide juste le flux

        return response()->json([
            'status' => 'ok',
            'clients' => count($clientIds),
        ]);
    }


    public function previewPmg(int $clientId)
    {
        $client = User::findOrFail($clientId);

        // 📅 Dates de référence
        $dateN  = now()->subMonth()->endOfMonth();
        $dateN1 = now()->subMonths(2)->endOfMonth();

        // 🔁 Calculs indépendants
        $valorisationN  = $this->calculerValorisationPmg($client->id, $dateN);
        $valorisationN1 = $this->calculerValorisationPmg($client->id, $dateN1);

        $total_placement = $valorisationN['portefeuille_total'];

        

        Log::info('Valorisation N', ['data' => $valorisationN]);
        Log::info('Valorisation N-1', ['data' => $valorisationN1]);
        Log::info('Date N', ['data' => $dateN]);
        Log::info('Date N-1', ['data' => $dateN1]);



        return view('front-end.releves.releve-preview', [
            'client' => $client,

            // 🗓️ Dates affichées
            'periode'               => $dateN->translatedFormat('F Y'),
            'date_releve'           => $dateN->format('d/m/Y'),
            'date_releve_precedent' => $dateN1->format('d/m/Y'),

            // 📊 Valorisation
            'valorisation_courante'   => $valorisationN['portefeuille_total'],
            'valorisation_precedente' => $valorisationN1['portefeuille_total'],
            'total_placement' => $total_placement,
            

            // 📋 Produits PMG du mois courant
            'produits' => $valorisationN['produits'],
        ]);
    }

private function getProductsWithGainsUserAtDate($user_id, Carbon $dateReference)
{
    $products = Product::all();
    $result = [];
    $total_placement = 0;
    
    foreach ($products as $product) {
        $transactions = Transaction::where('user_id', $user_id)
            ->where('status', 'Succès')
            ->where('product_id', $product->id)
            ->get();

        $additionalTransactions = TransactionSupplementaire::where('user_id', $user_id)
            ->where('status', 'Succès')
            ->where('product_id', $product->id)
            ->get();

        $allTransactions = $transactions->merge($additionalTransactions);

        if ($allTransactions->isEmpty()) {
            continue;
        }

        foreach ($allTransactions as $transaction) {
            
            // ✅ Calculer uniquement pour les produits PMG
            if ($product->products_category_id != 2) {
                continue;
            }

            $date_echeance_exploded = explode(" ", $transaction->date_echeance)[0];
            $date_validation_exploded = explode(" ", $transaction->date_validation)[0];

            // ✅ Vérifier que la souscription est AVANT ou ÉGALE à la date de référence
            if (strtotime($date_validation_exploded) > strtotime($dateReference->format('Y-m-d'))) {
                Log::info("❌ Produit pas encore souscrit à la date de référence", [
                    'product' => $product->title,
                    'date_validation' => $date_validation_exploded,
                    'date_reference' => $dateReference->format('Y-m-d'),
                ]);
                continue;
            }

            // ✅ Vérifier que le produit est actif à la date de référence (pas encore échu)
            if (strtotime($date_echeance_exploded) <= strtotime($dateReference->format('Y-m-d'))) {
                Log::info("❌ Produit échu à la date de référence", [
                    'product' => $product->title,
                    'date_echeance' => $date_echeance_exploded,
                    'date_reference' => $dateReference->format('Y-m-d'),
                ]);
                continue;
            }

            // ✅ Calculer le nombre de jours entre la souscription et la date de référence
            $dateValidation = Carbon::parse($date_validation_exploded);
            $joursEcoules = $dateValidation->diffInDays($dateReference);

            // ✅ Déterminer si le produit est "jeune" (< 27 jours)
            $produitJeune = $joursEcoules < 27 ? 1 : 0;

            Log::info("📅 Analyse ancienneté produit", [
                'product' => $product->title,
                'date_validation' => $dateValidation->format('Y-m-d'),
                'date_reference' => $dateReference->format('Y-m-d'),
                'jours_ecoules' => $joursEcoules,
                'produit_jeune' => $produitJeune,
            ]);

            $total_placement += $transaction->amount;

            // ✅ Calculer le gain à la date de référence
            $gainMonth = $this->productController->calculatePMGMonthlyGain(
                $transaction->amount,
                $transaction->vl_buy,
                $date_validation_exploded,
                $date_echeance_exploded,
                $dateReference->format('Y-m-d')
            );

            $gainMensuel = $this->productController->gainMonthPmg(
                $transaction->amount,
                $transaction->vl_buy
            );

            Log::info("💰 Gain calculé", [
                'product' => $product->title,
                'jours_ecoules' => $joursEcoules,
                'produit_jeune' => $produitJeune ? 'OUI' : 'NON',
                'gain_month' => $gainMonth,
                'gain_mensuel' => $gainMensuel,
            ]);

            $result[] = [
                'product_name' => $product->title,
                'vl_achat' => $transaction->vl_buy,
                'gain_month' => $gainMonth,
                'gain_mensuel' => $gainMensuel,
                'montant_transaction' => $transaction->amount,
                'type_product' => $product->products_category_id,
                'souscription' => $transaction->date_validation,
                'date_echeance' => $transaction->date_echeance,
                'soulte' => $transaction->montant_initiale,
                'jours_ecoules' => $joursEcoules,
                'produit_jeune' => $produitJeune, // ✅ NOUVELLE VARIABLE
                'total_placement' => $total_placement,
            ];
        }
    }

    return $result;
}

private function calculerValorisationPmg(
    int $clientId,
    Carbon $dateReference
): array {

    Log::info("🔍 Début calcul valorisation PMG", [
        'client_id' => $clientId,
        'date_reference' => $dateReference->format('Y-m-d'),
    ]);

    // ✅ Appeler une version modifiée qui accepte la date de référence
    $products = $this->getProductsWithGainsUserAtDate($clientId, $dateReference);

    $portefeuilleTotal = 0;
    $produits = [];

    foreach ($products as $product) {

        if ($product['type_product'] != 2) {
            continue;
        }

        if (empty($product['date_echeance'])) {
            continue;
        }

        $dateEcheance = Carbon::parse($product['date_echeance']);

        if ($dateEcheance->lte($dateReference)) {
            continue;
        }

        // ✅ Calcul du montant : gain_month + soulte
        $montant = $product['gain_month'] + $product['soulte'];

        Log::info("💰 Calcul produit", [
            'product_name' => $product['product_name'],
            'produit_jeune' => $product['produit_jeune'] ?? 0,
            'gain_month' => $product['gain_month'],
            'soulte' => $product['soulte'],
            'montant_total' => $montant,
        ]);

        $portefeuilleTotal += $montant;

        $produits[] = [
            'product_name'        => $product['product_name'],
            'gain_month'          => $product['gain_month'],
            'gain_mensuel'        => $product['gain_mensuel'],
            'montant_transaction' => $product['montant_transaction'],
            'montant'             => $montant,
            'total_placement'     => $product['total_placement'],
            'produit_jeune'       => $product['produit_jeune'] ?? 0,
            'vl'                  => $product['vl_achat'],
            'montant_initial'     => $product['soulte'], // ✅ MONTANT_INITIAL ajouté
            'date_echeance'       => $dateEcheance->format('d/m/Y'),
            'souscription'        => Carbon::parse($product['souscription'])->format('d/m/Y'),
        ];
    }

    Log::info("✅ Fin calcul valorisation PMG", [
        'client_id' => $clientId,
        'date_reference' => $dateReference->format('Y-m-d'),
        'portefeuille_total' => $portefeuilleTotal,
        'nombre_produits' => count($produits),
    ]);

    return [
        'portefeuille_total' => $portefeuilleTotal,
        'produits'           => $produits,
    ];
}




/* 
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
        // Générer la période une seule fois
        $periode = now()->subMonth()->locale('fr')->isoFormat('MMMM YYYY');
        
        foreach ($clientIds as $clientId) {
            $client = User::findOrFail($clientId);
            $pdfPath = $this->genererPdfPmg($client->id);
            $emailRelay = env('MAIL_RELAY_ADDRESS'); // releves@koriassetmanagement.com
            $emailBCC = env('MAIL_BCC_RELEVES');
            
            // ✅ Passer la période en 3ème paramètre (optionnel)
           $emailsCopie = [
                //'onboarding@koriassetmanagement.com',
                'ejabea@koriassetmanagement.com',
            ];

            Mail::to($client->email)
                ->bcc($emailsCopie) 
                ->send(new ReleveClientMail($client, [$pdfPath], $periode));
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

 */



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
        
        foreach ($clientIds as $clientId) {
            $client = User::findOrFail($clientId);
            $pdfPath = $this->genererPdfPmg($client->id);
            
            $emailsCopie = [
                'ejabea@koriassetmanagement.com',
            ];

            // ✅ Envoyer à releves@ avec l'email client dans le sujet
            Mail::to('onboarding@koriassetmanagement.com')
                ->bcc($emailsCopie) 
                ->send(new ReleveClientMail($client, [$pdfPath], $periode));
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






    public function testSimplePdf()
    {
        $html = '<h1>PDF TEST HARD</h1><p>Local environment</p>';

        $pdf = Pdf::loadHTML($html);

        $path = storage_path('app/pdf-test-hard');

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $file = $path . '/test-hard.pdf';

        $pdf->save($file);

        dd([
            'exists' => file_exists($file),
            'size'   => file_exists($file) ? filesize($file) : null,
            'path'   => $file,
        ]);
    }

private function genererPdfPmg(int $clientId): string
{
    $client = User::findOrFail($clientId);

    $dateN  = now()->subMonth()->endOfMonth();
    $dateN1 = now()->subMonths(2)->endOfMonth();

    Log::info("📅 Dates de calcul", [
        'client_id' => $clientId,
        'client_name' => $client->name,
        'dateN' => $dateN->format('Y-m-d'),
        'dateN1' => $dateN1->format('Y-m-d'),
    ]);

    $valorisationN  = $this->calculerValorisationPmg($client->id, $dateN);
    $valorisationN1 = $this->calculerValorisationPmg($client->id, $dateN1);

    Log::info("💰 Valorisations calculées", [
        'valorisation_N' => $valorisationN['portefeuille_total'],
        'valorisation_N1' => $valorisationN1['portefeuille_total'],
    ]);

    $periode = ucfirst($dateN->translatedFormat('F Y'));

    /* ---------------- Préparation des données avec gain cumulé mensuel ---------------- */
    
    // ✅ Créer un mapping des produits N-1 par nom pour faciliter la comparaison
    $produitsN1Map = [];
    foreach ($valorisationN1['produits'] as $produitN1) {
        $produitsN1Map[$produitN1['product_name']] = $produitN1;
    }

    $produits = collect($valorisationN['produits'])->map(function($produit) use ($produitsN1Map) {
        
        // ✅ Calculer le gain mensuel cumulé (différence entre N et N-1)
        $gainCumuleMensuel = 0;
        
        if (isset($produitsN1Map[$produit['product_name']])) {
            // Produit existait en N-1
            $produitN1 = $produitsN1Map[$produit['product_name']];
            $gainCumuleMensuel = $produit['gain_month'] - $produitN1['gain_month'];
        } else {
            // Produit n'existait pas en N-1 (nouveau produit)
            $gainCumuleMensuel = $produit['gain_month'];
        }

        Log::info("📊 Gain cumulé mensuel calculé", [
            'product_name' => $produit['product_name'],
            'gain_N' => $produit['gain_month'],
            'gain_N1' => isset($produitsN1Map[$produit['product_name']]) ? $produitsN1Map[$produit['product_name']]['gain_month'] : 0,
            'gain_cumule_mensuel' => $gainCumuleMensuel,
        ]);

        return [
            'product_name' => $produit['product_name'],
            'souscription' => $produit['souscription'],
            'date_echeance' => $produit['date_echeance'],
            'montant_transaction' => $produit['montant_transaction'] ?? 0,
            'vl' => $produit['vl'] ?? 0,
            'gain_mensuel' => $produit['gain_mensuel'] ?? 0,
            'gain_month' => $produit['gain_month'] ?? 0,
            'produit_jeune' => $produit['produit_jeune'] ?? 0,
            'gain_cumule_mensuel' => $gainCumuleMensuel,
            'montant_initial' => $produit['montant_initial'] ?? 0, // ✅ AJOUTÉ ICI
        ];
    })->toArray();

    Log::info("📊 Données préparées pour le PDF", [
        'client_id' => $clientId,
        'produits_count' => count($produits),
        'valorisation_N' => $valorisationN['portefeuille_total'],
        'valorisation_N1' => $valorisationN1['portefeuille_total'],
    ]);

    /* ---------------- Génération du PDF ---------------- */

    try {
        $pdf = Pdf::loadView('front-end.releves.releve-preview', [
            'client' => $client,
            'produits' => $produits,
            'valorisation_precedente' => $valorisationN1['portefeuille_total'],
            'valorisation_courante' => $valorisationN['portefeuille_total'],
            'date_releve_precedent' => $dateN1->format('d/m/Y'),
            'date_releve' => $dateN->format('d/m/Y'),
        ]);

        $path = storage_path(
            'app/releves/' . now()->year . '/' . ucfirst($dateN->translatedFormat('F')) . '/' . str_replace(' ', '_', $client->name)
        );

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $filePath = $path . '/releve-pmg.pdf';

        $pdf->save($filePath);

        Log::info("✅ PDF généré avec succès", [
            'client_id' => $clientId,
            'filepath' => $filePath,
        ]);

        return $filePath;
        
    } catch (\Exception $e) {
        Log::error("❌ Erreur génération PDF", [
            'client_id' => $clientId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        throw $e;
    }
}
}
