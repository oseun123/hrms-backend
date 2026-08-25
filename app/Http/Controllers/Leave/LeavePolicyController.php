<?php

namespace App\Http\Controllers\Leave;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Leave\LeavePolicy;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeavePolicyController extends Controller
{
    use HandlesApiErrors;

    public function index(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $query = LeavePolicy::with(['leaveType', 'leaveGroup', 'workflow'])
                ->where('tenant_id', $tenantId)
                ->whereHas('leaveGroup'); // Ensure group still exists

            if ($request->has('leave_group_id')) {
                $query->where('leave_group_id', $request->leave_group_id);
            }

            if ($request->has('leave_type_id')) {
                $query->where('leave_type_id', $request->leave_type_id);
            }

            $leavePolicies = $query->get();
            return ApiResponse::success($leavePolicies);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave policies');
        }
    }

    public function store(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $validated = $request->validate([
                'leave_type_id' => 'required|exists:leave_types,id,tenant_id,' . $tenantId,
                'leave_group_id' => 'required|exists:leave_groups,id,tenant_id,' . $tenantId,
                'entitlement_days' => 'required|numeric|min:0',
                'accrual_frequency' => 'required|in:yearly,monthly,on_hire,manual',
                'is_prorated' => 'boolean',
                'allow_carry_forward' => 'boolean',
                'max_carry_forward_days' => 'numeric|min:0',
                'carry_forward_expiry_months' => 'nullable|integer|min:1',
                'min_service_days' => 'integer|min:0',
                'max_consecutive_days' => 'nullable|integer|min:1',
                'notice_period_days' => 'integer|min:0',
                'allow_negative_balance' => 'boolean',
                'max_negative_days' => 'numeric|min:0',
                'include_public_holidays' => 'boolean',
                'include_weekends' => 'boolean',
                'allow_backdated_leave' => 'boolean',
                'max_backdated_days' => 'integer|min:0',
                'is_active' => 'boolean',
                'leave_workflow_id' => 'nullable|exists:leave_workflows,id,tenant_id,' . $tenantId,
            ]);

            if (empty($validated['leave_workflow_id'])) {
                $defaultWorkflow = \App\Models\Leave\LeaveWorkflow::where('tenant_id', $tenantId)
                    ->where('name', 'Manual (Legacy)')
                    ->first();
                if ($defaultWorkflow) {
                    $validated['leave_workflow_id'] = $defaultWorkflow->id;
                }
            }

            $leavePolicy = LeavePolicy::create(array_merge($validated, ['tenant_id' => $tenantId]));
            return ApiResponse::success($leavePolicy, 'Leave policy created successfully', 201);
        } catch (\Exception $e) {
            return $this->handleException($e, 'creating leave policy');
        }
    }

    public function show($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $leavePolicy = LeavePolicy::with(['leaveType', 'leaveGroup'])
                ->where('tenant_id', $tenantId)
                ->findOrFail($id);
            return ApiResponse::success($leavePolicy);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave policy');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $leavePolicy = LeavePolicy::where('tenant_id', $tenantId)->findOrFail($id);

            $validated = $request->validate([
                'leave_type_id' => 'exists:leave_types,id,tenant_id,' . $tenantId,
                'leave_group_id' => 'exists:leave_groups,id,tenant_id,' . $tenantId,
                'entitlement_days' => 'numeric|min:0',
                'accrual_frequency' => 'in:yearly,monthly,on_hire,manual',
                'is_prorated' => 'boolean',
                'allow_carry_forward' => 'boolean',
                'max_carry_forward_days' => 'numeric|min:0',
                'carry_forward_expiry_months' => 'nullable|integer|min:1',
                'min_service_days' => 'integer|min:0',
                'max_consecutive_days' => 'nullable|integer|min:1',
                'notice_period_days' => 'integer|min:0',
                'allow_negative_balance' => 'boolean',
                'max_negative_days' => 'numeric|min:0',
                'include_public_holidays' => 'boolean',
                'include_weekends' => 'boolean',
                'allow_backdated_leave' => 'boolean',
                'max_backdated_days' => 'integer|min:0',
                'is_active' => 'boolean',
                'leave_workflow_id' => 'nullable|exists:leave_workflows,id,tenant_id,' . $tenantId,
            ]);

            $leavePolicy->update($validated);
            return ApiResponse::success($leavePolicy, 'Leave policy updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'updating leave policy');
        }
    }

    public function destroy($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $leavePolicy = LeavePolicy::where('tenant_id', $tenantId)->findOrFail($id);
            $leavePolicy->delete();
            return ApiResponse::success(null, 'Leave policy deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'deleting leave policy');
        }
    }
}
