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

class BackofficeController extends Controller
{
    protected $investmentService;

    public function __construct(InvestmentService $investmentService)
    {
        $this->investmentService = $investmentService;

        $this->middleware(function ($request, $next) {
            $canViewBO = \App\Services\AccessControlService::can('view_backoffice');
            $canValidateCompliance = \App\Services\AccessControlService::can('validate_compliance');

            if (!$canViewBO && !$canValidateCompliance) {
                abort(403, 'Accès réservé aux profils autorisés (Backoffice / Contrôle).');
            }
            return $next($request);
        });
    }

    public function dashboard()
    {
        $today = Carbon::now();
        $thirtyDaysLater = $today->copy()->addDays(30);

        // Transactions en attente globale
        $pendingCount = Transaction::where('is_dg_validated', 0)->count();
        $pendingSuppCount = TransactionSupplementaire::where('is_dg_validated', 0)->count();

        // Produits PMG arrivant à expiration sous 30 jours
        $expiringPmg = Transaction::with(['user', 'product'])
            ->where('status', 'Succès')
            ->whereHas('product', function($q) {
                $q->where('products_category_id', 2);
            })
            ->whereBetween('date_echeance', [$today->toDateString(), $thirtyDaysLater->toDateString()])
            ->get();

        // Récupérer les 10 dernières opérations en attente
        $pendingTransactions = Transaction::with(['user', 'product'])
            ->where('status', '!=', 'Succès')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('front-end.backoffice.dashboard', compact(
            'pendingCount',
            'pendingSuppCount',
            'expiringPmg',
            'pendingTransactions'
        ));
    }

    public function transactions(Request $request)
    {
        $query = Transaction::with(['user', 'product']);
        
        if ($request->get('filter') == 'pending') {
            $query->where('status', '!=', 'Succès');
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('front-end.backoffice.transactions', compact('transactions'));
    }

    public function validateTransaction(Request $request, $id, $type = 'main')
    {
        $user = $request->user();
        $transaction = ($type == 'main') ? Transaction::findOrFail($id) : TransactionSupplementaire::findOrFail($id);

        $permissionService = \App\Services\AccessControlService::class;

        // Validation par le profil Compliance
        if ($permissionService::can('validate_compliance')) {
            $transaction->is_compliance_validated = 1;
            $transaction->compliance_validated_at = now();
        }
        
        // Validation par le profil Backoffice
        if ($permissionService::can('validate_backoffice')) {
            $transaction->is_backoffice_validated = 1;
            $transaction->backoffice_validated_at = now();
        }

        // Validation finale par Direction Générale
        if ($permissionService::can('validate_dg')) {
            $transaction->is_dg_validated = 1;
            $transaction->dg_validated_at = now();
        }

        $transaction->save();
        $validated = $transaction->checkValidationStatus();

        // Logging l'activité
        $roleName = $user->role->display_name ?? 'Utilisateur';
        \App\Models\UserActivityLog::log(
            "Validation " . $roleName, 
            $transaction, 
            "Validation effectuée par " . $user->name . " pour la transaction " . $transaction->ref
        );

        $msg = $validated ? "Transaction validée et activée avec succès !" : "Validation enregistrée. En attente des autres services.";

        return back()->with('success', $msg);
    }
}
