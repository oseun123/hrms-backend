<?php

namespace Database\Seeders;

use App\Models\Hris\Department;
use App\Models\Hris\Grade;
use App\Models\Hris\Level;
use App\Models\Hris\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
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
        $tenants = $this->tenant ? collect([$this->tenant]) : Tenant::all();

        foreach ($tenants as $tenant) {
            $adminUser = $this->adminUser ?? User::where('tenant_id', $tenant->id)->first() ?? User::where('email', 'admin@hrms.local')->first();

            if (!$adminUser) {
                continue;
            }

            $devDept = Department::where('tenant_id', $tenant->id)->where('code', 'IT-DEV')->first();
            $hrDept = Department::where('tenant_id', $tenant->id)->where('code', 'HR')->first();

            // Fallbacks for critical missing relationships
            if (!$devDept || !$hrDept) {
                continue;
            }

            $midLevel = Level::where('tenant_id', $tenant->id)->where('code', 'MID')->first();
            $seniorLevel = Level::where('tenant_id', $tenant->id)->where('code', 'SR')->first();
            $grade2 = Grade::where('tenant_id', $tenant->id)->where('code', 'G2')->first();
            $grade3 = Grade::where('tenant_id', $tenant->id)->where('code', 'G3')->first();

            Position::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'DEV-001'],
                [
                    'department_id' => $devDept->id,
                    'level_id' => $midLevel?->id,
                    'grade_id' => $grade2?->id,
                    'title' => 'Software Developer',
                    'description' => 'Full Stack Developer',
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ]
            );

            Position::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'HR-001'],
                [
                    'department_id' => $hrDept->id,
                    'level_id' => $seniorLevel?->id,
                    'grade_id' => $grade3?->id,
                    'title' => 'HR Manager',
                    'description' => 'Human Resources Manager',
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ]
            );
        }
    }
}
