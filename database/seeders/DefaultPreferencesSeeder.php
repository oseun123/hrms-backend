<?php

namespace Database\Seeders;

use App\Models\Preference\Preference;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class DefaultPreferencesSeeder extends Seeder
{
    protected ?Tenant $tenant = null;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant;
    }

    /**
     * Run the database seeder.
     * Seeds default preferences for all tenants
     */
    public function run(): void
    {
        $tenants = ($this->tenant && $this->tenant->exists) ? collect([$this->tenant]) : Tenant::all();

        foreach ($tenants as $tenant) {
            // Set default theme color to Geek Blue
            Preference::updateOrCreate(
                ['tenant_id' => $tenant->id, 'user_id' => null, 'category' => 'display', 'key' => 'theme_color'],
                ['value' => 'geekblue']
            );

            // Default Organization Settings
            $orgSettings = [
                'legal_name' => $tenant->name,
                'trading_name' => '',
                'rc_number' => 'RC' . rand(100000, 999999),
                'tin' => rand(10000000, 99999999) . '-0001',
                'industry' => 'Technology',
                'company_size' => '11-50 employees',
                'financial_year_start' => now()->startOfYear()->format('Y-m-d'),
                'description' => 'A leading service provider.',
            ];

            foreach ($orgSettings as $key => $value) {
                Preference::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'user_id' => null,
                        'category' => 'organization',
                        'key' => $key,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }

            // Default Leave Settings
            Preference::updateOrCreate(
                ['tenant_id' => $tenant->id, 'user_id' => null, 'category' => 'leave', 'key' => 'leave_year_start_month'],
                ['value' => 1] // Default to January (calendar year)
            );

            // Default Working Hours (Monday - Friday, 09:00 - 17:00)
            $workingDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            $weekendDays = ['saturday', 'sunday'];

            foreach ($workingDays as $day) {
                Preference::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'user_id' => null, 'category' => 'working_hours', 'key' => "{$day}_enabled"],
                    ['value' => true]
                );
                Preference::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'user_id' => null, 'category' => 'working_hours', 'key' => "{$day}_start"],
                    ['value' => '09:00']
                );
                Preference::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'user_id' => null, 'category' => 'working_hours', 'key' => "{$day}_end"],
                    ['value' => '17:00']
                );
            }

            foreach ($weekendDays as $day) {
                Preference::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'user_id' => null, 'category' => 'working_hours', 'key' => "{$day}_enabled"],
                    ['value' => false]
                );
                Preference::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'user_id' => null, 'category' => 'working_hours', 'key' => "{$day}_start"],
                    ['value' => '09:00']
                );
                Preference::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'user_id' => null, 'category' => 'working_hours', 'key' => "{$day}_end"],
                    ['value' => '17:00']
                );
            }

            $this->command?->info("Default preferences set for tenant: {$tenant->name}");
        }

        $this->command?->info('Default preferences seeded successfully for all tenants.');
    }
}
