<?php

namespace App\Http\Middleware;

use App\Models\AuthCode;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureOtpIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        if ($request->routeIs('otp.form', 'code-verification', 'otp.cancel', 'logout')) {
            return $next($request);
        }

        $latestCode = AuthCode::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestCode && (int)$latestCode->status !== 1) {
            return redirect()->route('otp.form');
        }

        return $next($request);
    }
}
