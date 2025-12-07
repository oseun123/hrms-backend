<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $documentTypes = [
            [
                'tenant_id' => 1,
                'code' => 'PASSPORT',
                'name' => 'Passport',
                'description' => 'International passport document',
                'is_required' => false,
                'requires_expiry' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'code' => 'NATIONAL_ID',
                'name' => 'National ID',
                'description' => 'National identification card',
                'is_required' => true,
                'requires_expiry' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'code' => 'DRIVERS_LICENSE',
                'name' => "Driver's License",
                'description' => 'Valid driving license',
                'is_required' => false,
                'requires_expiry' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'code' => 'BIRTH_CERT',
                'name' => 'Birth Certificate',
                'description' => 'Official birth certificate',
                'is_required' => false,
                'requires_expiry' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'code' => 'ACADEMIC_CERT',
                'name' => 'Academic Certificate',
                'description' => 'Educational qualification certificates',
                'is_required' => false,
                'requires_expiry' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'code' => 'PROF_CERT',
                'name' => 'Professional Certification',
                'description' => 'Professional certifications and licenses',
                'is_required' => false,
                'requires_expiry' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'code' => 'EMPLOYMENT_CONTRACT',
                'name' => 'Employment Contract',
                'description' => 'Signed employment contract',
                'is_required' => true,
                'requires_expiry' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'code' => 'TAX_DOCS',
                'name' => 'Tax Documents',
                'description' => 'Tax identification and related documents',
                'is_required' => false,
                'requires_expiry' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'code' => 'MEDICAL_CERT',
                'name' => 'Medical Certificate',
                'description' => 'Medical fitness certificate',
                'is_required' => false,
                'requires_expiry' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'code' => 'POLICE_CLEARANCE',
                'name' => 'Police Clearance',
                'description' => 'Police background check certificate',
                'is_required' => false,
                'requires_expiry' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('document_types')->insert($documentTypes);
    }
}
