<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Transaction;
use App\Models\FinancialMovement;
use Illuminate\Support\Str;

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
        ]);

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
            ->select('financial_movements.*', 'products.title as product_title', 'transactions.ref as transaction_ref')
            ->orderBy('financial_movements.date_operation', 'desc')
            ->get();

        $transactionsUsers = DB::table('transactions')
            ->where('user_id', $customerId)
            ->where('date_echeance', '>=', $currentDate->format('Y-m-d'))

            ->where('status', 'Succès')
            ->get();

        $customer = \App\Models\User::findOrFail($customerId);

        // Récupérer les produits FCP que le client possède réellement
        $ownedFcpIds = DB::table('fcp_movements')
            ->where('user_id', $customerId)
            ->distinct()
            ->pluck('product_id');

        $ownedFcpProducts = \App\Models\Product::whereIn('id', $ownedFcpIds)->get();

        // Récupérer les mouvements FCP
        $fcpMovements = DB::table('fcp_movements')
            ->join('products', 'fcp_movements.product_id', '=', 'products.id')
            ->where('fcp_movements.user_id', $customerId)
            ->select('fcp_movements.*', 'products.title as product_title')
            ->get();

        $unifiedOperations = collect();

        foreach ($movements as $mvt) {
            $unifiedOperations->push((object)[
                'date_op' => $mvt->date_operation,
                'category' => 'PMG',
                'product_title' => $mvt->product_title,
                'reference' => $mvt->transaction_ref,
                'type' => $mvt->type,
                'amount' => $mvt->amount,
                'parts_change' => null,
                'comment' => $mvt->comments
            ]);
        }

        foreach ($fcpMovements as $fcpMvt) {
            $unifiedOperations->push((object)[
                'date_op' => $fcpMvt->date_operation,
                'category' => 'FCP',
                'product_title' => $fcpMvt->product_title,
                'reference' => $fcpMvt->reference,
                'type' => $fcpMvt->type,
                'amount' => $fcpMvt->amount_xaf,
                'parts_change' => $fcpMvt->nb_parts_change,
                'comment' => $fcpMvt->comment
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

        // 1. Calcul de la valorisation AVANT l'opération
        $capitalBefore = $this->calculatePMGValorization($transaction, $request->date_operation);

        // 2. Logique selon le type d'opération
        if ($type === 'precompte_interets') {
            // Le capital reste le même, on ne fait que sortir les intérêts
            $capitalAfter = $capitalBefore;
        } elseif ($type === 'rachat_partiel') {
            // On diminue le capital du montant racheté
            $capitalAfter = $capitalBefore - $amount;
        } else {
            // Rajout : On augmente le capital
            $capitalAfter = $capitalBefore + $amount;
        }

        // 3. Insertion SQL
        DB::table('financial_movements')->insert([
            'transaction_id' => $transaction->id,
            'date_operation' => $request->date_operation . ' ' . date('H:i:s'),
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
        ]);

        DB::beginTransaction();
        try {
            $transaction = Transaction::findOrFail($request->transaction_id);

            if ($request->amount_brut > $transaction->amount) {
                return response()->json(['message' => 'Le montant dépasse le capital.'], 422);
            }

            $capitalAvant = $transaction->amount;
            $capitalApres = $capitalAvant - $request->amount_brut;

            $transaction->update(['amount' => $capitalApres]);

            FinancialMovement::create([
                'transaction_id' => $transaction->id,
                'type' => 'rachat_partiel',
                'amount' => $request->amount_brut,
                'capital_before' => $capitalAvant,
                'capital_after' => $capitalApres,
                'date_operation' => now(),
                'comments'    => 'Rachat partiel de ' . number_format($request->amount_brut) . ' XAF',
            ]);
            DB::commit();
            FinancialMovement::create([
                'transaction_id' => $transaction->id,
                'type' => 'frais_gestion',
                'amount' => $request->amount_frais,
                'capital_before' => $capitalAvant,
                'capital_after' => $capitalApres,
                'date_operation' => now(),
                'comments'    => 'Frais de gestion de ' . number_format($request->amount_frais) . ' XAF',
            ]);

            DB::commit();

            \App\Models\UserActivityLog::log(
                "RACHAT_PARTIEL_PMG",
                $transaction,
                "Rachat partiel de " . number_format($request->amount_brut) . " XAF validé."
            );

            // ✅ Retourner du JSON pour AJAX
            return response()->json([
                'success' => 'Rachat de ' . number_format($request->amount_brut) . ' XAF validé.'
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
        ]);

        $trans = Transaction::findOrFail($request->transaction_id);
        $amount = (float)$request->interest_amount;

        // Récupérer le dernier état du capital
        $lastMove = DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->orderBy('date_operation', 'desc')
            ->first();

        $capitalBefore = $lastMove ? $lastMove->capital_after : $trans->amount;

        // CAS 1 : Précompte (L'argent est "sorti" ou déduit du nominal)
        if ($request->type === 'precompte_interets') {
            DB::table('financial_movements')->insert([
                'transaction_id' => $trans->id,
                'date_operation' => now(),
                'type'           => 'precompte_interets',
                'amount'         => $amount,
                'capital_before' => $capitalBefore,
                'capital_after'  => $capitalBefore, // Le capital nominal ne change pas
                'comments'       => "Intérêts précomptés versés au client : " . number_format($amount, 0) . " XAF",
                'created_at'     => now(),
                'updated_at'     => now()
            ]);
        }

        // CAS 2 : Paiement d'intérêts (Versement ponctuel sans capitalisation)
        if ($request->type === 'paiement_interets') {
            DB::table('financial_movements')->insert([
                'transaction_id' => $trans->id,
                'date_operation' => now(),
                'type'           => 'paiement_interets',
                'amount'         => $amount,
                'capital_before' => $capitalBefore,
                'capital_after'  => $capitalBefore, // On ne touche pas au capital après paiement
                'comments'       => "Versement d'intérêts : " . number_format($amount, 0) . " XAF",
                'created_at'     => now(),
                'updated_at'     => now()
            ]);

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
        ]);

        $trans = Transaction::findOrFail($request->transaction_id);
        $amountToRefund = (float)$request->amount;
        $currentDate = Carbon::now();

        // 1. Calcul des intérêts gagnés à ce jour
        $valoTotale = $this->calculatePMGValorizationForRefund($trans, $currentDate);
        $capitalActuel = (float)DB::table('financial_movements')
            ->where('transaction_id', $trans->id)
            ->orderBy('date_operation', 'desc')
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
            'date_operation' => now(),
            'type'           => 'paiement_interets', // On utilise le type existant pour la compatibilité
            'amount'         => $amountToRefund,
            'capital_before' => $capitalActuel,
            'capital_after'  => $capitalActuel, // Le capital de base ne change pas, on ne retire que les intérêts
            'comments'       => "Remboursement d'intérêts versés au client : " . number_format($amountToRefund, 0) . " XAF",
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
            ->where('type', 'paiement_interets')
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
        $amountBrut = (float)$request->amount_brut;
        $amountFrais = (float)($request->amount_frais ?? 0);

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

        $vl = (float)$vlEntry->vl;
        $nbPartsARetirer = $amountBrut / $vl;

        // 2. Vérifier si le client a assez de parts à cette date
        $partsActuelles = \DB::table('fcp_movements')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('date_operation', '<=', $dateOp)
            ->sum('nb_parts_change');

        if ($nbPartsARetirer > $partsActuelles) {
            return response()->json([
                'message' => "Parts insuffisantes. Le client possède " . round($partsActuelles, 4) . " parts à cette date, or l'opération demande d'en retirer " . round($nbPartsARetirer, 4) . "."
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
                'nb_parts_change' => -$nbPartsARetirer,
                'nb_parts_total' => $oldNbPartsTotal - $nbPartsARetirer,
                'vl_applied' => $vl,
                'comment' => "Rachat FCP de $amountBrut XAF (Net Client: " . ($amountBrut - $amountFrais) . " XAF). Frais: $amountFrais XAF.",
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
                'message' => "Rachat FCP validé avec succès. VL appliquée: $vl XAF (" . round($nbPartsARetirer, 4) . " parts retirées)."
            ]);

        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json(['message' => "Erreur technique : " . $e->getMessage()], 500);
        }
    }
}
