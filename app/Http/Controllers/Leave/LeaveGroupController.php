<?php

namespace App\Http\Controllers\Leave;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Leave\LeaveGroup;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveGroupController extends Controller
{
    use HandlesApiErrors;

    public function index()
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $leaveGroups = LeaveGroup::with(['policies.leaveType', 'policies.workflow'])->where('tenant_id', $tenantId)->get();
            return ApiResponse::success($leaveGroups);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave groups');
        }
    }

    public function store(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
                'entitlements' => 'nullable|array',
                'entitlements.*.leave_type_id' => 'required|exists:leave_types,id,tenant_id,' . $tenantId,
                'entitlements.*.days' => 'nullable|numeric|min:0',
                'entitlements.*.entitlement_days' => 'nullable|numeric|min:0',
                'entitlements.*.accrual_frequency' => 'nullable|in:yearly,monthly,on_hire,manual',
                'entitlements.*.is_prorated' => 'nullable|boolean',
                'entitlements.*.allow_carry_forward' => 'nullable|boolean',
                'entitlements.*.max_carry_forward_days' => 'nullable|numeric|min:0',
                'entitlements.*.carry_forward_expiry_months' => 'nullable|integer|min:1',
                'entitlements.*.min_service_days' => 'nullable|integer|min:0',
                'entitlements.*.notice_period_days' => 'nullable|integer|min:0',
                'entitlements.*.max_consecutive_days' => 'nullable|integer|min:0',
                'entitlements.*.allow_backdated_leave' => 'nullable|boolean',
                'entitlements.*.max_backdated_days' => 'nullable|integer|min:0',
                'entitlements.*.include_public_holidays' => 'nullable|boolean',
                'entitlements.*.include_weekends' => 'nullable|boolean',
                'entitlements.*.leave_workflow_id' => 'nullable|exists:leave_workflows,id,tenant_id,' . $tenantId,
                'entitlements.*.is_active' => 'nullable|boolean',
            ]);

            $leaveGroup = LeaveGroup::create([
                'tenant_id' => $tenantId,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            if (!empty($validated['entitlements'])) {
                foreach ($validated['entitlements'] as $entitlement) {
                    $policyData = [
                        'tenant_id' => $tenantId,
                        'leave_type_id' => $entitlement['leave_type_id'],
                        'entitlement_days' => $entitlement['entitlement_days'] ?? $entitlement['days'] ?? 0,
                        'accrual_frequency' => $entitlement['accrual_frequency'] ?? 'yearly',
                        'is_prorated' => $entitlement['is_prorated'] ?? true,
                        'allow_carry_forward' => $entitlement['allow_carry_forward'] ?? false,
                        'max_carry_forward_days' => $entitlement['max_carry_forward_days'] ?? 0,
                        'carry_forward_expiry_months' => $entitlement['carry_forward_expiry_months'] ?? null,
                        'min_service_days' => $entitlement['min_service_days'] ?? 0,
                        'notice_period_days' => $entitlement['notice_period_days'] ?? 0,
                        'max_consecutive_days' => !empty($entitlement['max_consecutive_days']) ? $entitlement['max_consecutive_days'] : null,
                        'allow_backdated_leave' => $entitlement['allow_backdated_leave'] ?? false,
                        'max_backdated_days' => $entitlement['max_backdated_days'] ?? 0,
                        'include_public_holidays' => $entitlement['include_public_holidays'] ?? false,
                        'include_weekends' => $entitlement['include_weekends'] ?? false,
                        'leave_workflow_id' => $entitlement['leave_workflow_id'] ?? null,
                        'is_active' => $entitlement['is_active'] ?? true,
                    ];

                    $leaveGroup->policies()->create($policyData);
                }
            }

            return ApiResponse::success($leaveGroup->load(['policies.leaveType', 'policies.workflow']), 'Leave group created successfully', 201);
        } catch (\Exception $e) {
            return $this->handleException($e, 'creating leave group');
        }
    }

    public function show($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $leaveGroup = LeaveGroup::with(['policies.leaveType', 'policies.workflow'])
                ->where('tenant_id', $tenantId)
                ->findOrFail($id);
            return ApiResponse::success($leaveGroup);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave group');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $leaveGroup = LeaveGroup::where('tenant_id', $tenantId)->findOrFail($id);

            $validated = $request->validate([
                'name' => 'string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
                'entitlements' => 'nullable|array',
                'entitlements.*.leave_type_id' => 'required|exists:leave_types,id',
                'entitlements.*.days' => 'nullable|numeric|min:0',
                'entitlements.*.entitlement_days' => 'nullable|numeric|min:0',
                'entitlements.*.accrual_frequency' => 'nullable|in:yearly,monthly,on_hire,manual',
                'entitlements.*.is_prorated' => 'nullable|boolean',
                'entitlements.*.allow_carry_forward' => 'nullable|boolean',
                'entitlements.*.max_carry_forward_days' => 'nullable|numeric|min:0',
                'entitlements.*.carry_forward_expiry_months' => 'nullable|integer|min:1',
                'entitlements.*.min_service_days' => 'nullable|integer|min:0',
                'entitlements.*.notice_period_days' => 'nullable|integer|min:0',
                'entitlements.*.max_consecutive_days' => 'nullable|integer|min:0',
                'entitlements.*.allow_backdated_leave' => 'nullable|boolean',
                'entitlements.*.max_backdated_days' => 'nullable|integer|min:0',
                'entitlements.*.include_public_holidays' => 'nullable|boolean',
                'entitlements.*.include_weekends' => 'nullable|boolean',
                'entitlements.*.leave_workflow_id' => 'nullable|exists:leave_workflows,id,tenant_id,' . $tenantId,
                'entitlements.*.is_active' => 'nullable|boolean',
            ]);

            $leaveGroup->update($request->only(['name', 'description', 'is_active']));

            if (isset($validated['entitlements'])) {
                $typeIds = [];
                foreach ($validated['entitlements'] as $entitlement) {
                    $typeIds[] = $entitlement['leave_type_id'];

                    $policyData = [
                        'tenant_id' => $tenantId,
                        'leave_type_id' => $entitlement['leave_type_id'],
                        'entitlement_days' => $entitlement['entitlement_days'] ?? $entitlement['days'] ?? 0,
                        'accrual_frequency' => $entitlement['accrual_frequency'] ?? 'yearly',
                        'is_prorated' => $entitlement['is_prorated'] ?? true,
                        'allow_carry_forward' => $entitlement['allow_carry_forward'] ?? false,
                        'max_carry_forward_days' => $entitlement['max_carry_forward_days'] ?? 0,
                        'carry_forward_expiry_months' => $entitlement['carry_forward_expiry_months'] ?? null,
                        'min_service_days' => $entitlement['min_service_days'] ?? 0,
                        'notice_period_days' => $entitlement['notice_period_days'] ?? 0,
                        'max_consecutive_days' => !empty($entitlement['max_consecutive_days']) ? $entitlement['max_consecutive_days'] : null,
                        'allow_backdated_leave' => $entitlement['allow_backdated_leave'] ?? false,
                        'max_backdated_days' => $entitlement['max_backdated_days'] ?? 0,
                        'include_public_holidays' => $entitlement['include_public_holidays'] ?? false,
                        'include_weekends' => $entitlement['include_weekends'] ?? false,
                        'leave_workflow_id' => $entitlement['leave_workflow_id'] ?? null,
                        'is_active' => $entitlement['is_active'] ?? true,
                    ];

                    // Check for existing policy including soft-deleted ones
                    $policy = $leaveGroup->policies()->withTrashed()
                        ->where('leave_type_id', $entitlement['leave_type_id'])
                        ->first();

                    if ($policy) {
                        $policy->restore();
                        $policy->update($policyData);
                    } else {
                        $leaveGroup->policies()->create($policyData);
                    }
                }
                // Sync by soft-deleting types not in the current list
                $leaveGroup->policies()->whereNotIn('leave_type_id', $typeIds)->delete();
            }

            return ApiResponse::success($leaveGroup->load(['policies.leaveType', 'policies.workflow']), 'Leave group updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'updating leave group');
        }
    }

    public function destroy($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $leaveGroup = LeaveGroup::where('tenant_id', $tenantId)->findOrFail($id);

            // Cascade delete policies
            $leaveGroup->policies()->delete();

            $leaveGroup->delete();
            return ApiResponse::success(null, 'Leave group and its policies deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'deleting leave group');
        }
    }
}
