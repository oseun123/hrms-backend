<?php

namespace Database\Seeders;

use App\Models\Hris\Employee;
use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeavePolicy;
use App\Models\Leave\LeaveYearEndProcessing;
use App\Services\LeaveYearService;
use App\Services\Leave\LeaveBalanceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leaveBalanceService = app(LeaveBalanceService::class);
        $leaveYearService = app(LeaveYearService::class);

        $employees = Employee::with('employmentDetails')->get();
        $currentYear = $leaveYearService->getCurrentLeaveYear();
        $previousYear = $currentYear - 1;

        foreach ($employees as $employee) {
            $leaveGroupId = $employee->employmentDetails->leave_group_id;
            if (!$leaveGroupId) continue;

            $policies = LeavePolicy::where('leave_group_id', $leaveGroupId)
                ->where('is_active', true)
                ->get();

            foreach ($policies as $policy) {
                // Initialize current year balance
                $leaveBalanceService->accrue($employee, $policy, $currentYear);
            }
        }

        // Simulating that the previous year has already been processed for rollover
        $tenants = \App\Models\Tenant::all();
        $adminUser = \App\Models\User::where('email', 'admin@hrms.local')->first();

        foreach ($tenants as $tenant) {
            LeaveYearEndProcessing::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'from_year' => $previousYear,
                ],
                [
                    'to_year' => $currentYear,
                    'processed_at' => now()->subMonths(1), // Simulating it was done a month ago
                    'processed_by' => $adminUser ? $adminUser->id : 1,
                    'employees_processed' => $employees->where('tenant_id', $tenant->id)->count(),
                    'summary' => ['seeded' => true],
                ]
            );
        }

        $this->command?->info('Leave balances initialized and previous year rollover recorded.');
    }
}
