<?php

namespace App\Http\Controllers\Leave;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\EmployeeEmploymentDetail;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LeaveGroupAssignmentController extends Controller
{
    use HandlesApiErrors;

    /**
     * Get all employees with their leave group assignments
     */
    public function index(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $query = EmployeeEmploymentDetail::with(['employee', 'leaveGroup', 'department'])
                ->whereHas('employee', function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)
                        ->where('is_active', true);
                });

            // Filter by department if specified
            if ($request->has('department_id') && $request->department_id) {
                $query->where('department_id', $request->department_id);
            }

            // Filter by leave group if specified
            if ($request->has('leave_group_id') && $request->leave_group_id) {
                $query->where('leave_group_id', $request->leave_group_id);
            }

            // Search by employee name or number
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_number', 'like', "%{$search}%");
                });
            }

            $assignments = $query->get()->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'employee_id' => $detail->employee_id,
                    'employee_number' => $detail->employee->employee_number,
                    'employee_name' => $detail->employee->full_name,
                    'department' => $detail->department->name ?? 'N/A',
                    'department_id' => $detail->department_id,
                    'leave_group_id' => $detail->leave_group_id,
                    'leave_group_name' => $detail->leaveGroup->name ?? 'Not Assigned',
                ];
            });

            return ApiResponse::success($assignments);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave group assignments');
        }
    }

    /**
     * Assign leave group to employee
     */
    public function assign(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id,tenant_id,' . $tenantId,
                'leave_group_id' => 'required|exists:leave_groups,id,tenant_id,' . $tenantId,
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError('Validation failed', $validator->errors());
            }

            $employmentDetails = EmployeeEmploymentDetail::whereHas('employee', function ($q) use ($tenantId, $request) {
                $q->where('tenant_id', $tenantId)
                    ->where('id', $request->employee_id);
            })->first();

            if (!$employmentDetails) {
                return ApiResponse::notFound('Employee employment details not found');
            }

            $employmentDetails->leave_group_id = $request->leave_group_id;
            $employmentDetails->save();

            // Automatically accrue balances for the newly assigned group
            $employee = \App\Models\Hris\Employee::find($request->employee_id);
            if ($employee) {
                $leaveBalanceService = app(\App\Services\Leave\LeaveBalanceService::class);
                $leaveYearService = app(\App\Services\LeaveYearService::class);
                $year = $leaveYearService->getCurrentLeaveYear($tenantId);
                $policies = \App\Models\Leave\LeavePolicy::where('leave_group_id', $request->leave_group_id)
                    ->where('is_active', true)
                    ->get();

                foreach ($policies as $policy) {
                    $leaveBalanceService->accrue($employee, $policy, $year);
                }
            }

            return ApiResponse::success($employmentDetails, 'Leave group assigned successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'assigning leave group');
        }
    }

    /**
     * Bulk assign leave group to multiple employees
     */
    public function bulkAssign(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $validator = Validator::make($request->all(), [
                'employee_ids' => 'required|array',
                'employee_ids.*' => 'exists:employees,id,tenant_id,' . $tenantId,
                'leave_group_id' => 'required|exists:leave_groups,id,tenant_id,' . $tenantId,
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError('Validation failed', $validator->errors());
            }

            $updated = EmployeeEmploymentDetail::whereHas('employee', function ($q) use ($tenantId, $request) {
                $q->where('tenant_id', $tenantId)
                    ->whereIn('id', $request->employee_ids);
            })->update(['leave_group_id' => $request->leave_group_id]);

            // Automatically accrue balances for the newly assigned group
            $leaveBalanceService = app(\App\Services\Leave\LeaveBalanceService::class);
            $leaveYearService = app(\App\Services\LeaveYearService::class);
            $year = $leaveYearService->getCurrentLeaveYear($tenantId);
            $policies = \App\Models\Leave\LeavePolicy::where('leave_group_id', $request->leave_group_id)
                ->where('is_active', true)
                ->get();
            $employees = \App\Models\Hris\Employee::whereIn('id', $request->employee_ids)->get();

            foreach ($employees as $employee) {
                foreach ($policies as $policy) {
                    $leaveBalanceService->accrue($employee, $policy, $year);
                }
            }

            return ApiResponse::success(
                ['updated_count' => $updated],
                "Successfully assigned leave group to {$updated} employee(s)"
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'bulk assigning leave group');
        }
    }
}
