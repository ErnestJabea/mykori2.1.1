<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LoginController extends Controller
{
    protected function authenticated($request, $user)
    {
        if (!auth()->check()) {
            return redirect('/connexion'); // Redirige vers la page de connexion si l'utilisateur n'est pas connecté
        }
        if ($user->isAdmin()) {
            return redirect('/admin');
        } elseif ($user->isMember()) {
            return redirect('/dashboard');
        }
    }

}
