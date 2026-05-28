<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use Illuminate\Support\Facades\Auth;

trait TenantScoped
{
    /**
     * Boot the tenant scoped trait.
     */
    public static function bootTenantScoped()
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            if (Auth::check()) {
                /** @var \App\Models\User $user */
                $user = Auth::user();

                $token = method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;
                $tenantId = ($token && isset($token->tenant_id)) ? $token->tenant_id : $user->tenant_id;

                if ($tenantId && !$model->tenant_id) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }

    /**
     * Scope a query to only include models of the current tenant.
     * Helpful if you want to bypass other global scopes but keep tenancy.
     */
    public function scopeForCurrentTenant($query)
    {
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $token = method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;
            $tenantId = ($token && isset($token->tenant_id)) ? $token->tenant_id : $user->tenant_id;

            return $query->where('tenant_id', $tenantId);
        }
        return $query;
    }
}
