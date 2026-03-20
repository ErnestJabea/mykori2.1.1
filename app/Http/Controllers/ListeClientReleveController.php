<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\ProductController; // ✅ Import du moteur de calcul
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
                 ->where('date_echeance', '>=', $currentDate->format('Y-m-d'))
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

    $periode = ucfirst($dateN1->translatedFormat('F Y'));

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
}
