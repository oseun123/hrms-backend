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
            $leaveGroups = LeaveGroup::with(['policies.leaveType'])->where('tenant_id', $tenantId)->get();
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
                'entitlements.*.leave_type_id' => 'required|exists:leave_types,id',
                'entitlements.*.days' => 'required|numeric|min:0',
            ]);

            $leaveGroup = LeaveGroup::create([
                'tenant_id' => $tenantId,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            if (!empty($validated['entitlements'])) {
                foreach ($validated['entitlements'] as $entitlement) {
                    $leaveGroup->policies()->create([
                        'tenant_id' => $tenantId,
                        'leave_type_id' => $entitlement['leave_type_id'],
                        'entitlement_days' => $entitlement['days'],
                        'accrual_frequency' => 'yearly', // Default
                    ]);
                }
            }

            return ApiResponse::success($leaveGroup->load('policies.leaveType'), 'Leave group created successfully', 201);
        } catch (\Exception $e) {
            return $this->handleException($e, 'creating leave group');
        }
    }

    public function show($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $leaveGroup = LeaveGroup::with(['policies.leaveType'])
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
                'entitlements.*.days' => 'required|numeric|min:0',
            ]);

            $leaveGroup->update($request->only(['name', 'description', 'is_active']));

            if (isset($validated['entitlements'])) {
                $typeIds = [];
                foreach ($validated['entitlements'] as $entitlement) {
                    $typeIds[] = $entitlement['leave_type_id'];

                    // Check for existing policy including soft-deleted ones
                    $policy = $leaveGroup->policies()->withTrashed()
                        ->where('leave_type_id', $entitlement['leave_type_id'])
                        ->first();

                    if ($policy) {
                        $policy->restore();
                        $policy->update([
                            'entitlement_days' => $entitlement['days'],
                            'is_active' => true
                        ]);
                    } else {
                        $leaveGroup->policies()->create([
                            'tenant_id' => $tenantId,
                            'leave_type_id' => $entitlement['leave_type_id'],
                            'entitlement_days' => $entitlement['days'],
                            'accrual_frequency' => 'yearly',
                            'is_active' => true
                        ]);
                    }
                }
                // Optional: Sync by soft-deleting types not in the current list
                $leaveGroup->policies()->whereNotIn('leave_type_id', $typeIds)->delete();
            }

            return ApiResponse::success($leaveGroup->load('policies.leaveType'), 'Leave group updated successfully');
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
