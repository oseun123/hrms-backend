<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InjectTenantId
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only inject tenant_id for authenticated requests
        if ($request->user()) {
            $token = $request->user()->currentAccessToken();
            $tenantId = $token ? $token->tenant_id : $request->user()->tenant_id;

            // Merge tenant_id into request
            $request->merge(['tenant_id' => $tenantId]);
        }

        return $next($request);
    }
}
