<?php

namespace Database\Seeders;

use App\Models\Hris\Department;
use App\Models\Hris\Employee;
use App\Models\Hris\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;
        $adminUser = User::where('email', 'admin@hrms.local')->first();
        $employeeUser = User::where('email', 'john.doe@hrms.local')->first();
        $devDept = Department::where('code', 'IT-DEV')->first();
        $devPosition = Position::where('code', 'DEV-001')->first();

        // Create Employee
        $employee = Employee::create([
            'tenant_id' => $tenantId,
            'user_id' => $employeeUser->id,
            'employee_number' => 'STAFF/2025/001',
            'first_name' => 'John',
            'middle_name' => 'Michael',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-05-15',
            'gender' => 'male',
            'marital_status' => 'single',
            'nationality' => 'Nigerian',
            'is_active' => true,
            'created_by' => $adminUser->id,
        ]);

        // Create Employment Details
        DB::table('employee_employment_details')->insert([
            'tenant_id' => $tenantId,
            'employee_id' => $employee->id,
            'work_email' => 'john.doe@hrms.local',
            'department_id' => $devDept->id,
            'position_id' => $devPosition->id,
            'employment_type' => 'full-time',
            'employment_status' => 'active',
            'hire_date' => '2025-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create Contact Details
        DB::table('employee_contact_details')->insert([
            'tenant_id' => $tenantId,
            'employee_id' => $employee->id,
            'personal_email' => 'john.doe@gmail.com',
            'mobile_phone' => '+234-800-000-0000',
            'preferred_contact_method' => 'mobile',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('Employees seeded successfully!');
    }
}
