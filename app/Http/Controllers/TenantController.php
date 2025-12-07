<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantController extends Controller
{
    /**
     * Get tenant by slug (public endpoint)
     * This allows users to lookup tenant_id before logging in
     */
    public function getBySlug($slug)
    {
        try {
            $tenant = Tenant::where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if (!$tenant) {
                return ApiResponse::notFound('Tenant not found or inactive');
            }

            return ApiResponse::success([
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'domain' => $tenant->domain,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching tenant by slug: ' . $e->getMessage());
            return ApiResponse::serverError('An error occurred while fetching tenant information');
        }
    }
}
