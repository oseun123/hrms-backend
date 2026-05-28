<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // Sanctum tokens in this app have a tenant_id
            $token = method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;
            $tenantId = ($token && isset($token->tenant_id)) ? $token->tenant_id : $user->tenant_id;

            // Ensure we always filter, even if ID is missing (to prevent seeing everything)
            $builder->where($model->getTable() . '.tenant_id', $tenantId ?? -1);
        }
    }
}
