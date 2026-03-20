<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Transaction;
use App\Models\FinancialMovement;

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
            'user_id'        => $transaction->user_id,
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
            ->where('transactions.user_id', $customerId)
            ->select('financial_movements.*')
            ->orderBy('financial_movements.date_operation', 'desc')
            ->get();

        $transactionsUsers = DB::table('transactions')
            ->where('user_id', $customerId)
            ->where('date_echeance', '>=', $currentDate->format('Y-m-d'))

            ->where('status', 'Succès')
            ->get();

        $customer = \App\Models\User::findOrFail($customerId);

        return view('front-end.customer-transactions-management', compact('movements', 'customerId', 'transactionsUsers', 'customer'));
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
            'user_id'        => $request->user_id,
            'date_operation' => $request->date_operation . ' ' . date('H:i:s'),
            'type'           => $type,
            'amount'         => $amount,
            'capital_before' => $capitalBefore,
            'capital_after'  => $capitalAfter,
            'comments'       => $request->comments ?? "Opération de $type enregistrée via interface Admin",
            'created_at'     => now(),
            'updated_at'     => now()
        ]);

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
                'user_id' => $transaction->user_id,
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
                'user_id' => $transaction->user_id,
                'type' => 'frais_gestion',
                'amount' => $request->amount_frais,
                'capital_before' => $capitalAvant,
                'capital_after' => $capitalApres,
                'date_operation' => now(),
                'comments'    => 'Frais de gestion de ' . number_format($request->amount_frais) . ' XAF',
            ]);

            DB::commit();

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
        }

        return back()->with('success', 'L\'opération sur les intérêts a été enregistrée.');
    }
}
