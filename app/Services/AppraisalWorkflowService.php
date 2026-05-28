<?php

namespace App\Services;

use App\Models\Hris\Employee;
use App\Models\Performance\AppraisalSubmission;
use App\Models\Performance\PerformanceSetting;
use Illuminate\Support\Facades\DB;

class AppraisalWorkflowService
{
    /**
     * Get the reviewer chain for an employee
     *
     * @param int $employeeId
     * @param int $tenantId
     * @param int $maxLevels
     * @return array Array of [level => employee_id]
     */
    public function getReviewerChain($submissionId, $employeeId, $tenantId, $maxLevels)
    {
        $submission = AppraisalSubmission::findOrFail($submissionId);
        $reviewerConfig = $submission->reviewer_config ?? [];

        $chain = [];
        $chain[1] = $employeeId; // Level 1 is always the employee

        $currentEmployee = Employee::with('employmentDetails')->find($employeeId);

        // Build the chain
        for ($level = 2; $level <= $maxLevels; $level++) {
            // Check for specific employee assignment in config
            // Config format: [{level: 2, type: 'specific', employee_id: 123}, ...]
            $configForLevel = collect($reviewerConfig)->first(function ($item) use ($level) {
                return isset($item['level']) && $item['level'] == $level;
            });

            if ($configForLevel && isset($configForLevel['type'])) {
                $type = $configForLevel['type'];

                if ($type === 'specific' && !empty($configForLevel['employee_id'])) {
                    $chain[$level] = $configForLevel['employee_id'];
                    $currentEmployee = Employee::with('employmentDetails')->find($configForLevel['employee_id']);
                    continue;
                } elseif ($type === 'team_lead' && $currentEmployee->employmentDetails->team_lead_id) {
                    $chain[$level] = $currentEmployee->employmentDetails->team_lead_id;
                    $currentEmployee = Employee::with('employmentDetails')->find($chain[$level]);
                    continue;
                } elseif ($type === 'secondary_manager' && $currentEmployee->employmentDetails->secondary_manager_id) {
                    $chain[$level] = $currentEmployee->employmentDetails->secondary_manager_id;
                    $currentEmployee = Employee::with('employmentDetails')->find($chain[$level]);
                    continue;
                } elseif ($type === 'system_hr') {
                    // Start of Selection
                    $chain[$level] = 'SYSTEM_HR';
                    // We don't change $currentEmployee because HR doesn't sit in the hierarchy chain
                    // The next level (if any) should probably validly continue from the previous employee
                    // OR if the next level is "Manager", it should be the manager of the original employee?
                    // Usually System HR is the end or a checkpoint.
                    continue;
                    // End of Selection
                }
            }

            // Default behavior: Manager of previous person
            if (!$currentEmployee || !$currentEmployee->employmentDetails) {
                break;
            }

            $managerId = $currentEmployee->employmentDetails->manager_id;

            if (!$managerId) {
                break;
            }

            $chain[$level] = $managerId;
            $currentEmployee = Employee::with('employmentDetails')->find($managerId);
        }

        return $chain;
    }

    /**
     * Determine the next level for submission based on available reviewers
     *
     * @param int $currentLevel
     * @param array $reviewerChain
     * @param int $maxLevels
     * @return int|null Next level or null if complete
     */
    public function getNextLevel($currentLevel, $reviewerChain, $maxLevels)
    {
        // Find the next available level in the chain
        for ($level = $currentLevel + 1; $level <= $maxLevels; $level++) {
            if (isset($reviewerChain[$level])) {
                return $level;
            }
        }

        return null; // No more levels, appraisal is complete
    }

    /**
     * Check if employee can submit appraisal (must have line manager)
     *
     * @param int $employeeId
     * @return bool
     */
    public function canEmployeeSubmit($employeeId)
    {
        $employee = Employee::with('employmentDetails')->find($employeeId);

        if (!$employee || !$employee->employmentDetails) {
            return false;
        }

        // Employee must have a line manager (level 2)
        return !empty($employee->employmentDetails->manager_id);
    }

    /**
     * Submit appraisal to next level
     *
     * @param AppraisalSubmission $submission
     * @return bool
     */
    public function submitToNextLevel(AppraisalSubmission $submission)
    {
        $maxLevels = $submission->reviewer_levels ?? 2;

        $reviewerChain = $this->getReviewerChain(
            $submission->id,
            $submission->employee_id,
            $submission->tenant_id,
            $maxLevels
        );

        $nextLevel = $this->getNextLevel($submission->current_level, $reviewerChain, $maxLevels);

        if ($nextLevel === null) {
            // No more levels, mark as completed
            $submission->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            return true;
        }

        // Move to next level
        $submission->update([
            'current_level' => $nextLevel,
            'status' => 'in_progress',
            'submitted_at' => now(),
        ]);

        return true;
    }

    /**
     * Return appraisal to employee
     *
     * @param AppraisalSubmission $submission
     * @return bool
     */
    public function returnToEmployee(AppraisalSubmission $submission)
    {
        $submission->update([
            'status' => 'returned',
            'current_level' => 1, // Back to employee
        ]);

        return true;
    }

    /**
     * Employee accepts returned appraisal
     *
     * @param AppraisalSubmission $submission
     * @return bool
     */
    public function employeeAcceptReturn(AppraisalSubmission $submission)
    {
        // When employee accepts, resume normal flow
        return $this->submitToNextLevel($submission);
    }

    /**
     * Employee rejects returned appraisal
     *
     * @param AppraisalSubmission $submission
     * @return bool
     */
    public function employeeRejectReturn(AppraisalSubmission $submission)
    {
        // Send back to line manager (level 2)
        $submission->update([
            'current_level' => 2,
            'status' => 'in_progress',
        ]);

        return true;
    }

    /**
     * Get the employee ID who should review at a given level
     *
     * @param AppraisalSubmission $submission
     * @param int $level
     * @return int|null
     */
    public function getReviewerForLevel(AppraisalSubmission $submission, $level)
    {
        $maxLevels = $submission->reviewer_levels ?? 2;

        $reviewerChain = $this->getReviewerChain(
            $submission->id,
            $submission->employee_id,
            $submission->tenant_id,
            $maxLevels
        );

        return $reviewerChain[$level] ?? null;
    }
}
