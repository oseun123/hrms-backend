<?php

namespace App\Http\Controllers\Leave;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeavePolicy;
use App\Models\Hris\Employee;
use App\Services\LeaveYearService;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveBalanceController extends Controller
{
    use HandlesApiErrors;

    protected LeaveYearService $leaveYearService;

    public function __construct(LeaveYearService $leaveYearService)
    {
        $this->leaveYearService = $leaveYearService;
    }

    public function index(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $query = LeaveBalance::with(['leaveType', 'employee.employmentDetails.leaveGroup'])
                ->where('tenant_id', $tenantId);

            if ($request->has('employee_id')) {
                $employeeId = $request->employee_id;
                $year = $request->has('year') ? $request->year : $this->leaveYearService->getCurrentLeaveYear($tenantId);

                // Sync balances for the filtered employee
                $this->syncBalances((int)$employeeId, (int)$year);

                $query->where('employee_id', $employeeId);
            }

            if ($request->has('leave_type_id')) {
                $query->where('leave_type_id', $request->leave_type_id);
            }

            if ($request->has('leave_group_id')) {
                $groupId = $request->leave_group_id;
                $query->whereHas('employee.employmentDetails', function ($q) use ($groupId) {
                    $q->where('leave_group_id', $groupId);
                });
            }

            if ($request->has('year')) {
                $query->where('year', $request->year);
            } else {
                // Use the current leave year from the service
                $query->where('year', $this->leaveYearService->getCurrentLeaveYear($tenantId));
            }

            $balances = $query->get();
            return ApiResponse::success($balances);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave balances');
        }
    }

    public function myBalance()
    {
        try {
            $user = Auth::user();
            // Assuming user has one employee record
            $employee = Employee::where('user_id', $user->id)->first();

            if (!$employee) {
                return ApiResponse::error('Employee record not found for this user', 404);
            }

            $currentLeaveYear = $this->leaveYearService->getCurrentLeaveYear($user->tenant_id);

            // Sync balances for current user before fetching
            $this->syncBalances($employee->id, (int)$currentLeaveYear);

            $balances = LeaveBalance::with('leaveType')
                ->where('employee_id', $employee->id)
                ->where('year', $currentLeaveYear)
                ->get();

            // Fetch policies for the employee's group to ensure all types are represented
            $employee->load('employmentDetails.leaveGroup');
            $leaveGroup = $employee->employmentDetails->leaveGroup ?? null;

            if ($leaveGroup) {
                $policies = LeavePolicy::where('leave_group_id', $leaveGroup->id)
                    ->where('is_active', true)
                    ->get();

                $mappedBalances = $balances->keyBy('leave_type_id');

                $effectiveBalances = $policies->map(function ($policy) use ($mappedBalances, $employee, $currentLeaveYear) {
                    if ($mappedBalances->has($policy->leave_type_id)) {
                        return $mappedBalances->get($policy->leave_type_id);
                    }

                    // Create a virtual balance object if no record exists
                    return new LeaveBalance([
                        'employee_id' => $employee->id,
                        'leave_type_id' => $policy->leave_type_id,
                        'year' => $currentLeaveYear,
                        'entitlement' => $policy->entitlement_days,
                        'carried_forward' => 0,
                        'accrued' => 0,
                        'used' => 0,
                        'pending_approval' => 0,
                    ]);
                })->load('leaveType');

                return ApiResponse::success([
                    'leave_group' => $leaveGroup ? ['id' => $leaveGroup->id, 'name' => $leaveGroup->name] : null,
                    'balances' => $effectiveBalances
                ]);
            }

            return ApiResponse::success([
                'leave_group' => null,
                'balances' => $balances
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching personal leave balance');
        }
    }

    public function adjust(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id,tenant_id,' . $tenantId,
                'leave_type_id' => 'required|exists:leave_types,id,tenant_id,' . $tenantId,
                'year' => 'required|integer',
                'adjustment_type' => 'required|in:addition,deduction',
                'amount' => 'required|numeric|min:0.5',
                'reason' => 'required|string',
            ]);

            DB::beginTransaction();

            $balance = LeaveBalance::firstOrCreate([
                'tenant_id' => $tenantId,
                'employee_id' => $validated['employee_id'],
                'leave_type_id' => $validated['leave_type_id'],
                'year' => $validated['year'],
            ]);

            if ($validated['adjustment_type'] === 'addition') {
                $balance->increment('manual_adjustment', (float) $validated['amount']);
                $adjustmentAmount = (float) $validated['amount'];
            } else {
                $balance->decrement('manual_adjustment', (float) $validated['amount']);
                $adjustmentAmount = -(float) $validated['amount'];
            }

            // Record adjustment history
            \App\Models\Leave\LeaveAdjustment::create([
                'tenant_id' => $tenantId,
                'employee_id' => $validated['employee_id'],
                'leave_type_id' => $validated['leave_type_id'],
                'adjusted_by' => $user->id,
                'type' => 'correction', // Use one of: correction, bonus, penalty, manual_accrual
                'adjustment_amount' => $adjustmentAmount,
                'reason' => $validated['reason'],
            ]);

            DB::commit();

            return ApiResponse::success($balance, 'Balance adjusted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'adjusting leave balance');
        }
    }

    public function adjustments(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $query = \App\Models\Leave\LeaveAdjustment::with(['leaveType', 'adjuster.employee'])
                ->where('tenant_id', $tenantId);

            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->has('leave_type_id')) {
                $query->where('leave_type_id', $request->leave_type_id);
            }

            $adjustments = $query->orderBy('created_at', 'desc')->get();

            // Map to include a clean adjuster name
            $mapped = $adjustments->map(function ($a) {
                return [
                    'id' => $a->id,
                    'leave_type' => $a->leaveType->name ?? 'N/A',
                    'adjustment_amount' => $a->adjustment_amount,
                    'type' => $a->type,
                    'reason' => $a->reason,
                    'adjusted_by_name' => $a->adjuster?->employee?->full_name ?? $a->adjuster?->name ?? 'System',
                    'created_at' => $a->created_at->format('Y-m-d H:i'),
                ];
            });

            return ApiResponse::success($mapped);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching adjustment history');
        }
    }

    /**
     * Synchronize leave balance pending amounts with actual requests.
     */
    protected function syncBalances(int $employeeId, int $year)
    {
        try {
            $balances = LeaveBalance::where('employee_id', $employeeId)
                ->where('year', $year)
                ->get();

            $boundaries = $this->leaveYearService->getLeaveYearBoundaries($year, Auth::user()->tenant_id);

            foreach ($balances as $balance) {
                $actualPending = \App\Models\Leave\LeaveRequest::where('employee_id', $employeeId)
                    ->where('leave_type_id', $balance->leave_type_id)
                    ->whereBetween('start_date', [$boundaries['start'], $boundaries['end']])
                    ->where('status', 'pending')
                    ->sum('duration_days');

                if ((float)$balance->pending_approval !== (float)$actualPending) {
                    $balance->update(['pending_approval' => $actualPending]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync balances for employee {$employeeId}: " . $e->getMessage());
        }
    }
}
