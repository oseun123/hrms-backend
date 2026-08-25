<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Hris\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    protected ?Tenant $tenant;
    protected ?User $adminUser;

    public function __construct(?Tenant $tenant = null, ?User $adminUser = null)
    {
        $this->tenant = $tenant;
        $this->adminUser = $adminUser;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = ($this->tenant && $this->tenant->exists) ? collect([$this->tenant]) : Tenant::all();

        foreach ($tenants as $tenant) {
            $adminUser = ($this->adminUser && $this->adminUser->exists) ? $this->adminUser : (User::where('tenant_id', $tenant->id)->first() ?? User::where('email', 'admin@hrms.local')->first());

            Branch::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'code' => 'HQ',
                ],
                [
                    'name' => 'Headquarters',
                    'description' => 'Default company headquarters',
                    'is_active' => true,
                    'is_default' => true,
                    'created_by' => $adminUser?->id,
                ]
            );
        }
    }
}
