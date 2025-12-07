<?php

if (!function_exists('tenant_id')) {
    /**
     * Get the current tenant ID from the authenticated user's token or user record
     *
     * @return int|null
     */
    function tenant_id(): ?int
    {
        if (!auth()->check()) {
            return null;
        }

        $token = auth()->user()->currentAccessToken();
        return $token ? $token->tenant_id : auth()->user()->tenant_id;
    }
}

if (!function_exists('tenant')) {
    /**
     * Get the current tenant model
     *
     * @return \App\Models\Tenant|null
     */
    function tenant(): ?\App\Models\Tenant
    {
        $tenantId = tenant_id();
        return $tenantId ? \App\Models\Tenant::find($tenantId) : null;
    }
}
