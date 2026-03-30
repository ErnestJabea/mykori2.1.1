<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionSupplementaire;
use App\Models\AssetValue;
use App\Models\Product;
use App\Models\FinancialMovement;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\InvestmentService;

class ComplianceController extends Controller
{
    protected $investmentService;

    public function __construct(InvestmentService $investmentService)
    {
        $this->investmentService = $investmentService;

        $this->middleware(function ($request, $next) {
            if (!\App\Services\AccessControlService::can('view_compliance')) {
                abort(403, 'Accès réservé au profil Compliance.');
            }
            return $next($request);
        });
    }

    /**
     * Tableau de bord Compliance
     */
    public function dashboard()
    {
        $today = Carbon::now();
        
        // Statistiques globales
        $totalClients = User::where('role_id', 2)->count();
        $totalTransactions = Transaction::where('status', 'Succès')->count();
        
        // Mouvements récents pour le flux de contrôle
        $recentTransactions = Transaction::with(['user', 'product'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recentSupps = TransactionSupplementaire::with(['user', 'product'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('front-end.compliance.dashboard', compact(
            'totalClients', 
            'totalTransactions', 
            'recentTransactions',
            'recentSupps'
        ));
    }

    /**
     * Liste complète des clients pour audit
     */
    public function clients(Request $request)
    {
        $query = User::where('role_id', 2);

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        $clients = $query->orderBy('name', 'asc')->paginate(20);

        return view('front-end.compliance.clients', compact('clients'));
    }

    /**
     * Historique complet d'un client (Audit trail)
     */
    public function clientHistory(User $client, Request $request)
    {
        $startDate = $request->get('start_date', '2000-01-01');
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        // 1. Informations de base
        
        // 2. Toutes les transactions (Principales)
        $transactions = Transaction::where('user_id', $client->id)
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->orderBy('created_at', 'asc')
            ->get();

        // 3. Toutes les transactions supplémentaires
        $supplements = TransactionSupplementaire::where('user_id', $client->id)
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->orderBy('created_at', 'asc')
            ->get();

        // 4. Mouvements financiers (PMG) - Nécessite une jointure car pas de user_id direct
        $pmgMovements = DB::table('financial_movements')
            ->join('transactions', 'financial_movements.transaction_id', '=', 'transactions.id')
            ->where('transactions.user_id', $client->id)
            ->whereBetween('financial_movements.date_operation', [$startDate, $endDate])
            ->select('financial_movements.*')
            ->get();
            
        // 5. Mouvements FCP - Possède déjà user_id
        $fcpMovements = DB::table('fcp_movements')
            ->where('user_id', $client->id)
            ->whereBetween('date_operation', [$startDate, $endDate])
            ->get();

        return view('front-end.compliance.client-detail', compact(
            'client', 
            'transactions', 
            'supplements', 
            'pmgMovements', 
            'fcpMovements',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Évolution des Valeurs Liquidatives
     */
    public function vlHistory(Request $request)
    {
        $products = Product::where('products_category_id', 1)->get();
        $selectedProductId = $request->get('product_id', $products->first()?->id);
        
        $vls = AssetValue::with('product')
            ->where('product_id', $selectedProductId)
            ->orderBy('date_vl', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('front-end.compliance.vl-history', compact('products', 'vls', 'selectedProductId'));
    }

    /**
     * Exportation de données personnalisée
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'transactions');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $clientId = $request->get('client_id');
        $productIds = $request->get('product_ids');

        $fileName = 'export_' . $type . '_' . now()->format('YmdHis') . '.csv';
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($type, $startDate, $endDate, $clientId, $productIds) {
            $file = fopen('php://output', 'w');
            fputs($file, (chr(0xEF) . chr(0xBB) . chr(0xBF))); // BOM UTF-8

            if ($type == 'transactions') {
                fputcsv($file, ['Date', 'Client', 'Produit', 'Montant', 'Statut', 'Ref'], ';');
                $query = Transaction::with(['user', 'product']);
                if ($startDate) $query->where('created_at', '>=', $startDate);
                if ($endDate) $query->where('created_at', '<=', $endDate . ' 23:59:59');
                if ($clientId) $query->where('user_id', $clientId);
                
                $query->chunk(100, function($rows) use ($file) {
                    foreach ($rows as $row) {
                        fputcsv($file, [
                            $row->created_at->format('d/m/Y'),
                            $row->user->name,
                            $row->product->title,
                            $row->amount,
                            $row->status,
                            $row->ref
                        ], ';');
                    }
                });
            } elseif ($type == 'vls') {
                fputcsv($file, ['Date VL', 'Produit', 'Valeur (XAF)'], ';');
                $query = AssetValue::with('product')->orderBy('date_vl', 'desc');
                
                // Filtres Optionnels
                if ($productIds) {
                    $query->whereIn('product_id', (array)$productIds);
                }
                
                if ($startDate) {
                    try {
                        $start = Carbon::parse($startDate)->format('Y-m-d');
                        $query->where('date_vl', '>=', $start);
                    } catch (\Exception $e) {}
                }
                
                if ($endDate) {
                    try {
                        $end = Carbon::parse($endDate)->format('Y-m-d');
                        $query->where('date_vl', '<=', $end);
                    } catch (\Exception $e) {}
                }

                $query->chunk(100, function($rows) use ($file) {
                    foreach ($rows as $row) {
                        fputcsv($file, [
                            $row->date_vl,
                            $row->product->title ?? 'N/A',
                            $row->vl
                        ], ';');
                    }
                });
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function statementsHistory()
    {
        $batches = \App\Models\StatementBatch::with('user')->orderBy('created_at', 'desc')->paginate(15);
        return view('front-end.compliance.statements-history', compact('batches'));
    }

    public function downloadBatchReport($id)
    {
        $batch = \App\Models\StatementBatch::findOrFail($id);
        $path = storage_path('app/public/' . $batch->report_path);
        if (file_exists($path)) {
            return response()->download($path);
        }
        return back()->with('error', 'Fichier introuvable.');
    }
}
