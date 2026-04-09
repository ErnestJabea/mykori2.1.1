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

        // --- CALCUL DU PLACEMENT GLOBAL (CAPITAL NET HORS INTÉRÊTS) ---
        // FCP : Somme des versements (souscription/versement_libre) - Rachats
        $fcpSubscriptions = DB::table('fcp_movements')
            ->whereIn('type', ['souscription', 'versement_libre'])
            ->sum('amount_xaf');
        
        $fcpRedemptions = DB::table('fcp_movements')
            ->whereIn('type', ['rachat', 'rachat_partiel', 'rachat_total', 'cloture'])
            ->get()
            ->sum(function($m) {
                return abs((float)$m->nb_parts_change * (float)$m->vl_applied);
            });
        
        $totalAumFcpPlacement = $fcpSubscriptions - $fcpRedemptions;

        // PMG : Somme des mouvements hors capitalisation d'intérêts
        $totalAumPmgPlacement = DB::table('financial_movements')
            ->where('type', '!=', 'capitalisation_interets')
            ->sum('amount'); // Les rachats sont déjà négatifs dans cette table

        $totalPlacementGlobal = $totalAumFcpPlacement + $totalAumPmgPlacement;

        // --- CALCUL DU PLACEMENT DES CLIENTS ACTIFS UNIQUEMENT ---
        $activeUserIds = DB::table('customer_portfolios')
            ->where('status', 'active')
            ->distinct()
            ->pluck('user_id');

        $fcpActivePlacement = DB::table('fcp_movements')
            ->whereIn('user_id', $activeUserIds)
            ->whereIn('type', ['souscription', 'versement_libre'])
            ->sum('amount_xaf') 
            - abs(DB::table('fcp_movements')
                ->whereIn('user_id', $activeUserIds)
                ->whereIn('type', ['rachat', 'rachat_partiel', 'rachat_total', 'cloture'])
                ->get()
                ->sum(fn($m) => abs((float)$m->nb_parts_change * (float)$m->vl_applied)));

        $pmgActivePlacement = DB::table('financial_movements')
            ->whereIn('transaction_id', function($q) use ($activeUserIds) {
                $q->select('id')->from('transactions')->whereIn('user_id', $activeUserIds);
            })
            ->where('type', '!=', 'capitalisation_interets')
            ->sum('amount');

        $totalPlacementActiveClients = max(0, $fcpActivePlacement + $pmgActivePlacement);
        $totalInterets = DB::table('financial_movements')->where('type', 'capitalisation_interets')->sum('amount');
        
        // Plus-value latente FCP (Valeur actuelle - Placement)
        $fcpAumValue = 0;
        $fcpProducts = DB::table('fcp_movements')->distinct()->pluck('product_id');
        foreach ($fcpProducts as $pid) {
            $latestVl = DB::table('asset_values')->where('product_id', $pid)->orderBy('date_vl', 'desc')->value('vl');
            $totalParts = DB::table('fcp_movements')->where('product_id', $pid)->sum('nb_parts_change');
            if ($latestVl && $totalParts > 0) {
                $fcpAumValue += ($totalParts * $latestVl);
            }
        }
        $totalPlusValueFcp = max(0, $fcpAumValue - $totalAumFcpPlacement);

        // --- AUM GLOBAL (VALEUR TOTALE SOUS GESTION) ---
        $pmgCurrentAum = DB::table('financial_movements')
            ->whereIn('id', function($query) {
                $query->select(DB::raw('MAX(id)'))->from('financial_movements')->groupBy('transaction_id');
            })->sum('capital_after');
            
        $totalAumConsolide = $fcpAumValue + $pmgCurrentAum;

        // Autres KPIs
        $totalActiveInvestments = Transaction::where('status', 'Succès')->count();
        $totalActiveClients = User::where('role_id', 2)->whereHas('transactions', fn($q) => $q->where('status', 'Succès'))->count();
        $totalInactiveClients = User::where('role_id', 2)->whereDoesntHave('transactions', fn($q) => $q->where('status', 'Succès'))->count();

        $lastMonth = Carbon::now()->subMonth();
        $totalStatementsLastMonth = DB::table('statement_batches')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();

        // 2. Répartition AUM par Catégorie (FCP vs PMG)
        $aumByType = [
            (object)['category' => 'Fonds Communs (FCP)', 'total' => $fcpAumValue],
            (object)['category' => 'Mandats (PMG)', 'total' => $pmgCurrentAum]
        ];

        // 3. Top 5 Produits par AUM
        $topProducts = DB::table('transactions')
            ->join('products', 'transactions.product_id', '=', 'products.id')
            ->where('transactions.status', 'Succès')
            ->select('products.title', DB::raw('SUM(transactions.amount) as total'))
            ->groupBy('products.title')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        // 4. Flux en attente de signature DG
        $pendingTransactions = Transaction::with(['user', 'product'])->where('status', 'En attente')->where('is_dg_validated', 0)->get()->map(fn($t) => tap($t, fn($t) => $t->type_flux = 'main'));
        $pendingSupps = TransactionSupplementaire::with(['user', 'product'])->where('status', 'En attente')->where('is_dg_validated', 0)->get()->map(fn($t) => tap($t, fn($t) => $t->type_flux = 'supp'));
        $allPending = $pendingTransactions->merge($pendingSupps)->sortByDesc('created_at');

        // 5. Derniers Flux
        $recentSuccessFlows = Transaction::with(['user', 'product'])->where('status', 'Succès')->orderBy('updated_at', 'desc')->limit(10)->get();

        // 6. Alertes Échéances
        $today = Carbon::now();
        $thirtyDaysLater = $today->copy()->addDays(30);
        $expiringMandats = Transaction::with(['user', 'product'])->where('status', 'Succès')->whereHas('product', fn($q) => $q->where('products_category_id', 2))->whereBetween('date_echeance', [$today->toDateString(), $thirtyDaysLater->toDateString()])->get();

        return view('front-end.dg.dashboard', [
            'totalClients' => $totalClients,
            'totalAum' => $totalAumConsolide,
            'totalInterets' => $totalInterets + $totalPlusValueFcp,
            'totalAumFcp' => $totalAumFcpPlacement,
            'totalAumPmg' => $totalAumPmgPlacement,
            'totalActiveInvestments' => $totalPlacementGlobal, // On garde ce nom pour la compatibilité vue si nécessaire ou on utilise le nouveau
            'totalPlacementGlobal' => $totalPlacementGlobal,
            'totalActiveClients' => $totalActiveClients,
            'totalInactiveClients' => $totalInactiveClients,
            'totalStatementsLastMonth' => $totalStatementsLastMonth,
            'totalPlacementActiveClients' => $totalPlacementActiveClients,
            'aumByType' => $aumByType,
            'topProducts' => $topProducts,
            'recentSuccessFlows' => $recentSuccessFlows,
            'expiringMandats' => $expiringMandats,
            'allPending' => $allPending
        ]);
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
