<?php

namespace Database\Seeders;

use App\Models\Leave\LeaveGroup;
use App\Models\Leave\LeavePolicy;
use App\Models\Leave\LeaveType;
use App\Models\Leave\LeaveWorkflow;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class LeavePolicySeeder extends Seeder
{
    protected ?Tenant $tenant = null;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant;
    }

    /**
     * Run the database seeds.
     * Creates default leave policies by linking each seeded leave type
     * to the "All Staff" leave group with sensible entitlements.
     */
    public function run(): void
    {
        $tenants = $this->tenant ? collect([$this->tenant]) : Tenant::all();

        // Default entitlements (days) per leave type code
        $entitlements = [
            'AL'  => 21,  // Annual Leave
            'SL'  => 10,  // Sick Leave
            'CL'  => 5,   // Casual Leave
            'ML'  => 90,  // Maternity Leave
            'PL'  => 5,   // Paternity Leave
            'BL'  => 3,   // Bereavement Leave
            'STL' => 10,  // Study Leave
        ];

        foreach ($tenants as $tenant) {
            // Fetch the "All Staff" leave group for this tenant
            $allStaffGroup = LeaveGroup::where('tenant_id', $tenant->id)
                ->where('name', 'All Staff')
                ->first();

            if (! $allStaffGroup) {
                continue; // Group must exist first (run LeaveGroupSeeder before this)
            }

            // Fetch the default workflow (if available)
            $workflow = LeaveWorkflow::where('tenant_id', $tenant->id)->first();

            // Get all seeded leave types for this tenant
            $leaveTypes = LeaveType::where('tenant_id', $tenant->id)->get();

            foreach ($leaveTypes as $leaveType) {
                $days = $entitlements[$leaveType->code] ?? 10; // fallback: 10 days

                LeavePolicy::updateOrCreate(
                    [
                        'tenant_id'      => $tenant->id,
                        'leave_type_id'  => $leaveType->id,
                        'leave_group_id' => $allStaffGroup->id,
                    ],
                    [
                        'entitlement_days'        => $days,
                        'accrual_frequency'       => 'yearly',
                        'is_prorated'             => true,
                        'allow_carry_forward'     => in_array($leaveType->code, ['AL']),
                        'max_carry_forward_days'  => in_array($leaveType->code, ['AL']) ? 5 : 0,
                        'allow_encashment'        => false,
                        'max_encashment_days'     => 0,
                        'allow_negative_balance'  => false,
                        'max_negative_days'       => 0,
                        'include_public_holidays' => false,
                        'include_weekends'        => false,
                        'notice_period_days'      => 1,
                        'min_service_days'        => 0,
                        'max_consecutive_days'    => $days,
                        'approval_stages'         => 2,
                        'requires_hr_approval'    => true,
                        'leave_workflow_id'       => $workflow?->id,
                        'is_active'               => true,
                    ]
                );
            }
        }
    }
}
