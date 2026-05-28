<?php

namespace App\Services\Leave;

use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeavePolicy;
use App\Models\Hris\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveBalanceService
{
    protected \App\Services\LeaveYearService $leaveYearService;
    protected \App\Services\Leave\LeaveService $leaveService;

    public function __construct(
        \App\Services\LeaveYearService $leaveYearService,
        \App\Services\Leave\LeaveService $leaveService
    ) {
        $this->leaveYearService = $leaveYearService;
        $this->leaveService = $leaveService;
    }

    /**
     * Get the effective balance for an employee.
     * If no record exists in leave_balances, it returns a virtual balance based on the policy.
     */
    public function getEffectiveBalance(Employee $employee, LeavePolicy $policy, int $year): LeaveBalance
    {
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $policy->leave_type_id)
            ->where('year', $year)
            ->first();

        if ($balance) {
            return $balance;
        }

        // Virtualize balance from policy
        return new LeaveBalance([
            'tenant_id' => $employee->tenant_id,
            'employee_id' => $employee->id,
            'leave_type_id' => $policy->leave_type_id,
            'year' => $year,
            'entitlement' => (float) $policy->entitlement_days,
            'carried_forward' => 0,
            'accrued' => 0,
            'used' => 0,
            'pending_approval' => 0,
            'manual_adjustment' => 0,
        ]);
    }

    /**
     * Accrue leave for a specific employee and policy.
     */
    public function accrue(Employee $employee, LeavePolicy $policy, $year = null)
    {
        $year = $year ?: $this->leaveYearService->getCurrentLeaveYear($employee->tenant_id);
        $tenantId = $employee->tenant_id;

        $balance = LeaveBalance::firstOrCreate([
            'tenant_id' => $tenantId,
            'employee_id' => $employee->id,
            'leave_type_id' => $policy->leave_type_id,
            'year' => $year,
        ]);

        $entitlement = (float) $policy->entitlement_days;

        // Proration Logic
        if ($policy->is_prorated && $employee->employmentDetails->hire_date) {
            $hireDate = $employee->employmentDetails->hire_date;
            if ($hireDate->year == $year) {
                // Joiner in the current year
                $totalDaysInYear = $hireDate->copy()->endOfYear()->dayOfYear;
                $daysRemaining = $hireDate->diffInDays($hireDate->copy()->endOfYear()) + 1;
                $entitlement = round(($daysRemaining / $totalDaysInYear) * $entitlement, 2);
            }
        }

        $balance->update([
            'entitlement' => $entitlement,
        ]);

        return $balance;
    }

    /**
     * Run monthly accruals for all employees based on policies.
     */
    public function runMonthlyAccruals($tenantId)
    {
        // Monthly accrual logic usually adds a fractional amount
        // Implementation depends on whether we want to add (Entitlement / 12) each month
        // or just calculate entitlement once and let it stay. 
        // For now, let's stick to the policy accrual_frequency.
    }

    /**
     * Handle year-end carry forward.
     */
    public function carryForward($employeeId, $leaveTypeId, $fromYear)
    {
        $toYear = $fromYear + 1;
        $oldBalance = LeaveBalance::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $fromYear)
            ->first();

        if (!$oldBalance) return;

        $policy = LeavePolicy::where('leave_group_id', $oldBalance->employee->employmentDetails->leave_group_id)
            ->where('leave_type_id', $leaveTypeId)
            ->first();

        if (!$policy || !$policy->allow_carry_forward) return;

        $available = $oldBalance->available_balance;
        $carryAmount = min($available, (float) $policy->max_carry_forward_days);

        $newBalance = LeaveBalance::firstOrCreate([
            'tenant_id' => $oldBalance->tenant_id,
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
            'year' => $toYear,
        ]);

        $newBalance->update([
            'carried_forward' => $carryAmount,
        ]);
    }

    /**
     * Process year-end rollover for all employees in a tenant.
     */
    public function processYearEndForTenant(int $tenantId, int $fromYear): array
    {
        $toYear = $fromYear + 1;
        $employeesProcessed = 0;
        $totalCarriedForward = 0;

        // Get all active employees with leave groups
        $employees = Employee::where('tenant_id', $tenantId)
            ->whereHas('employmentDetails', function ($q) {
                $q->whereNotNull('leave_group_id');
            })
            ->with('employmentDetails.leaveGroup.policies.leaveType')
            ->get();

        foreach ($employees as $employee) {
            $leaveGroupId = $employee->employmentDetails->leave_group_id ?? null;

            if (!$leaveGroupId) continue;

            // Get all active policies for this employee's leave group
            $policies = LeavePolicy::where('leave_group_id', $leaveGroupId)
                ->where('is_active', true)
                ->get();

            foreach ($policies as $policy) {
                // Process carry forward for each leave type
                $this->carryForward($employee->id, $policy->leave_type_id, $fromYear);

                // Accrue new entitlement for the new year
                $this->accrue($employee, $policy, $toYear);
            }

            $employeesProcessed++;
        }

        return [
            'employees_processed' => $employeesProcessed,
            'from_year' => $fromYear,
            'to_year' => $toYear,
        ];
    }
}
