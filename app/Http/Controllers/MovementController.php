<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Transaction;
use App\Models\FinancialMovement;
use App\Support\FinancialDecimal;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MovementController extends Controller
{

    public function storePrecompte(Request $request)
    {
        $transaction = Transaction::findOrFail($request->transaction_id);
        $amountToPay = $request->amount; // Montant des intérêts à précompter

        // On récupère le dernier capital après mouvement ou le montant initial
        $lastCapital = DB::table('financial_movements')
            ->where('transaction_id', $transaction->id)
            ->orderBy('date_operation', 'desc')
            ->orderBy('id', 'desc')
            ->value('capital_after') ?? (float)$transaction->amount;

        // Insertion du mouvement de précompte
        DB::table('financial_movements')->insert([
            'transaction_id' => $transaction->id,
            'date_operation' => $request->date_operation ?? now(),
            'type'           => 'precompte_interets', // Valeur ENUM exacte
            'amount'         => $amountToPay,
            'capital_before' => $lastCapital,
            'capital_after'  => $lastCapital, // ✅ Le capital ne change pas (argent versé au client)
            'comments'       => $request->comments ?? 'Paiement d’intérêts précomptés',
            'created_at'     => now(),
            'updated_at'     => now()
        ] + $paymentMeta);

        return redirect()->back()->with([
            'message'    => "Intérêts précomptés de XAF " . number_format($amountToPay, 0, ' ', ' ') . " enregistrés.",
            'alert-type' => 'success'
        ]);
    }


    public function indexFinancialMovement($customerId)
    {
        $currentDate = Carbon::now();
        $movements  = DB::table('financial_movements')
            ->join('transactions', 'financial_movements.transaction_id', '=', 'transactions.id')
            ->join('products', 'transactions.product_id', '=', 'products.id')
            ->where('transactions.user_id', $customerId)
            ->select('financial_movements.*', 'products.title as product_title', 'transactions.ref as transaction_ref', 'transactions.vl_buy as transaction_vl')
            ->orderBy('financial_movements.date_operation', 'desc')
            ->get();

        $transactionsUsers = DB::table('transactions')
            ->join('products', 'transactions.product_id', '=', 'products.id')
            ->where('transactions.user_id', $customerId)

            ->where('transactions.status', 'Succès')
            ->where('products.products_category_id', 2)
            ->select('transactions.*')
            ->get();

        $customer = \App\Models\User::findOrFail($customerId);

        // Récupérer les produits FCP que le client possède réellement
        $ownedFcpIds = DB::table('fcp_movements')
            ->where('user_id', $customerId)
            ->distinct()
            ->pluck('product_id');

        $ownedFcpProducts = \App\Models\Product::whereIn('id', $ownedFcpIds)->get();

        // Récupérer les mouvements FCP triés par ordre chronologique pour calculer le solde courant
        $fcpMovements = DB::table('fcp_movements')
            ->join('products', 'fcp_movements.product_id', '=', 'products.id')
            ->where('fcp_movements.user_id', $customerId)
            ->select('fcp_movements.*', 'products.title as product_title')
            ->orderBy('fcp_movements.date_operation', 'asc')
            ->orderBy('fcp_movements.id', 'asc')
            ->get();

        // Calculer les soldes FCP par produit au fil de l'eau
        $runningParts = [];
        foreach ($fcpMovements as $fcpMvt) {
            $pid = $fcpMvt->product_id;
            if (!isset($runningParts[$pid])) {
                $runningParts[$pid] = 0.0;
            }

            $partsBefore = $runningParts[$pid];
            $runningParts[$pid] += (float)$fcpMvt->nb_parts_change;
            $partsAfter = $runningParts[$pid];

            $fcpMvt->parts_before = $partsBefore;
            $fcpMvt->parts_after = $partsAfter;
            $fcpMvt->balance_before = $partsBefore * (float)$fcpMvt->vl_applied;
            $fcpMvt->balance_after = $partsAfter * (float)$fcpMvt->vl_applied;
        }

        $unifiedOperations = collect();

        foreach ($movements as $mvt) {
            $unifiedOperations->push((object)[
                'id'             => $mvt->id,
                'transaction_id' => $mvt->transaction_id,
                'date_op'        => $mvt->date_operation,
                'category'       => 'PMG',
                'product_title'  => $mvt->product_title,
                'reference'      => $mvt->transaction_ref,
                'type'           => $mvt->type,
                'amount'         => $mvt->amount,
                'parts_change'   => null,
                'comment'        => $mvt->comments,
                'balance_before' => $mvt->capital_before,
                'balance_after'  => $mvt->capital_after,
                'parts_before'   => null,
                'parts_after'    => null,
                'vl_applied'     => $mvt->transaction_vl ?? 0,
                'payment_method' => $mvt->payment_method ?? null,
                'payment_reference' => $mvt->payment_reference ?? null,
                'payment_proof_path' => $mvt->payment_proof_path ?? null,
            ]);
        }

        foreach ($fcpMovements as $fcpMvt) {
            $unifiedOperations->push((object)[
                'id'             => $fcpMvt->id,
                'transaction_id' => $fcpMvt->transaction_id,
                'date_op'        => $fcpMvt->date_operation,
                'category'       => 'FCP',
                'product_title'  => $fcpMvt->product_title,
                'reference'      => $fcpMvt->reference,
                'type'           => $fcpMvt->type,
                'amount'         => $fcpMvt->amount_xaf,
                'parts_change'   => $fcpMvt->nb_parts_change,
                'comment'        => $fcpMvt->comment,
                'balance_before' => $fcpMvt->balance_before,
                'balance_after'  => $fcpMvt->balance_after,
                'parts_before'   => $fcpMvt->parts_before,
                'parts_after'    => $fcpMvt->parts_after,
                'vl_applied'     => $fcpMvt->vl_applied,
                'payment_method' => null,
                'payment_reference' => null,
                'payment_proof_path' => null,
            ]);
        }

        $allOperations = $unifiedOperations->sortByDesc('date_op')->values();

        return view('front-end.customer-transactions-management', compact('movements', 'customerId', 'transactionsUsers', 'customer', 'ownedFcpProducts', 'fcpMovements', 'allOperations'));
    }

    public function storeFinancialMovement(Request $request)
    {
        $transaction = Transaction::findOrFail($request->transaction_id);
        $type = $request->type;
        $amount = (float)$request->amount;
        $dateOperation = $request->date_operation . ' ' . date('H:i:s');
        $paymentMeta = [];

        if (in_array($type, ['precompte_interets', 'paiement_interets', 'paiement_capital'])) {
            $request->validate([
                'payment_date' => 'required|date',
                'payment_method' => 'required|in:virement,cheque,especes,mobile_money,autre',
                'payment_reference' => 'required|string|max:120',
                'payment_proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);

            $dateOperation = Carbon::parse($request->payment_date)->toDateTimeString();
            $paymentMeta = [
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'payment_proof_path' => $request->hasFile('payment_proof')
                    ? $request->file('payment_proof')->store('payment-proofs', 'public')
                    : null,
                'payment_recorded_by' => Auth::id(),
            ];
        }

        // 1. Calcul de la valorisation AVANT l'opération
        $capitalBefore = $this->calculatePMGValorization($transaction, $request->date_operation);

        // 2. Logique selon le type d'opération
        if (in_array($type, ['precompte_interets', 'paiement_interets', 'liquidite_interets', 'paiement_capital'])) {
            // Le capital reste le même, on ne fait que sortir les intérêts
            $capitalAfter = $capitalBefore;
        } elseif ($type === 'rachat_partiel') {
            // On diminue le capital du montant racheté
            $capitalAfter = $capitalBefore - $amount;
        } elseif ($type === 'liquidite_capital' || $type === 'rachat_total') {
            // A l'echeance, le capital quitte le PMG actif pour devenir une liquidite disponible.
            $capitalAfter = 0;
        } else {
            // Rajout : On augmente le capital
            $capitalAfter = $capitalBefore + $amount;
        }

        // 3. Insertion SQL
        DB::table('financial_movements')->insert([
            'transaction_id' => $transaction->id,
            'date_operation' => $dateOperation,
            'type'           => $type,
            'amount'         => $amount,
            'capital_before' => $capitalBefore,
            'capital_after'  => $capitalAfter,
            'comments'       => $request->comments ?? "Opération de $type enregistrée via interface Admin",
            'created_at'     => now(),
            'updated_at'     => now()
        ]);

        \App\Models\UserActivityLog::log(
            "AJOUT_MOUVEMENT_PMG",
            $transaction,
            "Enregistrement d'un mouvement PMG de type $type pour un montant de $amount XAF"
        );

        return response()->json(['message' => 'Mouvement enregistré avec succès !']);
    }


    public function rachatPartiel(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'amount_brut' => 'required|numeric|min:1',
            'amount_frais' => 'nullable|numeric|min:0',
        ]);

        $transaction = Transaction::findOrFail($request->transaction_id);
        $amountBrut = (float)$request->amount_brut;
        $amountFrais = (float)($request->amount_frais ?? 0);
        $totalRequested = $amountBrut + $amountFrais;

        $productController = app(\App\Http\Controllers\ProductController::class);
        $dateOp = now();
        $valuationBefore = $productController->calculatePMGValorization($transaction, $dateOp);

        if ($totalRequested > $valuationBefore) {
            return response()->json([
                'message' => 'Le montant saisi (avec les frais) dépasse la valorisation actuelle (' . number_format($valuationBefore, 0, ',', ' ') . ' XAF).'
            ], 422);
        }

        $capitalApres = max(0, $valuationBefore - $totalRequested);

        DB::beginTransaction();
        try {
            // 1. Mettre à jour le montant nominal de la transaction
            $transaction->update(['amount' => $capitalApres]);

            // 2. Déterminer le type (rachat_total si solde nul, rachat_partiel sinon)
            $movementType = ($capitalApres <= 0) ? 'rachat_total' : 'rachat_partiel';

            // 3. Créer le mouvement de rachat
            FinancialMovement::create([
                'transaction_id' => $transaction->id,
                'type'           => $movementType,
                'amount'         => $amountBrut,
                'capital_before' => $valuationBefore,
                'capital_after'  => $valuationBefore - $amountBrut,
                'date_operation' => $dateOp,
                'comments'       => 'Rachat de ' . number_format($amountBrut) . ' XAF',
            ]);

            // 4. Créer le mouvement de frais si applicable
            if ($amountFrais > 0) {
                FinancialMovement::create([
                    'transaction_id' => $transaction->id,
                    'type'           => 'frais_gestion',
                    'amount'         => $amountFrais,
                    'capital_before' => $valuationBefore - $amountBrut,
                    'capital_after'  => $capitalApres,
                    'date_operation' => $dateOp,
                    'comments'       => 'Frais de gestion de ' . number_format($amountFrais) . ' XAF',
                ]);
            }

            DB::commit();

            \App\Models\UserActivityLog::log(
                "RACHAT_PMG",
                $transaction,
                "Rachat PMG de " . number_format($amountBrut) . " XAF (Frais: " . number_format($amountFrais) . " XAF) validé."
            );

            return response()->json([
                'success' => 'Rachat de ' . number_format($amountBrut) . ' XAF validé.',
                'message' => 'Rachat de ' . number_format($amountBrut) . ' XAF validé avec succès.'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }



    /**
     * Gère le versement des intérêts précomptés
     */
    public function verserPrecompte(Request $request)
    {

        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'type' => 'required|in:precompte_interets,paiement_interets',
            'interest_amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:virement,cheque,especes,mobile_money,autre',
            'payment_reference' => 'required|string|max:120',
            'payment_proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $trans = Transaction::findOrFail($request->transaction_id);
        $amount = (float)$request->interest_amount;
        $paymentDate = Carbon::parse($request->payment_date);
        $proofPath = $request->hasFile('payment_proof')
            ? $request->file('payment_proof')->store('payment-proofs', 'public')
            : null;
        $paymentMeta = [
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
            'payment_proof_path' => $proofPath,
            'payment_recorded_by' => Auth::id(),
        ];

        // Récupérer le dernier état du capital
        $lastMove = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->orderBy('date_operation', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $capitalBefore = $lastMove ? $lastMove->capital_after : $trans->amount;

        // CAS 1 : Précompte (L'argent est "sorti" ou déduit du nominal)
        if ($request->type === 'precompte_interets') {
            DB::table('financial_movements')->insert([
                'transaction_id' => $trans->id,
                'date_operation' => $paymentDate->toDateTimeString(),
                'type'           => 'precompte_interets',
                'amount'         => $amount,
                'capital_before' => $capitalBefore,
                'capital_after'  => $capitalBefore, // Le capital nominal ne change pas
                'comments'       => "Intérêts précomptés versés au client : " . number_format($amount, 0) . " XAF",
                'created_at'     => now(),
                'updated_at'     => now()
            ] + $paymentMeta);
        }

        // CAS 2 : Paiement d'intérêts (Versement ponctuel sans capitalisation)
        if ($request->type === 'paiement_interets') {
            DB::table('financial_movements')->insert([
                'transaction_id' => $trans->id,
                'date_operation' => $paymentDate->toDateTimeString(),
                'type'           => 'paiement_interets',
                'amount'         => $amount,
                'capital_before' => $capitalBefore,
                'capital_after'  => $capitalBefore, // On ne touche pas au capital après paiement
                'comments'       => "Versement d'intérêts : " . number_format($amount, 0) . " XAF",
                'created_at'     => now(),
                'updated_at'     => now()
            ] + $paymentMeta);

            \App\Models\UserActivityLog::log(
                "VERSEMENT_INTERET",
                $trans,
                "Versement d'intérêts de type payment_interets pour un montant de $amount XAF"
            );
        }

        return back()->with('success', 'L\'opération sur les intérêts a été enregistrée.');
    }

    /**
     * Rembourse les intérêts gagnés par le client
     */
    public function rembourserInterets(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:virement,cheque,especes,mobile_money,autre',
            'payment_reference' => 'required|string|max:120',
            'payment_proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $trans = Transaction::findOrFail($request->transaction_id);
        $amountToRefund = (float)$request->amount;
        $currentDate = Carbon::now();
        $paymentDate = Carbon::parse($request->payment_date);
        $proofPath = $request->hasFile('payment_proof')
            ? $request->file('payment_proof')->store('payment-proofs', 'public')
            : null;
        $paymentMeta = [
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
            'payment_proof_path' => $proofPath,
            'payment_recorded_by' => Auth::id(),
        ];

        // 1. Calcul des intérêts gagnés à ce jour
        $valoTotale = $this->calculatePMGValorizationForRefund($trans, $currentDate);
        $capitalActuel = (float)DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->orderBy('date_operation', 'desc')
            ->orderBy('id', 'desc')
            ->value('capital_after') ?? (float)$trans->amount;

        $interetsDisponibles = max(0, $valoTotale - $capitalActuel);

        // 2. Vérification du solde d'intérêts
        if ($amountToRefund > $interetsDisponibles) {
            return response()->json([
                'message' => "Montant insuffisant. Intérêts disponibles : " . number_format($interetsDisponibles, 0, ',', ' ') . " XAF"
            ], 422);
        }

        // 3. Enregistrement du mouvement de remboursement
        DB::table('financial_movements')->insert([
            'transaction_id' => $trans->id,
            'date_operation' => $paymentDate->toDateTimeString(),
            'type'           => 'paiement_interets', // On utilise le type existant pour la compatibilité
            'amount'         => $amountToRefund,
            'capital_before' => $capitalActuel,
            'capital_after'  => $capitalActuel, // Le capital de base ne change pas, on ne retire que les intérêts
            'comments'       => "Remboursement d'intérêts versés au client : " . number_format($amountToRefund, 0) . " XAF",
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
            'payment_proof_path' => $proofPath,
            'payment_recorded_by' => Auth::id(),
            'created_at'     => now(),
            'updated_at'     => now()
        ]);

        \App\Models\UserActivityLog::log(
            "REMBOURSEMENT_INTERETS",
            $trans,
            "Remboursement d'intérêts de " . number_format($amountToRefund, 0) . " XAF validé."
        );

        return response()->json([
            'message' => "Remboursement de " . number_format($amountToRefund, 0, ',', ' ') . " XAF effectué avec succès."
        ]);
    }

    public function payerLiquiditePmg(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'scope' => 'required|in:interets,capital,total',
            'amount' => 'nullable|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:virement,cheque,especes,mobile_money,autre',
            'payment_reference' => 'required|string|max:120',
            'payment_proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $trans = Transaction::findOrFail($request->transaction_id);
        $available = $this->getPmgLiquidityAvailable($trans->id);
        $requestedAmount = $request->filled('amount') ? (float)$request->amount : null;
        $paymentDate = Carbon::parse($request->payment_date);
        $proofPath = $request->hasFile('payment_proof')
            ? $request->file('payment_proof')->store('payment-proofs', 'public')
            : null;
        $paymentMeta = [
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
            'payment_proof_path' => $proofPath,
            'payment_recorded_by' => Auth::id(),
        ];

        if ($request->scope === 'interets') {
            $amountInterest = $requestedAmount ?? $available['interets'];
            $amountCapital = 0;
            $limit = $available['interets'];
        } elseif ($request->scope === 'capital') {
            $amountInterest = 0;
            $amountCapital = $requestedAmount ?? $available['capital'];
            $limit = $available['capital'];
        } else {
            $amountToPay = $requestedAmount ?? ($available['interets'] + $available['capital']);
            $amountInterest = min($available['interets'], $amountToPay);
            $amountCapital = max(0, $amountToPay - $amountInterest);
            $limit = $available['interets'] + $available['capital'];
        }

        $totalPayment = $amountInterest + $amountCapital;
        if ($totalPayment <= 0 || $totalPayment > $limit) {
            return response()->json([
                'message' => 'Liquidite insuffisante. Disponible : ' . number_format($limit, 0, ',', ' ') . ' XAF.'
            ], 422);
        }

        $lastCapital = $this->getLastPmgCapitalAfter($trans);

        DB::beginTransaction();
        try {
            if ($amountInterest > 0) {
                DB::table('financial_movements')->insert([
                    'transaction_id' => $trans->id,
                    'date_operation' => $paymentDate->toDateTimeString(),
                    'type'           => 'paiement_interets',
                    'amount'         => round($amountInterest, 0),
                    'capital_before' => $lastCapital,
                    'capital_after'  => $lastCapital,
                    'comments'       => 'Paiement depuis liquidite - interets : ' . number_format($amountInterest, 0) . ' XAF | Ref: ' . $request->payment_reference,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ] + $paymentMeta);
            }

            if ($amountCapital > 0) {
                DB::table('financial_movements')->insert([
                    'transaction_id' => $trans->id,
                    'date_operation' => $paymentDate->copy()->addSecond()->toDateTimeString(),
                    'type'           => 'paiement_capital',
                    'amount'         => round($amountCapital, 0),
                    'capital_before' => $lastCapital,
                    'capital_after'  => $lastCapital,
                    'comments'       => 'Paiement depuis liquidite - capital : ' . number_format($amountCapital, 0) . ' XAF | Ref: ' . $request->payment_reference,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ] + $paymentMeta);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            if ($proofPath) {
                Storage::disk('public')->delete($proofPath);
            }
            return response()->json(['message' => $e->getMessage()], 500);
        }

        \App\Models\UserActivityLog::log(
            "PAIEMENT_LIQUIDITE_PMG",
            $trans,
            "Paiement de liquidite PMG : interets " . number_format($amountInterest, 0) . " XAF, capital " . number_format($amountCapital, 0) . " XAF"
        );

        return response()->json([
            'message' => 'Paiement de liquidite enregistre : interets '
                . number_format($amountInterest, 0, ',', ' ')
                . ' XAF, capital '
                . number_format($amountCapital, 0, ',', ' ')
                . ' XAF.'
        ]);
    }

    private function getPmgLiquidityAvailable($transactionId): array
    {
        $rows = DB::table('financial_movements')
            ->where('transaction_id', $transactionId)
            ->whereIn('type', ['liquidite_interets', 'liquidite_capital', 'paiement_interets', 'paiement_capital'])
            ->where(function ($q) {
                $q->where('type', '!=', 'paiement_interets')
                    ->orWhere('comments', 'LIKE', 'Paiement depuis liquidite%')
                    ->orWhere('comments', 'LIKE', 'Paiement depuis liquidité%');
            })
            ->select('type', DB::raw('SUM(amount) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        return [
            'interets' => round(max(0, (float)($rows['liquidite_interets'] ?? 0) - (float)($rows['paiement_interets'] ?? 0)), 0),
            'capital' => round(max(0, (float)($rows['liquidite_capital'] ?? 0) - (float)($rows['paiement_capital'] ?? 0)), 0),
        ];
    }

    private function getLastPmgCapitalAfter(Transaction $transaction): float
    {
        return (float)(DB::table('financial_movements')
            ->where('transaction_id', $transaction->id)
            ->orderBy('date_operation', 'desc')
            ->orderBy('id', 'desc')
            ->value('capital_after') ?? $transaction->amount);
    }

    /**
     * Version locale simplifiée pour le contrôleur de mouvements
     */
    private function calculatePMGValorizationForRefund($trans, $refDate)
    {
        $targetDate = Carbon::parse($refDate)->min(Carbon::parse($trans->date_echeance));
        $rate = (float)$trans->vl_buy / 100;

        $lastMovement = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->whereIn('type', ['capitalisation_interets', 'rachat_partiel'])
            ->where('date_operation', '<=', $targetDate->toDateString())
            ->orderBy('date_operation', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $baseCapital = $lastMovement ? (float)$lastMovement->capital_after : (float)$trans->amount;
        $startDate = $lastMovement ? Carbon::parse($lastMovement->date_operation) : Carbon::parse($trans->date_validation);

        $totalInterest = 0;
        if ($targetDate->gt($startDate)) {
            $nextMonth = $startDate->copy()->addMonthNoOverflow()->startOfMonth();

            if ($targetDate->lt($nextMonth)) {
                $totalInterest = ($baseCapital * $rate * $startDate->diffInDays($targetDate)) / 360;
            } else {
                $totalInterest = ($baseCapital * $rate * $startDate->diffInDays($startDate->copy()->endOfMonth())) / 360;
                $fullMonths = $nextMonth->diffInMonths($targetDate->copy()->addDay());
                $totalInterest += ($baseCapital * ($rate / 12)) * $fullMonths;
                $lastMonthStart = $nextMonth->copy()->addMonths($fullMonths);
                if ($lastMonthStart->lt($targetDate)) {
                    $totalInterest += ($baseCapital * $rate * $lastMonthStart->diffInDays($targetDate)) / 360;
                }
            }
        }

        $precompte = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->where('type', 'precompte_interets')
            ->sum('amount') ?? 0;

        $paiementsAnterieurs = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->whereIn('type', ['paiement_interets', 'liquidite_interets'])
            ->where(function ($q) {
                $q->where('type', '!=', 'paiement_interets')
                    ->orWhereNull('comments')
                    ->orWhere(function ($sub) {
                        $sub->where('comments', 'NOT LIKE', 'Paiement depuis liquidite%')
                            ->where('comments', 'NOT LIKE', 'Paiement depuis liquidité%');
                    });
            })
            ->sum('amount') ?? 0;

        return round(($baseCapital - $precompte - $paiementsAnterieurs) + $totalInterest, 0);
    }

    /**
     * Gère les rachats sur les produits FCP
     */
    public function rachatFcp(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'customer_id' => 'required|exists:users,id',
            'date_operation' => 'required|date',
            'amount_brut' => 'required|numeric|min:1',
            'amount_frais' => 'nullable|numeric|min:0',
        ]);

        $productId = $request->product_id;
        $userId = $request->customer_id;
        $dateOp = $request->date_operation;
        $amountBrut = FinancialDecimal::money($request->amount_brut);
        $amountFrais = FinancialDecimal::money($request->amount_frais ?? 0);

        // 1. Récupérer la VL à la date choisie (ou plus proche précédente)
        // Note: On utilise AssetValue via ProductController::getVlAtDate logic
        $vlEntry = \DB::table('asset_values')
            ->where('product_id', $productId)
            ->where('date_vl', '<=', $dateOp)
            ->orderBy('date_vl', 'desc')
            ->first();

        if (!$vlEntry) {
            return response()->json(['message' => "Aucune Valeur Liquidative trouvée à cette date pour ce produit."], 422);
        }

        $vl = (string) $vlEntry->vl;
        $nbPartsARetirer = FinancialDecimal::partsFromAmount($amountBrut, $vl);

        // 2. Vérifier si le client a assez de parts à cette date
        $partsActuelles = \DB::table('fcp_movements')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('date_operation', '<=', $dateOp)
            ->sum('nb_parts_change');

        if (FinancialDecimal::of($nbPartsARetirer)->isGreaterThan(FinancialDecimal::of($partsActuelles))) {
            return response()->json([
                'message' => "Parts insuffisantes. Le client possède " . round((float)$partsActuelles, 4) . " parts à cette date, or l'opération demande d'en retirer " . round((float)$nbPartsARetirer, 4) . "."
            ], 422);
        }

        \DB::beginTransaction();
        try {
            // 3. Enregistrer le mouvement de rachat
            $oldNbPartsTotal = \DB::table('fcp_movements')
                ->where('user_id', $userId)
                ->where('product_id', $productId)
                ->sum('nb_parts_change');

            $reference = 'RCH-' . date('dmY') . '-' . strtoupper(Str::random(4));
            \DB::table('fcp_movements')->insert([
                'user_id' => $userId,
                'product_id' => $productId,
                'reference' => $reference,
                'date_operation' => $dateOp,
                'type' => 'rachat',
                'amount_xaf' => $amountBrut,
                'fees' => $amountFrais,
                'nb_parts_change' => FinancialDecimal::of($nbPartsARetirer)->negated()->__toString(),
                'nb_parts_total' => FinancialDecimal::subtract($oldNbPartsTotal, $nbPartsARetirer, FinancialDecimal::PARTS_SCALE),
                'vl_applied' => $vl,
                'comment' => "Rachat FCP de $amountBrut XAF (Net Client: " . FinancialDecimal::subtract($amountBrut, $amountFrais) . " XAF). Frais: $amountFrais XAF.",
                'created_at' => now(),
                'updated_at' => now()
            ]);

            \DB::commit();

            \App\Models\UserActivityLog::log(
                "RACHAT_FCP",
                null,
                "Rachat FCP de $amountBrut XAF pour le client ID $userId (VL: $vl)"
            );

            return response()->json([
                'status' => 'ok',
                'message' => "Rachat FCP validé avec succès. VL appliquée: $vl XAF (" . round((float)$nbPartsARetirer, 4) . " parts retirées)."
            ]);

        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json(['message' => "Erreur technique : " . $e->getMessage()], 500);
        }
    }

    public function editMovement(Request $request)
    {
        $validated = $request->validate([
            'op_id' => 'required|integer|min:1',
            'op_category' => 'required|in:PMG,FCP',
            'amount' => 'required|numeric|min:0.01',
            'date_operation' => 'required|date|before_or_equal:today',
            'vl_applied' => 'nullable|numeric|min:0',
            'comments' => 'nullable|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($request, $validated) {
                $id = (int) $validated['op_id'];
                $category = $validated['op_category'];
                $date = Carbon::parse($validated['date_operation']);

                if ($category === 'PMG') {
                    $mvt = DB::table('financial_movements')->where('id', $id)->lockForUpdate()->first();
                    if (!$mvt) {
                        abort(404, 'Mouvement PMG introuvable.');
                    }

                    $transaction = Transaction::where('id', $mvt->transaction_id)->lockForUpdate()->firstOrFail();
                    $isRedemption = in_array($mvt->type, ['rachat_partiel', 'rachat_total'], true);
                    if ($date->lt(Carbon::parse($transaction->date_validation))) {
                        throw new \DomainException('La date du rachat ne peut pas preceder la date de valeur du placement.');
                    }
                    if ($isRedemption && $transaction->date_echeance && $date->gt(Carbon::parse($transaction->date_echeance))) {
                        throw new \DomainException('La date du rachat ne peut pas depasser la date d echeance du placement.');
                    }

                    $dateOp = $date->format('Y-m-d') . ' ' . Carbon::parse($mvt->date_operation)->format('H:i:s');
                    $comment = $request->has('comments') ? ($validated['comments'] ?? null) : $mvt->comments;
                    $oldValues = [
                        'amount' => $mvt->amount,
                        'date_operation' => $mvt->date_operation,
                        'comments' => $mvt->comments,
                    ];

                    DB::table('financial_movements')->where('id', $id)->update([
                        'amount' => FinancialDecimal::money($validated['amount']),
                        'date_operation' => $dateOp,
                        'comments' => $comment,
                        'updated_at' => now(),
                    ]);

                    $this->recalculatePMGMovements($mvt->transaction_id);
                    $updated = DB::table('financial_movements')->where('id', $id)->first();

                    if ($isRedemption) {
                        if ((float) $updated->capital_after < -0.005) {
                            throw new \DomainException('Le montant du rachat depasse la valorisation disponible a cette date.');
                        }

                        $movementType = (float) $updated->capital_after <= 0.005
                            ? 'rachat_total'
                            : 'rachat_partiel';
                        DB::table('financial_movements')->where('id', $id)->update(['type' => $movementType]);
                    }

                    \App\Models\UserActivityLog::log(
                        'MODIFICATION_MOUVEMENT_PMG',
                        $transaction,
                        "Modification du mouvement PMG #{$id} sans suppression de l historique.",
                        [
                            'before' => $oldValues,
                            'after' => [
                                'amount' => FinancialDecimal::money($validated['amount']),
                                'date_operation' => $dateOp,
                                'comments' => $comment,
                            ],
                        ]
                    );

                    return response()->json([
                        'status' => 'success',
                        'message' => $isRedemption
                            ? 'Rachat PMG mis a jour et soldes recalcules.'
                            : 'Operation PMG mise a jour et soldes recalcules.',
                    ]);
                }

                $mvt = DB::table('fcp_movements')->where('id', $id)->lockForUpdate()->first();
                if (!$mvt) {
                    abort(404, 'Mouvement FCP introuvable.');
                }

                $vlEntry = DB::table('asset_values')
                    ->where('product_id', $mvt->product_id)
                    ->where('date_vl', '<=', $date->toDateString())
                    ->orderByDesc('date_vl')
                    ->first();
                if (!$vlEntry || FinancialDecimal::of($vlEntry->vl)->isLessThanOrEqualTo('0')) {
                    throw new \DomainException('Aucune valeur liquidative valide n existe a cette date.');
                }

                $amount = FinancialDecimal::money($validated['amount']);
                $vlApplied = FinancialDecimal::vl($vlEntry->vl);
                $isRachat = str_contains(strtolower($mvt->type), 'rachat') || (float) $mvt->nb_parts_change < 0;
                $nbPartsChange = FinancialDecimal::partsFromAmount($amount, $vlApplied);
                if ($isRachat) {
                    $nbPartsChange = FinancialDecimal::of($nbPartsChange)->abs()->negated()->__toString();
                }

                $dateOp = $date->format('Y-m-d') . ' ' . Carbon::parse($mvt->date_operation)->format('H:i:s');
                $comment = $request->has('comments') ? ($validated['comments'] ?? null) : $mvt->comment;
                $oldValues = [
                    'amount_xaf' => $mvt->amount_xaf,
                    'vl_applied' => $mvt->vl_applied,
                    'nb_parts_change' => $mvt->nb_parts_change,
                    'date_operation' => $mvt->date_operation,
                    'comment' => $mvt->comment,
                ];

                DB::table('fcp_movements')->where('id', $id)->update([
                    'amount_xaf' => $amount,
                    'vl_applied' => $vlApplied,
                    'nb_parts_change' => $nbPartsChange,
                    'date_operation' => $dateOp,
                    'comment' => $comment,
                    'updated_at' => now(),
                ]);

                $this->recalculateFCPMovements($mvt->user_id, $mvt->product_id);
                $minimumParts = DB::table('fcp_movements')
                    ->where('user_id', $mvt->user_id)
                    ->where('product_id', $mvt->product_id)
                    ->min('nb_parts_total');

                if (FinancialDecimal::of($minimumParts)->isLessThan('0')) {
                    throw new \DomainException('Le rachat depasse le nombre de parts disponible a cette date.');
                }

                \App\Models\UserActivityLog::log(
                    'MODIFICATION_MOUVEMENT_FCP',
                    null,
                    "Modification du mouvement FCP #{$id} sans suppression de l historique.",
                    [
                        'movement_id' => $id,
                        'user_id' => $mvt->user_id,
                        'product_id' => $mvt->product_id,
                        'before' => $oldValues,
                        'after' => [
                            'amount_xaf' => $amount,
                            'vl_applied' => $vlApplied,
                            'nb_parts_change' => $nbPartsChange,
                            'date_operation' => $dateOp,
                            'comment' => $comment,
                        ],
                    ]
                );

                return response()->json([
                    'status' => 'success',
                    'message' => 'Rachat FCP mis a jour et parts recalculees.',
                ]);
            });
        } catch (\DomainException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    private function recalculatePMGMovements($transactionId)
    {
        $transaction = Transaction::findOrFail($transactionId);
        
        $movements = DB::table('financial_movements')
            ->where('transaction_id', $transactionId)
            ->orderBy('date_operation', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $firstMvt = $movements->first();
        if ($firstMvt && in_array($firstMvt->type, ['souscription', 'versement_libre'])) {
            $currentCapital = 0;
        } else {
            $currentCapital = $firstMvt ? (float)$firstMvt->capital_before : (float)$transaction->amount;
        }

        $lastCapDate = Carbon::parse($transaction->date_validation)->toDateString();
        $rate = (float)$transaction->vl_buy;
        $payoutsAccumulated = 0;
        
        foreach ($movements as $mvt) {
            $mvtDate = Carbon::parse($mvt->date_operation)->toDateString();
            
            // Calculate interest accrued since last anniversary or rachat date
            $accrued = 0;
            $startDate = Carbon::parse($lastCapDate);
            $targetDate = Carbon::parse($mvtDate);
            $annualRate = $rate / 100;
            
            if ($targetDate->gt($startDate)) {
                $nextMonth = $startDate->copy()->addMonthNoOverflow()->startOfMonth();

                if ($targetDate->lt($nextMonth)) {
                    $accrued = ($currentCapital * $annualRate * ($startDate->diffInDays($targetDate))) / 360;
                } else {
                    $accrued = ($currentCapital * $annualRate * ($startDate->diffInDays($startDate->copy()->endOfMonth()))) / 360;
                    $fullMonths = $nextMonth->diffInMonths($targetDate->copy()->addDay());
                    $accrued += ($currentCapital * ($annualRate / 12)) * $fullMonths;
                    $lastMonthStart = $nextMonth->copy()->addMonths($fullMonths);
                    if ($lastMonthStart->lt($targetDate)) {
                        $accrued += ($currentCapital * $annualRate * ($lastMonthStart->diffInDays($targetDate) + 1)) / 360;
                    }
                }
                $accrued = round($accrued, 0);
            }
            
            $valuationBefore = ($currentCapital - $payoutsAccumulated) + $accrued;
            $capitalBefore = $currentCapital;

            if ($mvt->type === 'souscription') {
                $capitalBefore = 0;
                $capitalAfter = (float)$mvt->amount;
                $currentCapital = $capitalAfter;
                $lastCapDate = $mvtDate;
                $payoutsAccumulated = 0;
            } elseif ($mvt->type === 'versement_libre') {
                $capitalAfter = $currentCapital + (float)$mvt->amount;
                $currentCapital = $capitalAfter;
                $lastCapDate = $mvtDate;
                $payoutsAccumulated = 0;
            } elseif ($mvt->type === 'capitalisation_interets') {
                $capitalAfter = $currentCapital + (float)$mvt->amount;
                $currentCapital = $capitalAfter;
                $lastCapDate = $mvtDate;
                $payoutsAccumulated = 0;
            } elseif (in_array($mvt->type, ['rachat_partiel', 'rachat_total'])) {
                $capitalBefore = $valuationBefore;
                $capitalAfter = $valuationBefore - (float)$mvt->amount;
                $currentCapital = $capitalAfter;
                $lastCapDate = $mvtDate;
                $payoutsAccumulated = 0;
            } elseif ($mvt->type === 'liquidite_capital') {
                $capitalBefore = $currentCapital;
                $capitalAfter = 0;
                $currentCapital = 0;
                $lastCapDate = $mvtDate;
                $payoutsAccumulated = 0;
            } elseif ($mvt->type === 'frais_gestion') {
                $capitalAfter = $currentCapital - (float)$mvt->amount;
                $currentCapital = $capitalAfter;
            } elseif (in_array($mvt->type, ['precompte_interets', 'paiement_interets', 'liquidite_interets'])) {
                $capitalAfter = $currentCapital;
                $isLiquidityPayment = $mvt->type === 'paiement_interets'
                    && str_starts_with((string)$mvt->comments, 'Paiement depuis liquidite');
                if (!$isLiquidityPayment) {
                    $payoutsAccumulated += (float)$mvt->amount;
                }
            } elseif ($mvt->type === 'paiement_capital') {
                $capitalAfter = $currentCapital;
            } else {
                $capitalAfter = $currentCapital;
            }

            DB::table('financial_movements')->where('id', $mvt->id)->update([
                'capital_before' => $capitalBefore,
                'capital_after'  => $capitalAfter,
            ]);
        }

        $transaction->update(['amount' => $currentCapital]);
    }

    private function recalculateFCPMovements($userId, $productId)
    {
        $movements = DB::table('fcp_movements')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->orderBy('date_operation', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningParts = FinancialDecimal::parts(0);
        foreach ($movements as $mvt) {
            $partsBefore = $runningParts;
            $runningParts = FinancialDecimal::add($runningParts, $mvt->nb_parts_change, FinancialDecimal::PARTS_SCALE);
            $partsAfter = $runningParts;

            DB::table('fcp_movements')->where('id', $mvt->id)->update([
                'nb_parts_total' => $partsAfter,
            ]);
        }
    }
}
