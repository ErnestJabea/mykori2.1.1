<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionSupplementaire;
use App\Models\Product;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\InvestmentService;

class DirectorGeneralController extends Controller
{
    protected $investmentService;

    public function __construct(InvestmentService $investmentService)
    {
        $this->investmentService = $investmentService;

        $this->middleware(function ($request, $next) {
            if (!\App\Services\AccessControlService::can('view_dg')) {
                abort(403, 'Accès réservé au Directeur Général.');
            }
            return $next($request);
        });
    }

    public function dashboard()
    {
        // 1. Indicateurs Globaux
        $totalClients = User::where('role_id', 2)->count();
        $totalAum = Transaction::where('status', 'Succès')->sum('amount');
        $totalInterets = DB::table('financial_movements')->where('type', 'capitalisation_interets')->sum('amount');

        // 2. Répartition AUM par Catégorie (FCP vs PMG)
        $aumByType = DB::table('transactions')
            ->join('products', 'transactions.product_id', '=', 'products.id')
            ->join('products_categories', 'products.products_category_id', '=', 'products_categories.id')
            ->where('transactions.status', 'Succès')
            ->select('products_categories.title as category', DB::raw('SUM(transactions.amount) as total'))
            ->groupBy('products_categories.title')
            ->get();

        // 3. Top 5 Produits par AUM
        $topProducts = DB::table('transactions')
            ->join('products', 'transactions.product_id', '=', 'products.id')
            ->where('transactions.status', 'Succès')
            ->select('products.title', DB::raw('SUM(transactions.amount) as total'))
            ->groupBy('products.title')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        // 4. Derniers Flux de Trésorerie (Succès)
        $recentSuccessFlows = Transaction::with(['user', 'product'])
            ->where('status', 'Succès')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        // 5. Mandats/PMG arrivant bientôt à échéance (Alertes stratégiques)
        $today = Carbon::now();
        $thirtyDaysLater = $today->copy()->addDays(30);
        $expiringMandats = Transaction::with(['user', 'product'])
            ->where('status', 'Succès')
            ->whereHas('product', function($q) {
                $q->where('products_category_id', 2); // Catégorie PMG/Mandats
            })
            ->whereBetween('date_echeance', [$today->toDateString(), $thirtyDaysLater->toDateString()])
            ->get();

        return view('front-end.dg.dashboard', compact(
            'totalClients',
            'totalAum',
            'totalInterets',
            'aumByType',
            'topProducts',
            'recentSuccessFlows',
            'expiringMandats'
        ));
    }

    public function statementsHistory()
    {
        $batches = \App\Models\StatementBatch::with('user')->orderBy('created_at', 'desc')->paginate(15);
        return view('front-end.dg.statements-history', compact('batches'));
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
