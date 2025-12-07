<?php

namespace App\Observers;

use App\Models\Employee;
use App\Services\ProfileCompletenessService;

class EmployeeObserver
{
    protected $completenessService;

    public function __construct(ProfileCompletenessService $completenessService)
    {
        $this->completenessService = $completenessService;
    }

    /**
     * Handle the Employee "created" event.
     */
    public function created(Employee $employee): void
    {
        // Calculate initial profile completeness
        $this->completenessService->calculate($employee);
    }

    /**
     * Handle the Employee "updated" event.
     */
    public function updated(Employee $employee): void
    {
        // Recalculate profile completeness
        $this->completenessService->calculate($employee);
    }

    /**
     * Handle the Employee "deleted" event.
     */
    public function deleted(Employee $employee): void
    {
        // Profile completeness will be cascade deleted via foreign key
    }
}
