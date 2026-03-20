<?php

namespace App\Services;

use App\Models\User;
use App\Http\Controllers\ProductController;
use App\Mail\ReleveClientMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReleveClientService
{
    protected ProductController $productController;

    public function __construct(ProductController $productController)
    {
        $this->productController = $productController;
    }

    /**
     * Point d’entrée unique – envoi massif
     */
    public function envoyerReleves(string $periode): array
    {
        $clients = User::where('role_id', 2)->get();

        $rapport = [
            'envoyes' => 0,
            'ignores' => 0,
            'erreurs' => 0,
        ];

        foreach ($clients as $client) {
            if (!$this->clientEligible($client)) {
                $rapport['ignores']++;
                continue;
            }

            try {
                $this->genererEtEnvoyerReleve($client, $periode);
                $rapport['envoyes']++;
            } catch (\Throwable $e) {
                Log::error('Erreur génération relevé', [
                    'client_id' => $client->id,
                    'message' => $e->getMessage(),
                ]);
                $rapport['erreurs']++;
            }
        }

        return $rapport;
    }

    /**
     * Règles métier d’éligibilité
     */
    private function clientEligible(User $client): bool
    {
        return !empty($client->email) && $client->product_count > 0;
    }

    /**
     * Génération PDF + sauvegarde + envoi
     */
    private function genererEtEnvoyerReleve(User $client, string $periode): void
    {
        $products = $this->productController
            ->getProductsWithGainsUser($client->id);

        $totalFcp = 0;
        $totalPmg = 0;

        foreach ($products as $product) {
            if ($product['type_product'] == 1) {
                $totalFcp +=
                    $product['valorisation_portefeuille_fcp'] +
                    $product['montant_transaction'];
            } else {
                $totalPmg +=
                    $product['gain_month'] +
                    $product['soulte'];
            }
        }

        $data = [
            'customer' => $client,
            'periode' => $periode,
            'productsWithGains' => $products,
            'totalPortefeuilleFcp' => $totalFcp,
            'totalPortefeuillePmg' => $totalPmg,
            'portefeuille_total' => $totalFcp + $totalPmg,
            'date_generation' => Carbon::now(),
        ];

        $pdf = Pdf::loadView('releves.releve-client', $data)
            ->setPaper('A4', 'portrait');

        // === Structure de dossiers ===
        $annee = now()->year;
        $moisLettre = $this->moisFrancais(now()->month);
        $nomClient = $this->normaliserNomClient($client->name);

        $directory = "releves/{$annee}/{$moisLettre}/{$nomClient}";
        $fileName = 'releve-client.pdf';

        Storage::makeDirectory($directory);

        $relativePath = "{$directory}/{$fileName}";
        $absolutePath = storage_path("app/{$relativePath}");

        Storage::put($relativePath, $pdf->output());

        Mail::to($client->email)
            ->send(new ReleveClientMail($client, ['path' => $absolutePath], $periode));
    }

    /**
     * Mois en français
     */
    private function moisFrancais(int $mois): string
    {
        return [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
            4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
            7 => 'Juillet', 8 => 'Août', 9 => 'Septembre',
            10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ][$mois];
    }

    /**
     * Nettoyage nom client pour filesystem
     */
    private function normaliserNomClient(string $nom): string
    {
        $nom = strtoupper($nom);
        $nom = iconv('UTF-8', 'ASCII//TRANSLIT', $nom);
        $nom = preg_replace('/[^A-Z0-9\s]/', '', $nom);
        $nom = preg_replace('/\s+/', ' ', $nom);

        return trim($nom);
    }
}
