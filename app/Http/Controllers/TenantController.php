<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Tenant;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }
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

            if (! $tenant) {
                return ApiResponse::notFound('Tenant not found or inactive');
            }

            // Force append logo_url and theme_color which use accessors
            $tenant->append(['logo_url', 'theme_color']);

            return ApiResponse::success([
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'domain' => $tenant->domain,
                'theme_color' => $tenant->theme_color,
                'logo_url' => $tenant->logo_url,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching tenant by slug: ' . $e->getMessage());

            return ApiResponse::serverError('An error occurred while fetching tenant information');
        }
    }

    /**
     * Upload tenant logo
     */
    public function uploadLogo(Request $request)
    {
        try {
            $request->validate([
                'logo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $tenantId = $request->user()->tenant_id;

            // Get current logo to delete if exists
            $currentLogo = \App\Models\Preference\Preference::where('tenant_id', $tenantId)
                ->where('category', 'organization')
                ->where('key', 'logo_url')
                ->whereNull('user_id')
                ->value('value');

            if ($currentLogo && !str_starts_with($currentLogo, 'data:image')) {
                $this->fileUploadService->delete($currentLogo);
            }

            $uploadResult = $this->fileUploadService->upload(
                $request->file('logo'),
                'tenant-logos',
                [
                    'tenant_id' => $tenantId,
                    'validation' => ['image', 'mimes:jpeg,png,jpg', 'max:2048'],
                ]
            );

            // Save path to preferences
            \App\Models\Preference\Preference::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'user_id' => null,
                    'category' => 'organization',
                    'key' => 'logo_url',
                ],
                ['value' => $uploadResult['path']]
            );

            return ApiResponse::success([
                'url' => $uploadResult['url'],
                'path' => $uploadResult['path'],
            ], 'Logo uploaded successfully');
        } catch (\Exception $e) {
            Log::error('Error uploading logo: ' . $e->getMessage());
            return ApiResponse::error('Failed to upload logo: ' . $e->getMessage());
        }
    }

    /**
     * Delete tenant logo
     */
    public function deleteLogo(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;

            $preference = \App\Models\Preference\Preference::where('tenant_id', $tenantId)
                ->where('category', 'organization')
                ->where('key', 'logo_url')
                ->whereNull('user_id')
                ->first();

            if ($preference && $preference->value && !str_starts_with($preference->value, 'data:image')) {
                $this->fileUploadService->delete($preference->value);
            }

            if ($preference) {
                $preference->update(['value' => null]);
            }

            return ApiResponse::success(null, 'Logo deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error deleting logo: ' . $e->getMessage());
            return ApiResponse::error('Failed to delete logo');
        }
    }
}
