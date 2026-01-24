<?php

namespace App\Observers;

use App\Models\Hris\EmployeeEmploymentDetail;
use App\Models\Hris\EmployeeFinancialDetail;
use App\Models\Hris\EmployeeHistory;
use Illuminate\Support\Facades\Auth;

class EmployeeHistoryObserver
{
    /**
     * Handle the model "created" event.
     */
    public function created($model): void
    {
        $this->handleAnyChanges($model, true);
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated($model): void
    {
        $this->handleAnyChanges($model, false);
    }

    /**
     * Dispatch to specific handlers
     */
    protected function handleAnyChanges($model, bool $isCreated): void
    {
        if ($model instanceof EmployeeEmploymentDetail) {
            $this->handleEmploymentChanges($model, $isCreated);
        } elseif ($model instanceof EmployeeFinancialDetail) {
            $this->handleFinancialChanges($model, $isCreated);
        }
    }

    /**
     * Handle changes in employment details (Promotion/Transfer/Status)
     */
    protected function handleEmploymentChanges(EmployeeEmploymentDetail $detail, bool $isCreated): void
    {
        $changes = $isCreated ? $detail->getAttributes() : $detail->getChanges();
        $original = $isCreated ? [] : $detail->getOriginal();

        // 1. Position Change (Promotion/Demotion)
        if (isset($changes['position_id'])) {
            EmployeeHistory::create([
                'tenant_id' => $detail->tenant_id,
                'employee_id' => $detail->employee_id,
                'change_type' => 'promotion', // Can be refined later based on rank
                'effective_date' => now(),
                'previous_value' => ['position_id' => $original['position_id'] ?? null],
                'new_value' => ['position_id' => $changes['position_id']],
                'reason' => 'Position updated',
                'created_by' => Auth::id(),
            ]);
        }

        // 2. Department Change (Transfer)
        if (isset($changes['department_id'])) {
            EmployeeHistory::create([
                'tenant_id' => $detail->tenant_id,
                'employee_id' => $detail->employee_id,
                'change_type' => 'transfer',
                'effective_date' => now(),
                'previous_value' => ['department_id' => $original['department_id'] ?? null],
                'new_value' => ['department_id' => $changes['department_id']],
                'reason' => 'Department transfer',
                'created_by' => Auth::id(),
            ]);
        }

        // 3. Status Change
        if (isset($changes['employment_status'])) {
            EmployeeHistory::create([
                'tenant_id' => $detail->tenant_id,
                'employee_id' => $detail->employee_id,
                'change_type' => 'status_change',
                'effective_date' => now(),
                'previous_value' => ['employment_status' => $original['employment_status'] ?? null],
                'new_value' => ['employment_status' => $changes['employment_status']],
                'reason' => 'Employment status updated',
                'created_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Handle changes in financial details (Salary)
     */
    protected function handleFinancialChanges(EmployeeFinancialDetail $detail, bool $isCreated): void
    {
        $changes = $isCreated ? $detail->getAttributes() : $detail->getChanges();
        $original = $isCreated ? [] : $detail->getOriginal();

        // 1. Salary or Currency Change
        if (isset($changes['current_salary']) || isset($changes['salary_currency'])) {
            EmployeeHistory::create([
                'tenant_id' => $detail->tenant_id,
                'employee_id' => $detail->employee_id,
                'change_type' => 'salary_change',
                'effective_date' => now(),
                'previous_value' => [
                    'current_salary' => $original['current_salary'] ?? null,
                    'salary_currency' => $original['salary_currency'] ?? null,
                ],
                'new_value' => [
                    'current_salary' => $detail->current_salary,
                    'salary_currency' => $detail->salary_currency,
                ],
                'reason' => $isCreated ? 'Initial salary setup' : 'Salary adjustment',
                'created_by' => Auth::id() ?? $detail->created_by ?? 1,
            ]);
        }
    }
}
