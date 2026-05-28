<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth('super-admin')->check()) {
            return ApiResponse::error('Unauthorized. Super admin access required.', 401);
        }

        return $next($request);
    }
}
