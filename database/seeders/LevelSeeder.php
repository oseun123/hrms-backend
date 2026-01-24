<?php

namespace Database\Seeders;

use App\Models\Hris\Level;
use App\Models\User;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;
        $adminUser = User::where('email', 'admin@hrms.local')->first();

        Level::create([
            'tenant_id' => $tenantId,
            'code' => 'JR',
            'name' => 'Junior',
            'description' => 'Junior Level',
            'rank' => 1,
            'is_active' => true,
            'created_by' => $adminUser->id,
        ]);

        Level::create([
            'tenant_id' => $tenantId,
            'code' => 'MID',
            'name' => 'Mid-Level',
            'description' => 'Mid-Level',
            'rank' => 2,
            'is_active' => true,
            'created_by' => $adminUser->id,
        ]);

        Level::create([
            'tenant_id' => $tenantId,
            'code' => 'SR',
            'name' => 'Senior',
            'description' => 'Senior Level',
            'rank' => 3,
            'is_active' => true,
            'created_by' => $adminUser->id,
        ]);

        Level::create([
            'tenant_id' => $tenantId,
            'code' => 'LEAD',
            'name' => 'Lead',
            'description' => 'Team Lead',
            'rank' => 4,
            'is_active' => true,
            'created_by' => $adminUser->id,
        ]);

        $this->command->info('Levels seeded successfully!');
    }
}
