<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = [
            [
                'name' => 'Default Tenant',
                'slug' => 'default',
                'domain' => 'default.localhost',
                'is_active' => true,
            ],
            [
                'name' => 'Acme Corporation',
                'slug' => 'acme',
                'domain' => 'acme.localhost',
                'is_active' => true,
            ],
        ];

        foreach ($tenants as $tenantData) {
            Tenant::updateOrCreate(
                ['slug' => $tenantData['slug']],
                $tenantData
            );
        }

        $this->command->info('Tenants seeded successfully!');
    }
}
