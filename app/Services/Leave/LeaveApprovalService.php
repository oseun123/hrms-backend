<?php

namespace App\Services\Leave;

use App\Models\Leave\LeaveRequest;
use App\Models\Leave\LeaveApproval;
use App\Models\Leave\LeavePolicy;
use App\Models\Hris\Employee;
use App\Models\Leave\LeaveWorkflow;
use App\Models\Leave\LeaveWorkflowLevel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LeaveApprovalService
{
    /**
     * Initialize the approval chain for a new leave request.
     */
    public function initializeApprovalChain(LeaveRequest $leaveRequest, LeavePolicy $policy)
    {
        if ($policy->leave_workflow_id) {
            $workflow = $policy->workflow;
            if ($workflow && $workflow->levels()->exists()) {
                return $this->advanceToNextAvailableLevel($leaveRequest, 1, $workflow);
            }
        }

        // Legacy/Fallback Logic
        $employee = $leaveRequest->employee;
        $manager = $employee->employmentDetails->manager;

        if ($manager && $manager->user_id) {
            return $this->createApprovalStage($leaveRequest, $manager->user_id, 1);
        } else {
            $hrUser = $this->getFallbackApprover($leaveRequest->tenant_id);
            if ($hrUser) {
                return $this->createApprovalStage($leaveRequest, $hrUser->id, 1);
            }
        }

        return null;
    }

    /**
     * Progress the approval chain to the next stage.
     */
    public function progressChain(LeaveRequest $leaveRequest, LeaveApproval $currentApproval)
    {
        $policy = LeavePolicy::where('leave_group_id', $leaveRequest->employee->employmentDetails->leave_group_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->first();

        if (!$policy) return;

        $nextLevel = $currentApproval->level + 1;

        if ($policy->leave_workflow_id) {
            $workflow = $policy->workflow;
            if ($workflow) {
                $stage = $this->advanceToNextAvailableLevel($leaveRequest, $nextLevel, $workflow);
                if ($stage) return;
            }
        } else {
            // Legacy Logic
            if ($nextLevel <= $policy->approval_stages) {
                $approverId = $this->determineLegacyApprover($leaveRequest, $nextLevel, $policy);
                if ($approverId) {
                    $this->createApprovalStage($leaveRequest, $approverId, $nextLevel);
                    return;
                }
            }
        }

        // No more stages, fully approve the leave request
        $leaveRequest->update(['status' => 'approved']);
        $this->deductBalance($leaveRequest);

        // Notify Employee
        $employeeUser = $leaveRequest->employee->user;
        if ($employeeUser) {
            $employeeUser->notify(new \App\Notifications\LeaveRequestApproved($leaveRequest));
        }
    }

    /**
     * Finds the next valid approval level in the workflow, skipping invalid ones.
     */
    protected function advanceToNextAvailableLevel(LeaveRequest $leaveRequest, $startLevel, LeaveWorkflow $workflow)
    {
        $levels = $workflow->levels()->where('level', '>=', $startLevel)->get();

        foreach ($levels as $levelConfig) {
            $approverUserId = $this->resolveApproverUserId($leaveRequest, $levelConfig);

            // Edge Case: Missing Approver
            if (!$approverUserId) {
                continue;
            }

            // Create Stage
            return $this->createApprovalStage($leaveRequest, $approverUserId, $levelConfig->level);
        }

        return null;
    }

    protected function resolveApproverUserId(LeaveRequest $leaveRequest, LeaveWorkflowLevel $levelConfig)
    {
        $employee = $leaveRequest->employee;
        $details = $employee->employmentDetails;

        switch ($levelConfig->approver_type) {
            case 'manager':
                return $details->manager?->user_id;
            case 'team_lead':
                return $details->teamLead?->user_id;
            case 'secondary_manager':
                return $details->secondaryManager?->user_id;
            case 'hr':
                $hrUser = $this->getFallbackApprover($leaveRequest->tenant_id);
                return $hrUser?->id;
            case 'specific_employee':
                $specificEmp = Employee::find($levelConfig->approver_id);
                return $specificEmp?->user_id;
            default:
                return null;
        }
    }

    /**
     * Handle decline action.
     */
    public function handleDecline(LeaveRequest $leaveRequest, $reason)
    {
        $leaveRequest->update([
            'status' => 'declined',
            'decline_reason' => $reason,
        ]);

        // Decrement pending_approval balance
        $balance = \App\Models\Leave\LeaveBalance::where('employee_id', $leaveRequest->employee_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', \Carbon\Carbon::parse($leaveRequest->start_date)->year)
            ->first();

        if ($balance) {
            $balance->decrementPending((float) $leaveRequest->duration_days);
        }

        // Notify Employee
        $employeeUser = $leaveRequest->employee->user;
        if ($employeeUser) {
            $employeeUser->notify(new \App\Notifications\LeaveRequestDeclined($leaveRequest));
        }
    }

    protected function createApprovalStage(LeaveRequest $leaveRequest, $approverId, $level)
    {
        $approval = LeaveApproval::create([
            'tenant_id' => $leaveRequest->tenant_id,
            'leave_request_id' => $leaveRequest->id,
            'approver_id' => $approverId,
            'level' => $level,
            'status' => 'pending',
        ]);

        // Notify Approver
        $approver = User::find($approverId);
        if ($approver) {
            $approver->notify(new \App\Notifications\LeaveRequestSubmitted($leaveRequest));
        }

        return $approval;
    }

    protected function determineLegacyApprover(LeaveRequest $leaveRequest, $level, LeavePolicy $policy)
    {
        if ($level === 2 && $policy->requires_hr_approval) {
            $hrUser = $this->getFallbackApprover($leaveRequest->tenant_id);
            return $hrUser ? $hrUser->id : null;
        }

        return null;
    }

    protected function getFallbackApprover($tenantId)
    {
        return User::where('tenant_id', $tenantId)
            ->whereHas('roles', function ($q) {
                $q->where('name', 'hr');
            })->first() ?: User::where('tenant_id', $tenantId)
            ->whereHas('roles', function ($q) {
                $q->where('name', 'admin');
            })->first() ?: User::where('tenant_id', $tenantId)->first();
    }

    protected function deductBalance(LeaveRequest $leaveRequest)
    {
        $balance = \App\Models\Leave\LeaveBalance::where('employee_id', $leaveRequest->employee_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', \Carbon\Carbon::parse($leaveRequest->start_date)->year)
            ->first();

        if ($balance) {
            $balance->decrementPending((float) $leaveRequest->duration_days);
            $balance->increment('used', (float) $leaveRequest->duration_days);
        }
    }
}
