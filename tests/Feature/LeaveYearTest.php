<?php

namespace Tests\Feature;

use App\Models\Hris\Employee;
use App\Models\Leave\LeavePolicy;
use App\Models\Leave\LeaveRequest;
use App\Models\Leave\LeaveType;
use App\Models\Leave\LeaveYearEndProcessing;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioningService;
use App\Services\LeaveYearService;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

class LeaveYearTest extends TestCase
{
    use DatabaseTransactions;

    public function test_tenant_provisioning_seeds_previous_leave_year_processing()
    {
        Carbon::setTestNow('2026-06-12 12:00:00');

        $provisioningService = app(TenantProvisioningService::class);

        $suffix = Str::random(6);
        $data = [
            'name' => 'New Tenant ' . $suffix,
            'slug' => 'new-tenant-' . strtolower($suffix),
            'contact_email' => 'admin@' . strtolower($suffix) . '.com',
            'admin_name' => 'John Admin',
            'admin_email' => 'admin@' . strtolower($suffix) . '.com',
            'admin_password' => 'password123',
        ];

        $result = $provisioningService->provision($data);

        $tenant = $result['tenant'];

        $this->assertDatabaseHas('leave_year_end_processing', [
            'tenant_id' => $tenant->id,
            'from_year' => 2025,
            'to_year' => 2026,
        ]);

        $leaveYearService = app(LeaveYearService::class);
        $this->assertEquals(2026, $leaveYearService->getCurrentActiveLeaveYear($tenant->id));
    }

    public function test_cannot_book_leave_in_unprocessed_future_years()
    {
        Carbon::setTestNow('2026-06-12 12:00:00');

        $suffix = Str::random(6);
        $tenant = Tenant::create([
            'name' => 'Company ' . $suffix,
            'slug' => 'company-' . strtolower($suffix)
        ]);
        
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'employee_number' => 'EMP' . Str::random(4),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $leaveType = LeaveType::create([
            'tenant_id' => $tenant->id,
            'name' => 'Casual Leave ' . $suffix,
            'code' => 'CL' . strtoupper($suffix),
        ]);

        $leaveGroup = \App\Models\Leave\LeaveGroup::create([
            'tenant_id' => $tenant->id,
            'name' => 'Standard Group ' . $suffix,
        ]);

        $policy = LeavePolicy::create([
            'tenant_id' => $tenant->id,
            'leave_group_id' => $leaveGroup->id,
            'leave_type_id' => $leaveType->id,
            'entitlement_days' => 20,
            'is_active' => true,
        ]);

        $department = \App\Models\Hris\Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'IT ' . $suffix,
            'code' => 'IT-' . strtoupper($suffix),
            'created_by' => $user->id,
        ]);

        $branch = \App\Models\Hris\Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Main ' . $suffix,
        ]);

        $position = \App\Models\Hris\Position::create([
            'tenant_id' => $tenant->id,
            'title' => 'Developer ' . $suffix,
            'name' => 'Developer ' . $suffix,
            'code' => 'DEV-' . strtoupper($suffix),
            'department_id' => $department->id,
            'created_by' => $user->id,
        ]);

        \App\Models\Hris\EmployeeEmploymentDetail::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'leave_group_id' => $leaveGroup->id,
            'department_id' => $department->id,
            'branch_id' => $branch->id,
            'position_id' => $position->id,
            'hire_date' => '2026-01-01',
            'work_email' => 'employee@' . strtolower($suffix) . '.com',
        ]);

        // Scenario A: Seed processed 2025 -> 2026. Current active year is 2026.
        LeaveYearEndProcessing::create([
            'tenant_id' => $tenant->id,
            'from_year' => 2025,
            'to_year' => 2026,
            'processed_at' => now(),
            'processed_by' => $user->id,
            'employees_processed' => 1,
            'summary' => [],
        ]);

        // Attempting to book in 2027 (which is current active year + 1, and rollover not done)
        $response = $this->postJson('/api/leave/requests', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2027-01-10',
            'end_date' => '2027-01-15',
            'reason' => 'Future vacation',
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment([
            'success' => false,
            'message' => 'Cannot book leave for 2027 as the year-end rollover for 2026 has not been processed yet.',
        ]);
    }
}
