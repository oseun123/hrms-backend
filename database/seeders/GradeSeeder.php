<?php

namespace Database\Seeders;

use App\Models\Hris\Grade;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
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

            Grade::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'G1'],
                [
                    'name' => 'Grade 1',
                    'description' => 'Entry Level Grade',
                    'min_salary' => 30000,
                    'max_salary' => 50000,
                    'rank' => 1,
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ]
            );

            Grade::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'G2'],
                [
                    'name' => 'Grade 2',
                    'description' => 'Mid Level Grade',
                    'min_salary' => 50000,
                    'max_salary' => 80000,
                    'rank' => 2,
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ]
            );

            Grade::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'G3'],
                [
                    'name' => 'Grade 3',
                    'description' => 'Senior Level Grade',
                    'min_salary' => 80000,
                    'max_salary' => 120000,
                    'rank' => 3,
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ]
            );
        }
    }
}
