<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\FinancialMovement;
use App\Models\StatementVersion;
use App\Models\StatementCorrection;
use App\Models\StatementAuditLog;
use App\Services\AdjustmentService;
use App\Services\InvestmentService;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Exception;

class ControlAdjustmentController extends Controller
{
    protected $adjustmentService;
    protected $investmentService;
    protected $productController;

    public function __construct(
        AdjustmentService $adjustmentService,
        InvestmentService $investmentService,
        ProductController $productController
    ) {
        $this->adjustmentService = $adjustmentService;
        $this->investmentService = $investmentService;
        $this->productController = $productController;
    }

    /**
     * Index page: list statements & corrections to control
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', StatementCorrection::class);

        $products = Product::all();
        $clients = User::where('role_id', 2)->orderBy('name')->get();

        $query = StatementCorrection::with(['user', 'product', 'operator', 'controller']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('client_id')) {
            $query->where('user_id', $request->client_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $corrections = $query->orderBy('created_at', 'desc')->paginate(15);

        $pendingCount = StatementCorrection::where('status', 'A_controler')->count();
        $validatedCount = StatementCorrection::where('status', 'Valide')->count();
        $rejectedCount = StatementCorrection::where('status', 'Rejete')->count();

        // Recent statement versions
        $versions = StatementVersion::with(['user', 'product'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('control_adjustments.index', compact(
            'corrections',
            'products',
            'clients',
            'pendingCount',
            'validatedCount',
            'rejectedCount',
            'versions'
        ));
    }

    /**
     * Detailed control card for a client statement
     */
    public function show($clientId)
    {
        $this->authorize('viewAny', StatementCorrection::class);

        $client = User::where('role_id', 2)->findOrFail($clientId);
        $transactions = Transaction::where('user_id', $clientId)
            ->with(['product', 'sousTransactions'])
            ->orderBy('created_at', 'desc')
            ->get();

        $currentDate = Carbon::now();

        // Calculate current valuations
        $statementData = [];
        foreach ($transactions as $trans) {
            if ($trans->product->products_category_id === 2) {
                // PMG
                $val = (float)$this->productController->calculatePMGValorization($trans, $currentDate);
                $whitelisted = AdjustmentService::getWhitelistedFields(2);
            } else {
                // FCP
                $val = (float)($trans->nb_part * ($trans->product->latest_vl ?? $trans->vl_buy));
                $whitelisted = AdjustmentService::getWhitelistedFields(1);
            }

            $movements = FinancialMovement::where('transaction_id', $trans->id)
                ->orderBy('date_operation', 'desc')
                ->get();

            $statementData[] = [
                'transaction' => $trans,
                'valuation' => $val,
                'movements' => $movements,
                'whitelisted_fields' => $whitelisted,
            ];
        }

        // Correction history for this client
        $history = StatementCorrection::where('user_id', $clientId)
            ->with(['operator', 'controller'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Audit logs for this client
        $auditLogs = StatementAuditLog::where('user_id', $clientId)
            ->with(['operator', 'controller'])
            ->orderBy('action_at', 'desc')
            ->get();

        // Versions
        $versions = StatementVersion::where('user_id', $clientId)
            ->orderBy('version_number', 'desc')
            ->get();

        return view('control_adjustments.show', compact(
            'client',
            'statementData',
            'history',
            'auditLogs',
            'versions'
        ));
    }

    /**
     * AJAX endpoint for live delta simulation
     */
    public function simulate(Request $request)
    {
        $this->authorize('create', StatementCorrection::class);

        $request->validate([
            'target_entity' => 'required|string',
            'target_id' => 'required|integer',
            'field_name' => 'required|string',
            'new_value' => 'required',
        ]);

        try {
            $simulation = $this->adjustmentService->simulateCorrection($request->all());
            return response()->json([
                'success' => true,
                'data' => $simulation
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Submit a correction request (Operator)
     */
    public function storeCorrection(Request $request)
    {
        $this->authorize('create', StatementCorrection::class);

        $request->validate([
            'client_id' => 'required|integer',
            'target_entity' => 'required|string',
            'target_id' => 'required|integer',
            'field_name' => 'required|string',
            'new_value' => 'required',
            'reason' => 'required|string|min:5',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,png,jpg,jpeg,doc,docx|max:5120',
        ]);

        try {
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('adjustments/proofs', 'private');
            }

            $params = $request->all();
            $params['attachment_path'] = $attachmentPath;

            $correction = $this->adjustmentService->requestCorrection($params);

            // Email Notification on pending correction
            try {
                $controllers = User::whereIn('role_id', [1, 5, 6, 8])->pluck('email')->filter()->toArray();
                if (!empty($controllers)) {
                    \Illuminate\Support\Facades\Mail::to($controllers)->send(new \App\Mail\CorrectionPendingMail($correction));
                }
            } catch (\Throwable $t) {
                \Illuminate\Support\Facades\Log::warning('Notification email failed: ' . $t->getMessage());
            }

            return redirect()->back()->with('success', 'La demande de correction a été enregistrée avec succès et les contrôleurs ont été notifiés.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    /**
     * Download secure private proof file
     */
    public function downloadProof($id)
    {
        $correction = StatementCorrection::findOrFail($id);
        $this->authorize('viewAny', StatementCorrection::class);

        if (!$correction->attachment_path || !Storage::disk('private')->exists($correction->attachment_path)) {
            abort(404, "Pièce justificative introuvable sur le stockage privé.");
        }

        return Storage::disk('private')->download($correction->attachment_path);
    }

    /**
     * Approve/Validate correction (Controller - strictly distinct user)
     */
    public function validateCorrection(Request $request, $id)
    {
        try {
            $correction = StatementCorrection::findOrFail($id);
            $this->authorize('validateOrReject', $correction);

            $controllerId = Auth::id();
            $this->adjustmentService->validateCorrection($id, $controllerId);

            return redirect()->back()->with('success', 'La correction a été validée avec succès et le recalcul a été exécuté.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Échec de la validation : ' . $e->getMessage());
        }
    }

    /**
     * Reject correction (Controller - strictly distinct user)
     */
    public function rejectCorrection(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:5',
        ]);

        try {
            $correction = StatementCorrection::findOrFail($id);
            $this->authorize('validateOrReject', $correction);

            $controllerId = Auth::id();
            $this->adjustmentService->rejectCorrection($id, $controllerId, $request->rejection_reason);

            return redirect()->back()->with('info', 'La demande de correction a été rejetée.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Échec du rejet : ' . $e->getMessage());
        }
    }

    /**
     * Global Append-Only Audit History View
     */
    public function auditHistory(Request $request)
    {
        $this->authorize('viewAny', StatementCorrection::class);

        $query = StatementAuditLog::with(['user', 'operator', 'controller']);

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }
        if ($request->filled('client_id')) {
            $query->where('user_id', $request->client_id);
        }

        $logs = $query->orderBy('action_at', 'desc')->paginate(25);
        $clients = User::where('role_id', 2)->orderBy('name')->get();

        return view('control_adjustments.history', compact('logs', 'clients'));
    }
}
