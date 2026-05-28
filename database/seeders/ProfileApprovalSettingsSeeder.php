<?php

namespace Database\Seeders;

use App\Models\Preference\ProfileApprovalSetting;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ProfileApprovalSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // All approvable sections
        $sections = [
            'contact_details',
            'addresses',
            'emergency_contacts',
            'education',
            'skills',
            'work_experience',
            'certifications',
            'dependents',
            'financial',
            'medical',
            'documents',
        ];

        // Get all tenants
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            foreach ($sections as $section) {
                ProfileApprovalSetting::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'section' => $section,
                    ],
                    [
                        'requires_approval' => false, // Default: no approval required
                    ]
                );
            }
        }

        $this->command?->info('Profile approval settings seeded successfully for all tenants.');
    }
}
