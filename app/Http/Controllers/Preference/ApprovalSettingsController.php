<?php

namespace App\Http\Controllers\Preference;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Preference\ProfileApprovalSetting;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprovalSettingsController extends Controller
{
    use HandlesApiErrors;

    /**
     * Get all approval settings for the current tenant
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $settings = ProfileApprovalSetting::where('tenant_id', $tenantId)
                ->get()
                ->map(function ($setting) {
                    return [
                        'section' => $setting->section,
                        'requires_approval' => $setting->requires_approval,
                    ];
                });

            return ApiResponse::success($settings);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to fetch approval settings');
        }
    }

    /**
     * Update approval settings (bulk update)
     */
    public function update(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $request->validate([
                'settings' => 'required|array',
                'settings.*.section' => 'required|string',
                'settings.*.requires_approval' => 'required|boolean',
            ]);

            DB::beginTransaction();

            foreach ($request->settings as $settingData) {
                ProfileApprovalSetting::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'section' => $settingData['section'],
                    ],
                    [
                        'requires_approval' => $settingData['requires_approval'],
                    ]
                );
            }

            DB::commit();

            return ApiResponse::success(null, 'Approval settings updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to update approval settings');
        }
    }
}
