<?php

namespace App\Services;

use App\Models\Hris\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantProvisioningService
{
    /**
     * Provision a new tenant with an admin user, employee record, and default roles.
     *
     * @param array $data
     * @return array
     */
    public function provision(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // 1. Create Tenant
            $tenant = Tenant::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'contact_email' => $data['contact_email'] ?? null,
                'plan' => $data['plan'] ?? 'starter',
                'max_users' => $data['max_users'] ?? null,
                'trial_ends_at' => $data['trial_ends_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => true,
            ]);

            // 2. Create Default Roles and Permissions
            $this->createDefaultRoles($tenant);

            // 3. Create Default Admin User
            $user = User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'tenant_id' => $tenant->id,
                'email_verified_at' => now(),
            ]);

            // 4. Assign Admin Role
            $adminRole = Role::where('tenant_id', $tenant->id)->where('slug', 'admin')->first();
            if ($adminRole) {
                $user->roles()->attach($adminRole->id);
            }

            // 5. Run Tenant-Specific Seeders
            $this->runTenantSeeders($tenant, $user);

            // 6. Get Seeded Resources for Employee Creation
            $department = \App\Models\Hris\Department::where('tenant_id', $tenant->id)->where('code', 'IT')->first();
            $position = \App\Models\Hris\Position::where('tenant_id', $tenant->id)->where('code', 'DEV-001')->first();

            // 7. Create Employee Record
            $names = explode(' ', $data['admin_name'], 2);
            $firstName = $names[0];
            $lastName = $names[1] ?? 'Admin';

            $employee = Employee::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'employee_number' => 'ADM/' . strtoupper(Str::random(4)),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'is_active' => true,
                'created_by' => $user->id,
            ]);

            // 8. Create Employment Details
            $defaultBranch = \App\Models\Hris\Branch::where('tenant_id', $tenant->id)->where('is_default', true)->first();
            $defaultLeaveGroup = \App\Models\Leave\LeaveGroup::where('tenant_id', $tenant->id)->first();

            \App\Models\Hris\EmployeeEmploymentDetail::withoutEvents(function () use ($tenant, $employee, $department, $position, $defaultBranch, $defaultLeaveGroup, $data) {
                return \App\Models\Hris\EmployeeEmploymentDetail::create([
                    'tenant_id' => $tenant->id,
                    'employee_id' => $employee->id,
                    'department_id' => $department?->id,
                    'branch_id' => $defaultBranch?->id,
                    'position_id' => $position?->id,
                    'leave_group_id' => $defaultLeaveGroup?->id,
                    'work_email' => $data['admin_email'],
                    'employment_type' => 'full-time',
                    'employment_status' => 'active',
                    'hire_date' => now()->format('Y-m-d'),
                ]);
            });

            // Seed initial LeaveYearEndProcessing record for the previous year to make current year active
            $leaveYearService = app(\App\Services\LeaveYearService::class);
            $currentYear = $leaveYearService->getCurrentLeaveYear($tenant->id);
            $previousYear = $currentYear - 1;

            \App\Models\Leave\LeaveYearEndProcessing::create([
                'tenant_id' => $tenant->id,
                'from_year' => $previousYear,
                'to_year' => $currentYear,
                'processed_at' => now(),
                'processed_by' => $user->id,
                'employees_processed' => 1,
                'summary' => ['provisioned' => true],
            ]);

            return [
                'tenant' => $tenant,
                'user' => $user,
                'employee' => $employee,
            ];
        });
    }

    /**
     * Create default roles and attach permissions.
     */
    protected function createDefaultRoles(Tenant $tenant)
    {
        // Re-using the logic from PermissionSeeder
        $seeder = new PermissionSeeder();
        $seeder->createDefaultRoles($tenant);
    }

    /**
     * Run all necessary seeders for a new tenant.
     */
    protected function runTenantSeeders(Tenant $tenant, User $adminUser)
    {
        // 1. Preferences & Security
        (new \Database\Seeders\DefaultPreferencesSeeder($tenant))->run();
        (new \Database\Seeders\ProfileApprovalSettingsSeeder($tenant))->run();
        (new \Database\Seeders\DefaultSecurityPoliciesSeeder($tenant))->run();
        (new \Database\Seeders\EmployeeNumberFormatSeeder($tenant))->run();

        // 2. HR Infrastructure (Order matters for foreign keys)
        (new \Database\Seeders\DepartmentSeeder($tenant, $adminUser))->run();
        (new \Database\Seeders\BranchSeeder($tenant, $adminUser))->run();
        (new \Database\Seeders\LevelSeeder($tenant, $adminUser))->run();
        (new \Database\Seeders\GradeSeeder($tenant, $adminUser))->run();
        (new \Database\Seeders\PositionSeeder($tenant, $adminUser))->run();

        // 3. Operational Data
        (new \Database\Seeders\PublicHolidaySeeder($tenant))->run();
        (new \Database\Seeders\SkillSeeder($tenant))->run();
        (new \Database\Seeders\LeaveTypeSeeder($tenant))->run();
        (new \Database\Seeders\DocumentTypeSeeder($tenant))->run();
        (new \Database\Seeders\LeaveGroupSeeder($tenant))->run();

        // 4. Module Specific
        (new \Database\Seeders\PerformanceDefaultsSeeder($tenant))->run();
        (new \Database\Seeders\PayrollSeeder($tenant))->run();
        (new \Database\Seeders\LeaveWorkflowSeeder($tenant))->run();
        (new \Database\Seeders\LeavePolicySeeder($tenant))->run();
        (new \Database\Seeders\AttendanceDefaultsSeeder($tenant))->run();
    }
}
