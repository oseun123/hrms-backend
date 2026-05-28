<?php

namespace App\Services;

use App\Models\Requests\RequestSubmission;
use App\Models\Requests\RequestTemplate;
use App\Models\Requests\RequestWorkflow;
use App\Models\Requests\RequestApproval;
use App\Models\Hris\Employee;
use App\Notifications\RequestPendingApproval;
use App\Notifications\RequestStatusUpdated;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RequestService
{
    /**
     * Generate a unique reference number for a request.
     */
    public function generateReferenceNumber($tenantId)
    {
        $year = date('Y');
        $count = RequestSubmission::where('tenant_id', $tenantId)
            ->whereYear('submitted_at', $year)
            ->count() + 1;

        return 'REQ-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Start the approval chain for a request submission.
     */
    public function startApprovalChain(RequestSubmission $submission)
    {
        /** @var \App\Models\Requests\RequestTemplate|null $template */
        $template = RequestTemplate::where('id', $submission->template_id)->with('workflow.levels')->first();
        
        if (!$template || !($workflow = $template->workflow)) {
            // No workflow assigned or template not found, auto-approve
            $submission->update([
                'status' => 'approved',
                'completed_at' => now(),
            ]);
            return;
        }

        $levels = $workflow->levels;

        if ($levels->isEmpty()) {
            // Empty workflow, auto-approve
            $submission->update([
                'status' => 'approved',
                'completed_at' => now(),
            ]);
            return;
        }

        // Create approval records for all levels
        foreach ($levels as $level) {
            $approverId = $this->resolveApprover($submission, $level);
            
            if ($approverId) {
                RequestApproval::create([
                    'request_submission_id' => $submission->id,
                    'approver_id' => $approverId,
                    'level' => $level->level,
                    'status' => 'pending',
                ]);
            }
        }

        // Check if level 1 approver was found
        $firstApproval = $submission->approvals()->where('level', 1)->first();
        if ($firstApproval) {
            $submission->update(['status' => 'in_progress', 'current_level' => 1]);
            $firstApproval->update(['notified_at' => now()]);
            
            // Trigger notification for the first approver
            if ($approver = $firstApproval->approver) {
                $approver->notify(new RequestPendingApproval($submission));
            }
        } else {
            // If no approver found for first level, skip or handle (for now, skip to next)
            $this->advanceApproval($submission);
        }
    }

    /**
     * Resolve the approver ID (User ID) based on the level configuration.
     */
    protected function resolveApprover(RequestSubmission $submission, $level)
    {
        $employee = $submission->employee()->first();
        if (!$employee) {
            return null;
        }

        $employmentDetails = $employee->employmentDetails;
        if (!$employmentDetails) {
            return null;
        }
        
        switch ($level->approver_type) {
            case 'specific_employee':
                return Employee::where('id', $level->approver_id)->value('user_id');
            
            case 'manager':
                $employeeId = $employmentDetails->manager_id;
                return $employeeId ? Employee::where('id', $employeeId)->value('user_id') : null;
            
            case 'team_lead':
                $employeeId = $employmentDetails->team_lead_id;
                return $employeeId ? Employee::where('id', $employeeId)->value('user_id') : null;
            
            case 'secondary_manager':
                $employeeId = $employmentDetails->secondary_manager_id;
                return $employeeId ? Employee::where('id', $employeeId)->value('user_id') : null;
            
            case 'hr':
                // Find first HR user in tenant
                return \App\Models\User::where('tenant_id', $submission->tenant_id)
                    ->where('is_hr', true)
                    ->value('id');
            
            default:
                return null;
        }
    }

    /**
     * Advance the approval to the next level.
     */
    public function advanceApproval(RequestSubmission $submission)
    {
        $currentLevel = $submission->current_level;
        $nextLevel = $currentLevel + 1;
        
        $nextApproval = $submission->approvals()->where('level', $nextLevel)->first();
        
        if ($nextApproval) {
            $submission->update(['current_level' => $nextLevel]);
            $nextApproval->update(['notified_at' => now()]);
            
            // Trigger notification for the next approver
            if ($approver = $nextApproval->approver) {
                $approver->notify(new RequestPendingApproval($submission));
            }
        } else {
            // All levels approved
            $submission->update([
                'status' => 'approved',
                'completed_at' => now(),
            ]);

            // Notify requester of approval
            if ($requester = $submission->employee->user) {
                $requester->notify(new RequestStatusUpdated($submission, 'approved'));
            }
        }
    }
}
