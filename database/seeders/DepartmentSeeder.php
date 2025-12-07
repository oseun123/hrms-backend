<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\User;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;
        $adminUser = User::where('email', 'admin@hrms.local')->first();

        // Create main departments
        $itDept = Department::create([
            'tenant_id' => $tenantId,
            'code' => 'IT',
            'name' => 'Information Technology',
            'description' => 'IT Department',
            'is_active' => true,
            'created_by' => $adminUser->id,
        ]);

        Department::create([
            'tenant_id' => $tenantId,
            'code' => 'HR',
            'name' => 'Human Resources',
            'description' => 'HR Department',
            'is_active' => true,
            'created_by' => $adminUser->id,
        ]);

        Department::create([
            'tenant_id' => $tenantId,
            'code' => 'FIN',
            'name' => 'Finance',
            'description' => 'Finance Department',
            'is_active' => true,
            'created_by' => $adminUser->id,
        ]);

        // Create sub-department
        Department::create([
            'tenant_id' => $tenantId,
            'parent_id' => $itDept->id,
            'code' => 'IT-DEV',
            'name' => 'Development',
            'description' => 'Software Development Team',
            'is_active' => true,
            'created_by' => $adminUser->id,
        ]);

        $this->command->info('Departments seeded successfully!');
    }
}
