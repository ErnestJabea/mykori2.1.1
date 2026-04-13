<?php

namespace App\Http\Controllers;

use App\Transaction;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;


class TransactionViewController extends Controller
{
    //
    protected $fillable = [
        'title',
        'ref',
        'payment_mode',
        'amount',
        'status',
        'user_id',
        'vl_buy',
        'nb_part',
        'product_id'
    ];
    public function index(Request $request)
    {
        if (auth()->check()) {
            // L'utilisateur est connecté, récupérez les produits
            $products = \App\Product::get();
            $transactions = Transaction::with('sousTransactions')->where('user_id', Auth::user()->id)->paginate(10);

            if ($request->ajax()) {
                return response()->json(view('front-end.partials.transactions_partial', compact('transactions'))->render());
            }

            return view('front-end.my-history', compact('transactions'));
        } else {
            // Redirigez l'utilisateur vers la page de connexion
            return redirect('/connexion');
        }
    }
}
