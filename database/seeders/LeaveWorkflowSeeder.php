<?php

namespace Database\Seeders;

use App\Models\Leave\LeaveWorkflow;
use App\Models\Leave\LeavePolicy;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class LeaveWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // 1. Create the default "Manual (Legacy)" workflow
            $workflow = LeaveWorkflow::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'Manual (Legacy)'
                ],
                [
                    'description' => 'System default 2-level approval workflow: Direct Manager followed by HR.',
                    'is_active' => true
                ]
            );

            // 2. Define the levels
            // Level 1: Direct Manager
            $workflow->levels()->updateOrCreate(
                ['level' => 1],
                [
                    'tenant_id' => $tenant->id,
                    'approver_type' => 'manager',
                    'approver_id' => null
                ]
            );

            // Level 2: HR
            $workflow->levels()->updateOrCreate(
                ['level' => 2],
                [
                    'tenant_id' => $tenant->id,
                    'approver_type' => 'hr',
                    'approver_id' => null
                ]
            );

            // 3. Assign this workflow to all existing policies that don't have one
            LeavePolicy::where('tenant_id', $tenant->id)
                ->whereNull('leave_workflow_id')
                ->update(['leave_workflow_id' => $workflow->id]);
        }
    }
}
