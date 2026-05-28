<?php

namespace App\Http\Controllers\Performance;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Performance\AppraisalSubmission;
use App\Services\AppraisalWorkflowService;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppraisalTrackingController extends Controller
{
    use HandlesApiErrors;

    protected $workflowService;

    public function __construct(AppraisalWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Get all submissions for an appraisal with tracking info
     */
    public function getAppraisalSubmissions($appraisalId)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $submissions = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('appraisal_id', $appraisalId)
                ->with([
                    'employee.employmentDetails.position',
                    'employee.employmentDetails.department',
                    'levelScores' => function ($query) {
                        $query->orderBy('reviewer_level', 'desc')->limit(1);
                    },
                    'levelScores.reviewer',
                ])
                ->get();

            // Enrich with reviewer chain info
            $enrichedSubmissions = $submissions->map(function ($submission) use ($tenantId) {
                $reviewerChain = $this->workflowService->getReviewerChain(
                    $submission->id,
                    $submission->employee_id,
                    $tenantId,
                    10 // Max levels
                );

                $currentReviewerId = $this->workflowService->getReviewerForLevel(
                    $submission,
                    $submission->current_level
                );

                $currentReviewerObj = null;
                if ($currentReviewerId === 'SYSTEM_HR') {
                    $currentReviewerObj = [
                        'first_name' => 'System',
                        'last_name' => 'HR',
                        'id' => 'SYSTEM_HR'
                    ];
                } elseif ($currentReviewerId) {
                    $emp = \App\Models\Hris\Employee::find($currentReviewerId);
                    if ($emp) {
                        $currentReviewerObj = $emp;
                    }
                }

                $totalLevels = $submission->reviewer_levels ?? 2;

                // Flatten the response
                $data = $submission->toArray();
                $data['reviewer_chain'] = $reviewerChain;
                $data['current_reviewer_id'] = $currentReviewerId;
                $data['current_reviewer'] = $currentReviewerObj;
                $data['progress_percentage'] = $this->calculateProgress($submission);
                $data['total_levels'] = $totalLevels;

                return $data;
            });

            return ApiResponse::success($enrichedSubmissions);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching appraisal tracking');
        }
    }

    /**
     * Get tracking info for a specific employee
     */
    public function getEmployeeTracking($appraisalId, $employeeId)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $submission = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('appraisal_id', $appraisalId)
                ->where('employee_id', $employeeId)
                ->with([
                    'employee',
                    'appraisal',
                    'levelScores' => function ($query) {
                        $query->orderBy('reviewer_level');
                    },
                    'levelScores.reviewer',
                ])
                ->first();

            if (!$submission) {
                return ApiResponse::error('Submission not found', 404);
            }

            $reviewerChain = $this->workflowService->getReviewerChain(
                $submission->id,
                $submission->employee_id,
                $tenantId,
                10
            );

            $currentReviewerId = $this->workflowService->getReviewerForLevel(
                $submission,
                $submission->current_level
            );

            $currentReviewerObj = null;
            if ($currentReviewerId === 'SYSTEM_HR') {
                $currentReviewerObj = [
                    'first_name' => 'System',
                    'last_name' => 'HR',
                    'id' => 'SYSTEM_HR'
                ];
            } elseif ($currentReviewerId) {
                // Fetch employee details
                $emp = \App\Models\Hris\Employee::find($currentReviewerId);
                if ($emp) {
                    $currentReviewerObj = $emp;
                }
            }

            $totalLevels = $submission->reviewer_levels ?? 2;

            $data = $submission->toArray();
            $data['reviewer_chain'] = $reviewerChain;
            $data['current_reviewer_id'] = $currentReviewerId;
            $data['current_reviewer'] = $currentReviewerObj;
            $data['progress_percentage'] = $this->calculateProgress($submission);
            $data['timeline'] = $this->buildTimeline($submission);
            $data['total_levels'] = $totalLevels;

            return ApiResponse::success($data);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching employee tracking');
        }
    }

    /**
     * Get summary statistics for an appraisal
     */
    public function getAppraisalStats($appraisalId)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $total = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('appraisal_id', $appraisalId)
                ->count();

            $pending = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('appraisal_id', $appraisalId)
                ->where('status', 'pending')
                ->count();

            $inProgress = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('appraisal_id', $appraisalId)
                ->where('status', 'in_progress')
                ->count();

            $completed = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('appraisal_id', $appraisalId)
                ->where('status', 'completed')
                ->count();

            $returned = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('appraisal_id', $appraisalId)
                ->where('status', 'returned')
                ->count();

            return ApiResponse::success([
                'total_submissions' => $total,
                'pending_count' => $pending,
                'in_progress_count' => $inProgress,
                'completed_count' => $completed,
                'returned_count' => $returned,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching appraisal stats');
        }
    }

    /**
     * Calculate progress percentage for a submission
     */
    protected function calculateProgress(AppraisalSubmission $submission)
    {
        if ($submission->status === 'completed') {
            return 100;
        }

        if ($submission->status === 'pending') {
            return 0;
        }

        // For in-progress and returned, calculate based on snapshot settings
        $totalLevels = $submission->reviewer_levels ?? 2;
        $progress = ($submission->current_level / $totalLevels) * 100;

        return min(round($progress, 2), 90); // Cap at 90% until completed
    }

    /**
     * Build timeline of submission events
     */
    protected function buildTimeline(AppraisalSubmission $submission)
    {
        $timeline = [];

        $timeline[] = [
            'event' => 'created',
            'date' => $submission->created_at,
            'level' => 0,
        ];

        foreach ($submission->levelScores as $levelScore) {
            if ($levelScore->submitted_at) {
                $timeline[] = [
                    'event' => 'scored',
                    'date' => $levelScore->submitted_at,
                    'level' => $levelScore->reviewer_level,
                    'reviewer' => $levelScore->reviewer,
                ];
            }
        }

        if ($submission->completed_at) {
            $timeline[] = [
                'event' => 'completed',
                'date' => $submission->completed_at,
                'level' => 0,
            ];
        }

        return $timeline;
    }
}
