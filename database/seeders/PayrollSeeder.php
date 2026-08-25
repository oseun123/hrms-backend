<?php

namespace Database\Seeders;

use App\Models\Payroll\SalaryComponent;
use App\Models\Payroll\TaxScheme;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PayrollSeeder extends Seeder
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
        $tenant = ($this->tenant && $this->tenant->exists) ? $this->tenant : Tenant::first();

        if (!$tenant) {
            return;
        }

        // 1. Create Current Nigeria PAYE Scheme (Pre-2026)
        $currentScheme = TaxScheme::updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Nigeria PAYE (Finance Act 2020)'],
            [
                'description' => 'Current statutory progressive tax with CRA relief.',
                'employee_pension_percentage' => 8.00,
                'employer_pension_percentage' => 10.00,
                'apply_cra' => true,
                'apply_rent_relief' => false,
                'is_active' => true,
                'is_system_defined' => true,
            ]
        );

        $currentBands = [
            ['lower_limit' => 0, 'upper_limit' => 300000, 'rate_percentage' => 7, 'flat_amount' => 0],
            ['lower_limit' => 300000, 'upper_limit' => 600000, 'rate_percentage' => 11, 'flat_amount' => 0],
            ['lower_limit' => 600000, 'upper_limit' => 1100000, 'rate_percentage' => 15, 'flat_amount' => 0],
            ['lower_limit' => 1100000, 'upper_limit' => 1600000, 'rate_percentage' => 19, 'flat_amount' => 0],
            ['lower_limit' => 1600000, 'upper_limit' => 3200000, 'rate_percentage' => 21, 'flat_amount' => 0],
            ['lower_limit' => 3200000, 'upper_limit' => null, 'rate_percentage' => 24, 'flat_amount' => 0],
        ];

        // Clear and re-add bands to ensure correctness
        $currentScheme->bands()->delete();
        foreach ($currentBands as $band) {
            $currentScheme->bands()->create($band);
        }

        // 2. Create NEW Nigeria Tax Act 2025 (Effective Jan 2026)
        $newScheme = TaxScheme::updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Nigeria Tax Act 2025 (Effective 2026)'],
            [
                'description' => 'New statutory progressive tax - CRA abolished, higher 0% threshold.',
                'employee_pension_percentage' => 8.00,
                'employer_pension_percentage' => 10.00,
                'apply_cra' => false,
                'apply_rent_relief' => true,
                'rent_relief_max_amount' => 500000,
                'rent_relief_percentage' => 20,
                'is_active' => true,
                'is_system_defined' => true,
            ]
        );

        $newBands = [
            ['lower_limit' => 0, 'upper_limit' => 800000, 'rate_percentage' => 0, 'flat_amount' => 0],
            ['lower_limit' => 800000, 'upper_limit' => 3000000, 'rate_percentage' => 15, 'flat_amount' => 0],
            ['lower_limit' => 3000000, 'upper_limit' => 12000000, 'rate_percentage' => 18, 'flat_amount' => 0],
            ['lower_limit' => 12000000, 'upper_limit' => 25000000, 'rate_percentage' => 21, 'flat_amount' => 0],
            ['lower_limit' => 25000000, 'upper_limit' => 50000000, 'rate_percentage' => 23, 'flat_amount' => 0],
            ['lower_limit' => 50000000, 'upper_limit' => null, 'rate_percentage' => 25, 'flat_amount' => 0],
        ];

        $newScheme->bands()->delete();
        foreach ($newBands as $band) {
            $newScheme->bands()->create($band);
        }

        // 3. Create Standard Salary Components
        $components = [
            [
                'name' => 'Basic Salary',
                'code' => 'BASIC',
                'type' => 'earning',
                'category' => 'fixed',
                'is_taxable' => true,
                'is_tax_deductible' => false,
                'calculation_type' => 'fixed',
                'amount_value' => 0,
                'is_system_defined' => true,
                'is_pensionable' => true,
            ],
            [
                'name' => 'Housing Allowance',
                'code' => 'HOU',
                'type' => 'earning',
                'category' => 'fixed',
                'is_taxable' => true,
                'is_tax_deductible' => false,
                'calculation_type' => 'fixed',
                'amount_value' => 0,
                'is_system_defined' => true,
                'is_pensionable' => true,
            ],
            [
                'name' => 'Transport Allowance',
                'code' => 'TRA',
                'type' => 'earning',
                'category' => 'fixed',
                'is_taxable' => true,
                'is_tax_deductible' => false,
                'calculation_type' => 'fixed',
                'amount_value' => 0,
                'is_system_defined' => true,
                'is_pensionable' => true,
            ],
            [
                'name' => 'Utility Allowance',
                'code' => 'UTL',
                'type' => 'earning',
                'category' => 'fixed',
                'is_taxable' => true,
                'is_tax_deductible' => false,
                'calculation_type' => 'fixed',
                'amount_value' => 0,
            ],
            [
                'name' => 'Medical Allowance',
                'code' => 'MED',
                'type' => 'earning',
                'category' => 'fixed',
                'is_taxable' => false,
                'is_tax_deductible' => false,
                'calculation_type' => 'fixed',
                'amount_value' => 0,
            ],
        ];

        foreach ($components as $compData) {
            SalaryComponent::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => $compData['code']],
                array_merge($compData, ['is_active' => true])
            );
        }
    }
}
