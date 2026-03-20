<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\InvestmentService;
use App\Product;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StatementController extends Controller
{
    protected $investmentService;

    /**
     * Injection du service d'investissement
     */
    public function __construct(InvestmentService $investmentService)
    {
        $this->investmentService = $investmentService;
    }

    /**
     * Génère un relevé FCP personnalisé basé sur un intervalle de dates
     * * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generateCustomFcpStatement(Request $request)
    {
        // 1. Validation des entrées
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'user_id'    => 'nullable|exists:users,id', // Optionnel pour les admins
        ]);

        // 2. Détermination de l'utilisateur (soit l'utilisateur connecté, soit un ID spécifique pour l'admin)
        $userId = $request->user_id ?? Auth::id();
        $client = User::findOrFail($userId);
        $product = Product::findOrFail($request->product_id);

        // 3. Récupération des données via le Service
        // Cette fonction retourne les mouvements, le report de parts et la VL de fin de période
        $data = $this->investmentService->getFcpStatementData(
            $userId, 
            $request->product_id, 
            $request->start_date, 
            $request->end_date
        );

        // 4. Préparation du nom de fichier
        $fileName = "releve_FCP_" . str_replace(' ', '_', $product->title) . "_" . $request->start_date . "_au_" . $request->end_date . ".pdf";

        // 5. Chargement de la vue PDF spécifique avec les données filtrées
        $pdf = Pdf::loadView('front-end.releves.releve-history-fcp', [
            'data'      => $data,
            'product'   => $product,
            'client'    => $client,
            'startDate' => Carbon::parse($request->start_date),
            'endDate'   => Carbon::parse($request->end_date)
        ]);

        // Configuration optionnelle du PDF (Orientation, marges)
        $pdf->setPaper('a4', 'portrait');

        // 6. Retourne le téléchargement au client
        return $pdf->download($fileName);
    }
}