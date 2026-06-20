<?php

namespace App\Http\Middleware;

use App\Services\AccessControlService;
use Closure;
use Illuminate\Http\Request;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        foreach ($permissions as $permission) {
            if (AccessControlService::can($permission)) {
                return $next($request);
            }
        }

        abort(403, 'Acces refuse.');
    }
}
