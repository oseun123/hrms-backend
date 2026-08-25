<?php

namespace Database\Seeders;

use App\Models\Hris\EmployeeNumberFormat;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeNumberFormatSeeder extends Seeder
{
    protected ?Tenant $tenant = null;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all tenants
        $tenants = ($this->tenant && $this->tenant->exists) ? collect([$this->tenant]) : \App\Models\Tenant::all();

        foreach ($tenants as $tenant) {
            // Get any user for this tenant to be the creator
            $adminUser = User::where('tenant_id', $tenant->id)->first();

            if ($adminUser) {
                // Create a default format for each tenant
                EmployeeNumberFormat::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'format_name' => 'Default Format',
                    ],
                    [
                        'prefix' => 'EMP',
                        'include_year' => true,
                        'year_format' => 'YYYY',
                        'include_month' => false,
                        'month_format' => null,
                        'separator' => '-',
                        'sequence_length' => 4,
                        'current_sequence' => 0,
                        'reset_sequence' => 'yearly',
                        'sample_format' => 'EMP-YYYY-0000',
                        'is_active' => true,
                        'is_default' => true,
                        'created_by' => $adminUser->id,
                    ]
                );
            }
        }
    }
}
