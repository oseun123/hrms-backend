<?php

namespace App\Services;

use App\Models\Hris\Employee;
use App\Models\Performance\EmployeeDeliverable;
use App\Models\Performance\EmployeeDeliverableDetail;
use App\Models\Performance\PerformanceGoal;
use Illuminate\Support\Facades\DB;

class DeliverableActivationService
{
    /**
     * Check if deliverables can be activated (weightage must be 100%)
     *
     * @param int $employeeId
     * @param int $tenantId
     * @param array $deliverableIds
     * @return array ['canActivate' => bool, 'totalWeightage' => float, 'message' => string]
     */
    public function canActivateDeliverables($employeeId, $tenantId, array $deliverableIds = [])
    {
        // Get all inactive deliverables or specific ones
        $query = EmployeeDeliverable::where('employee_id', $employeeId)
            ->where('tenant_id', $tenantId);

        if (!empty($deliverableIds)) {
            $query->whereIn('id', $deliverableIds);
        } else {
            $query->where('is_active', false);
        }

        $deliverables = $query->with('details.measureTarget')->get();

        // Also include already active deliverables in total calculation
        $activeDeliverables = EmployeeDeliverable::where('employee_id', $employeeId)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('details.measureTarget')
            ->get();

        $totalWeightage = 0;

        // Calculate weightage from deliverables to be activated
        foreach ($deliverables as $deliverable) {
            foreach ($deliverable->details as $detail) {
                $weightage = $detail->custom_weightage ?? $detail->measureTarget->weightage ?? 0;
                $totalWeightage += $weightage;
            }
        }

        // Add weightage from already active deliverables
        foreach ($activeDeliverables as $deliverable) {
            foreach ($deliverable->details as $detail) {
                $weightage = $detail->custom_weightage ?? $detail->measureTarget->weightage ?? 0;
                $totalWeightage += $weightage;
            }
        }

        $canActivate = abs($totalWeightage - 100) < 0.01; // Allow small floating point differences

        return [
            'canActivate' => $canActivate,
            'totalWeightage' => round($totalWeightage, 2),
            'message' => $canActivate
                ? 'Deliverables can be activated'
                : "Total weightage must be 100%. Current total: {$totalWeightage}%"
        ];
    }

    /**
     * Activate deliverables for an employee
     *
     * @param int $employeeId
     * @param int $tenantId
     * @param array $deliverableIds
     * @param int $activatedBy
     * @return array ['success' => bool, 'message' => string, 'activated' => int]
     */
    public function activateDeliverables($employeeId, $tenantId, array $deliverableIds, $activatedBy)
    {
        $validation = $this->canActivateDeliverables($employeeId, $tenantId, $deliverableIds);

        if (!$validation['canActivate']) {
            return [
                'success' => false,
                'message' => $validation['message'],
                'activated' => 0,
            ];
        }

        DB::beginTransaction();
        try {
            $updated = EmployeeDeliverable::where('employee_id', $employeeId)
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $deliverableIds)
                ->update([
                    'is_active' => true,
                    'activated_at' => now(),
                    'activated_by' => $activatedBy,
                ]);

            DB::commit();

            return [
                'success' => true,
                'message' => "{$updated} deliverable(s) activated successfully",
                'activated' => $updated,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to activate deliverables: ' . $e->getMessage(),
                'activated' => 0,
            ];
        }
    }

    /**
     * Get team members for a manager
     *
     * @param int $managerId (employee_id)
     * @param int $tenantId
     * @return \Illuminate\Support\Collection
     */
    public function getTeamMembers($managerId, $tenantId)
    {
        $manager = Employee::where('id', $managerId)->where('tenant_id', $tenantId)->first();
        if (!$manager || !$manager->employmentDetails) {
            return collect();
        }

        $departmentId = $manager->employmentDetails->department_id;

        return Employee::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereHas('employmentDetails', function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })
            ->with(['employmentDetails.department', 'employmentDetails.position'])
            ->get();
    }

    /**
     * Assign goal to employee (creates inactive deliverable)
     *
     * @param int $employeeId
     * @param int $goalId
     * @param int $tenantId
     * @param int $assignedBy
     * @param array $measureTargetIds Optional specific measures to assign
     * @return EmployeeDeliverable
     */
    public function assignGoalToEmployee($employeeId, $goalId, $tenantId, $assignedBy, array $measureTargetIds = [])
    {
        DB::beginTransaction();
        try {
            // Get the goal with its measures
            $goal = PerformanceGoal::with(['areaOfFocus', 'measuresTargets', 'objectives.measuresTargets'])->find($goalId);

            // Create the deliverable (inactive by default) with snapshot
            $deliverable = EmployeeDeliverable::create([
                'tenant_id' => $tenantId,
                'employee_id' => $employeeId,
                'goal_id' => $goalId,
                'snapshot_title' => $goal->title,
                'snapshot_description' => $goal->description,
                'snapshot_goal_type' => $goal->goal_type,
                'assigned_by' => $assignedBy,
                'assigned_at' => now(),
                'is_active' => false,
            ]);

            // Determine which measures to assign
            if ($goal->goal_type === 'simple') {
                $measuresTargets = $goal->measuresTargets;
            } else {
                // Complex goal - get measures from objectives
                $measuresTargets = collect();
                foreach ($goal->objectives as $objective) {
                    $measuresTargets = $measuresTargets->merge($objective->measuresTargets);
                }
            }

            // Filter if specific measures were requested
            if (!empty($measureTargetIds)) {
                $measuresTargets = $measuresTargets->whereIn('id', $measureTargetIds);
            }

            // Create deliverable details with snapshot
            foreach ($measuresTargets as $measureTarget) {
                EmployeeDeliverableDetail::create([
                    'tenant_id' => $tenantId,
                    'employee_deliverable_id' => $deliverable->id,
                    'measure_target_id' => $measureTarget->id,
                    'snapshot_measure' => $measureTarget->measure_description,
                    'snapshot_target' => $measureTarget->target_description,
                    'snapshot_uom' => $measureTarget->uom,
                    'snapshot_weightage' => $measureTarget->weightage,
                ]);
            }

            DB::commit();
            return $deliverable;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
