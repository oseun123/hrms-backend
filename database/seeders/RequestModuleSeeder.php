<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Requests\RequestTemplate;
use App\Models\Tenant;

class RequestModuleSeeder extends Seeder
{
    protected ?Tenant $tenant = null;

    /**
     * The constructor allows for targeted seeding of a specific tenant.
     * Note: Laravel's container may inject an empty Tenant instance if run via db:seed.
     */
    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant;
    }

    public function run(): void
    {
        // If a valid tenant was provided (with an ID), seed only that tenant.
        // We check ->exists or ->id to ensure it's not just an empty model injected by the container.
        if ($this->tenant && $this->tenant->exists) {
            $this->seedTemplates($this->tenant);
            return;
        }

        // Otherwise, seed all active tenants in the system.
        $tenants = Tenant::all();
        
        if ($tenants->isEmpty()) {
            $this->command?->warn('No tenants found in the database. Seeding skipped.');
            return;
        }

        /** @var Tenant $tenant */
        foreach ($tenants as $tenant) {
            $this->seedTemplates($tenant);
        }
    }

    /**
     * Seed predefined templates for a specific tenant.
     */
    protected function seedTemplates(Tenant $tenant)
    {
        if (!$tenant || !$tenant->id) {
            return;
        }

        $templates = [
            [
                'name' => 'Cash / Petty Cash Request',
                'description' => 'Request cash advance or petty cash disbursement with line item breakdown.',
                'category' => 'predefined',
                'template_key' => 'cash_request',
                'icon' => 'DollarOutlined',
                'is_active' => true,
            ],
            [
                'name' => 'Expense Reimbursement',
                'description' => 'Submit expense claims with receipt attachments for reimbursement.',
                'category' => 'predefined',
                'template_key' => 'expense_reimbursement',
                'icon' => 'WalletOutlined',
                'is_active' => true,
            ],
            [
                'name' => 'Asset / Equipment Request',
                'description' => 'Request office equipment, IT hardware, or company assets.',
                'category' => 'predefined',
                'template_key' => 'asset_request',
                'icon' => 'LaptopOutlined',
                'is_active' => true,
            ],
            [
                'name' => 'IT / System Access Request',
                'description' => 'Request access to internal systems or software resources.',
                'category' => 'predefined',
                'template_key' => 'it_access_request',
                'icon' => 'KeyOutlined',
                'is_active' => true,
            ],
            [
                'name' => 'Travel & Business Trip Request',
                'description' => 'Request official travel with flight, accommodation, and per diem details.',
                'category' => 'predefined',
                'template_key' => 'travel_request',
                'icon' => 'GlobalOutlined',
                'is_active' => true,
            ],
            [
                'name' => 'Vehicle & Logistics Request',
                'description' => 'Request company vehicle for official assignments, transit, or deliveries.',
                'category' => 'predefined',
                'template_key' => 'vehicle_request',
                'icon' => 'CarOutlined',
                'is_active' => true,
            ],
            [
                'name' => 'Overtime Claim Form',
                'description' => 'Submit overtime hours worked with daily log and task breakdown.',
                'category' => 'predefined',
                'template_key' => 'overtime_claim',
                'icon' => 'FieldTimeOutlined',
                'is_active' => true,
            ],
            [
                'name' => 'Loan / Salary Advance Request',
                'description' => 'Request financial assistance or salary advance with repayment terms.',
                'category' => 'predefined',
                'template_key' => 'loan_advance_request',
                'icon' => 'SafetyCertificateOutlined',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $tpl) {
            RequestTemplate::updateOrCreate(
                ['tenant_id' => $tenant->id, 'template_key' => $tpl['template_key']],
                $tpl
            );
        }
    }
}
