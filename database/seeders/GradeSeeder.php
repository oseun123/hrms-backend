<?php

namespace Database\Seeders;

use App\Models\Hris\Grade;
use App\Models\User;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;
        $adminUser = User::where('email', 'admin@hrms.local')->first();

        Grade::create([
            'tenant_id' => $tenantId,
            'code' => 'G1',
            'name' => 'Grade 1',
            'description' => 'Entry Level Grade',
            'min_salary' => 30000,
            'max_salary' => 50000,
            'rank' => 1,
            'is_active' => true,
            'created_by' => $adminUser->id,
        ]);

        Grade::create([
            'tenant_id' => $tenantId,
            'code' => 'G2',
            'name' => 'Grade 2',
            'description' => 'Mid Level Grade',
            'min_salary' => 50000,
            'max_salary' => 80000,
            'rank' => 2,
            'is_active' => true,
            'created_by' => $adminUser->id,
        ]);

        Grade::create([
            'tenant_id' => $tenantId,
            'code' => 'G3',
            'name' => 'Grade 3',
            'description' => 'Senior Level Grade',
            'min_salary' => 80000,
            'max_salary' => 120000,
            'rank' => 3,
            'is_active' => true,
            'created_by' => $adminUser->id,
        ]);

        $this->command->info('Grades seeded successfully!');
    }
}
