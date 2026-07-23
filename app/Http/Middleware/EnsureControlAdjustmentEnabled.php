<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureControlAdjustmentEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Check Feature Flag
        if (!config('app.features.control_adjustments', false)) {
            abort(404, "Le module Contrôle et Ajustements est désactivé.");
        }

        // 2. HERMETIC CLIENT LOCK (role_id = 2 is strictly forbidden)
        $user = Auth::user();
        if (!$user || (int)$user->role_id === 2) {
            abort(403, "Accès interdit : les clients ne disposent pas des habilitations pour ce module.");
        }

        return $next($request);
    }
}
