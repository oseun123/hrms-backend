<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\Preference\Preference;

class DefaultSecurityPoliciesSeeder extends Seeder
{
    protected ?Tenant $tenant = null;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant;
    }

    /**
     * Seed default security policies for all tenants.
     * These defaults will be used if no custom policies are set.
     */
    public function run(): void
    {
        $tenants = $this->tenant ? collect([$this->tenant]) : Tenant::all();

        $defaultPolicies = [
            [
                'category' => 'security_policy',
                'key' => 'enforce_2fa',
                'value' => false,
            ],
            [
                'category' => 'security_policy',
                'key' => 'session_timeout',
                'value' => 15, // 15 minutes
            ],
            [
                'category' => 'security_policy',
                'key' => 'password_expiry_days',
                'value' => 0, // 0 = disabled
            ],
            [
                'category' => 'security_policy',
                'key' => 'min_password_length',
                'value' => 8,
            ],
        ];

        foreach ($tenants as $tenant) {
            foreach ($defaultPolicies as $policy) {
                // Only create if it doesn't exist (don't override existing settings)
                Preference::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'user_id' => null,
                        'category' => $policy['category'],
                        'key' => $policy['key'],
                    ],
                    [
                        'value' => $policy['value'],
                    ]
                );
            }

            $this->command?->info("Default security policies seeded for tenant: {$tenant->name}");
        }

        $this->command?->info('Default security policies seeded successfully!');
    }
}
