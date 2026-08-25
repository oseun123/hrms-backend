<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTypeSeeder extends Seeder
{
    protected ?Tenant $tenant = null;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant;
    }

    public function run(): void
    {
        $tenants = ($this->tenant && $this->tenant->exists) ? collect([$this->tenant]) : Tenant::all();

        $documentTypes = [
            [
                'code' => 'PASSPORT',
                'name' => 'Passport',
                'description' => 'International passport document',
                'is_required' => false,
                'requires_expiry' => true,
            ],
            [
                'code' => 'NATIONAL_ID',
                'name' => 'National ID',
                'description' => 'National identification card',
                'is_required' => true,
                'requires_expiry' => true,
            ],
            [
                'code' => 'DRIVERS_LICENSE',
                'name' => "Driver's License",
                'description' => 'Valid driving license',
                'is_required' => false,
                'requires_expiry' => true,
            ],
            [
                'code' => 'BIRTH_CERT',
                'name' => 'Birth Certificate',
                'description' => 'Official birth certificate',
                'is_required' => false,
                'requires_expiry' => false,
            ],
            [
                'code' => 'ACADEMIC_CERT',
                'name' => 'Academic Certificate',
                'description' => 'Educational qualification certificates',
                'is_required' => false,
                'requires_expiry' => false,
            ],
            [
                'code' => 'PROF_CERT',
                'name' => 'Professional Certification',
                'description' => 'Professional certifications and licenses',
                'is_required' => false,
                'requires_expiry' => true,
            ],
            [
                'code' => 'EMPLOYMENT_CONTRACT',
                'name' => 'Employment Contract',
                'description' => 'Signed employment contract',
                'is_required' => true,
                'requires_expiry' => false,
            ],
            [
                'code' => 'TAX_DOCS',
                'name' => 'Tax Documents',
                'description' => 'Tax identification and related documents',
                'is_required' => false,
                'requires_expiry' => false,
            ],
            [
                'code' => 'MEDICAL_CERT',
                'name' => 'Medical Certificate',
                'description' => 'Medical fitness certificate',
                'is_required' => false,
                'requires_expiry' => true,
            ],
            [
                'code' => 'POLICE_CLEARANCE',
                'name' => 'Police Clearance',
                'description' => 'Police background check certificate',
                'is_required' => false,
                'requires_expiry' => true,
            ],
        ];

        foreach ($tenants as $tenant) {
            foreach ($documentTypes as $type) {
                DB::table('document_types')->updateOrInsert(
                    ['tenant_id' => $tenant->id, 'code' => $type['code']],
                    array_merge($type, [
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }
    }
}
