<?php

namespace Database\Seeders;

use App\Models\Hris\Department;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    protected ?Tenant $tenant = null;
    protected ?User $adminUser = null;

    public function __construct(?Tenant $tenant = null, ?User $adminUser = null)
    {
        $this->tenant = $tenant;
        $this->adminUser = $adminUser;
    }

    public function run(): void
    {
        $tenants = ($this->tenant && $this->tenant->exists) ? collect([$this->tenant]) : Tenant::all();

        foreach ($tenants as $tenant) {
            $adminUser = ($this->adminUser && $this->adminUser->exists) ? $this->adminUser : (User::where('tenant_id', $tenant->id)->first() ?? User::where('email', 'admin@hrms.local')->first());

            if (!$adminUser) {
                continue;
            }

            $itDept = Department::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'IT'],
                [
                    'name' => 'Information Technology',
                    'description' => 'IT Department',
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ]
            );

            Department::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'HR'],
                [
                    'name' => 'Human Resources',
                    'description' => 'HR Department',
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ]
            );

            Department::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'FIN'],
                [
                    'name' => 'Finance',
                    'description' => 'Finance Department',
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ]
            );

            // Create sub-department
            Department::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'IT-DEV'],
                [
                    'parent_id' => $itDept->id,
                    'name' => 'Development',
                    'description' => 'Software Development Team',
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ]
            );
        }
    }
}
