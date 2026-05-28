<?php

namespace App\Http\Controllers\Performance;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Performance\PerformanceCycle;
use App\Models\Performance\PerformanceSetting;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PerformanceSettingsController extends Controller
{
    use HandlesApiErrors;

    /**
     * Get performance settings for tenant
     */
    public function index()
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $settings = PerformanceSetting::where('tenant_id', $tenantId)->first();

            if (!$settings) {
                // Return default settings if none exist
                return ApiResponse::success([
                    'cycle_type' => 'annual',
                    'reviewer_levels' => 2,
                    'final_score_level' => 2,
                    'results_weight' => 70.00,
                    'competency_weight' => 30.00,
                    'goal_structure' => 'simple',
                    'enforce_submit_back' => false,
                ]);
            }

            return ApiResponse::success($settings);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching performance settings');
        }
    }

    /**
     * Update performance settings
     */
    public function update(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $validated = $request->validate([
                'cycle_type' => 'in:monthly,quarterly,bi-annual,annual',
                'reviewer_levels' => 'integer|min:2|max:10',
                'final_score_level' => 'integer|min:1|max:10',
                'results_weight' => 'numeric|min:0|max:100',
                'competency_weight' => 'numeric|min:0|max:100',
                'goal_structure' => 'in:simple,complex',
                'enforce_submit_back' => 'boolean',
                'reviewer_config' => 'nullable|array',
            ]);

            // Validate that weights sum to 100
            if (isset($validated['results_weight']) && isset($validated['competency_weight'])) {
                if (abs(($validated['results_weight'] + $validated['competency_weight']) - 100) > 0.01) {
                    return ApiResponse::error('Results weight and competency weight must sum to 100%', 422);
                }
            }


            $settings = PerformanceSetting::updateOrCreate(
                ['tenant_id' => $tenantId],
                array_merge($validated, ['tenant_id' => $tenantId])
            );

            return ApiResponse::success($settings, 'Performance settings updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'updating performance settings');
        }
    }

    /**
     * Get current performance cycle info
     */
    public function getCycleInfo()
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $currentYear = date('Y');

            $cycle = PerformanceCycle::where('tenant_id', $tenantId)
                ->where('year', $currentYear)
                ->first();

            $settings = PerformanceSetting::where('tenant_id', $tenantId)->first();

            return ApiResponse::success([
                'current_cycle' => $cycle,
                'is_locked' => $cycle && $cycle->locked_at ? true : false,
                'settings' => $settings,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching cycle info');
        }
    }
}
