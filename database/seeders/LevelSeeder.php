<?php

namespace Database\Seeders;

use App\Models\Hris\Level;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
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

            Level::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'JR'],
                [
                    'name' => 'Junior',
                    'description' => 'Junior Level',
                    'rank' => 1,
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ]
            );

            Level::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'MID'],
                [
                    'name' => 'Mid-Level',
                    'description' => 'Mid-Level',
                    'rank' => 2,
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ]
            );

            Level::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'SR'],
                [
                    'name' => 'Senior',
                    'description' => 'Senior Level',
                    'rank' => 3,
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ]
            );

            Level::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'LEAD'],
                [
                    'name' => 'Lead',
                    'description' => 'Team Lead',
                    'rank' => 4,
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ]
            );
        }
    }
}
