<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //

    public function index()
    {
        if (auth()->check()) {
            // L'utilisateur est connecté, récupérez les produits
            $products = \App\Product::get();
            return view('front-end.products', compact('products'));
        } else {
            // Redirigez l'utilisateur vers la page de connexion
            return redirect('/connexion');
        }
    }

}
