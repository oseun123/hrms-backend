<?php

namespace Database\Seeders;

use App\Models\Leave\LeaveGroup;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class LeaveGroupSeeder extends Seeder
{
    protected ?Tenant $tenant = null;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant;
    }

    /**
     * Run the database seeds.
     * Seeds default leave groups for each tenant.
     */
    public function run(): void
    {
        $tenants = ($this->tenant && $this->tenant->exists) ? collect([$this->tenant]) : Tenant::all();

        $defaultGroups = [
            [
                'name'        => 'All Staff',
                'description' => 'Default leave group for all employees. Applies standard leave entitlements.',
            ],
        ];

        foreach ($tenants as $tenant) {
            foreach ($defaultGroups as $group) {
                LeaveGroup::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name'      => $group['name'],
                    ],
                    [
                        'description' => $group['description'],
                        'is_active'   => true,
                    ]
                );
            }
        }
    }
}
