<?php

namespace App\Http\Controllers\Performance;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\Employee;
use App\Models\Performance\AppraisalAttachment;
use App\Models\Performance\AppraisalCompetencyScore;
use App\Models\Performance\AppraisalGoalScore;
use App\Models\Performance\AppraisalLevelScore;
use App\Models\Performance\AppraisalSubmission;
use App\Models\Performance\PerformanceSetting;
use App\Models\Performance\Competency;
use App\Models\Performance\EmployeeDeliverable;
use App\Services\AppraisalScoringService;
use App\Services\AppraisalWorkflowService;
use App\Services\FileUploadService;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppraisalSubmissionController extends Controller
{
    use HandlesApiErrors;

    protected $workflowService;
    protected $scoringService;
    protected $fileUploadService;

    public function __construct(
        AppraisalWorkflowService $workflowService,
        AppraisalScoringService $scoringService,
        FileUploadService $fileUploadService
    ) {
        $this->workflowService = $workflowService;
        $this->scoringService = $scoringService;
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Get submission details for employee or reviewer
     */
    public function show($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $submission = AppraisalSubmission::where('tenant_id', $tenantId)
                ->with([
                    'appraisal',
                    'employee.employmentDetails',
                    'levelScores' => function ($query) {
                        $query->orderBy('reviewer_level');
                    },
                    'levelScores.reviewer',
                    'levelScores.goalScores.employeeDeliverable.goal',
                    'levelScores.goalScores.measureTarget',
                    'levelScores.competencyScores.competency',
                    'attachments',
                ])
                ->findOrFail($id);

            // Get employee's deliverables and competencies (use frozen snapshot if available)
            if (!empty($submission->deliverables_snapshot)) {
                $deliverables = $submission->deliverables_snapshot;
            } else {
                $deliverables = EmployeeDeliverable::where('employee_id', $submission->employee_id)
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->with(['goal', 'details.measureTarget'])
                    ->get();
            }

            if (!empty($submission->competencies_snapshot)) {
                $competencies = $submission->competencies_snapshot;
            } else {
                $competencies = Competency::where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->get();
            }

            // Determine if current user is authorized to edit/review
            $userId = Auth::id();
            $currentEmployee = Employee::where('user_id', $userId)->where('tenant_id', $tenantId)->first();
            $canEdit = false;

            if ($currentEmployee) {
                if (in_array($submission->status, ['pending', 'returned'])) {
                    $canEdit = ($submission->employee_id === $currentEmployee->id);
                } elseif ($submission->status === 'in_progress') {
                    $expectedReviewer = $this->workflowService->getReviewerForLevel($submission, $submission->current_level);

                    if ($expectedReviewer === 'SYSTEM_HR') {
                        $canEdit = \App\Models\Preference\Preference::where('tenant_id', $tenantId)
                            ->where('category', 'hr_admins')
                            ->where('key', $userId)
                            ->where('value', 'like', '%"role":"System HR"%')
                            ->exists();
                    } else {
                        $canEdit = ($expectedReviewer === $currentEmployee->id);
                    }
                }
            }

            return ApiResponse::success([
                'submission' => $submission,
                'deliverables' => $deliverables,
                'competencies' => $competencies,
                'can_edit' => $canEdit,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching submission');
        }
    }

    /**
     * Submit scores at current level
     */
    public function submitScores(Request $request, $id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $userId = Auth::id();

            $submission = AppraisalSubmission::where('tenant_id', $tenantId)->findOrFail($id);

            // Get current employee
            $currentEmployee = Employee::where('user_id', $userId)->where('tenant_id', $tenantId)->first();
            if (!$currentEmployee) {
                return ApiResponse::error('Employee record not found', 404);
            }

            // Verify user is authorized for current level
            $expectedReviewer = $this->workflowService->getReviewerForLevel($submission, $submission->current_level);

            $isAuthorized = false;
            if ($expectedReviewer === 'SYSTEM_HR') {
                // Check if current user is in the "System HR" list in preferences
                $user = Auth::user();

                // Fetch HR Admins preference for this user
                // The value is stored as JSON string, so we use a LIKE query to find the ID as key
                // and ensure the role is "System HR"
                // Ideally this should use a proper JSON query if supported, but text search is safer for compatibility
                $isSystemHr = \App\Models\Preference\Preference::where('tenant_id', $user->tenant_id)
                    ->where('category', 'hr_admins')
                    ->where('key', $user->id)
                    ->where('value', 'like', '%"role":"System HR"%')
                    ->exists();

                if ($isSystemHr) {
                    $isAuthorized = true;
                }
            } elseif ($expectedReviewer === $currentEmployee->id) {
                $isAuthorized = true;
            }

            if (!$isAuthorized) {
                return ApiResponse::error('Unauthorized to submit at this level', 403);
            }

            $validated = $request->validate([
                'goal_scores' => 'required|array',
                'goal_scores.*.employee_deliverable_id' => 'required|exists:employee_deliverables,id',
                'goal_scores.*.measure_target_id' => 'required|exists:performance_measures_targets,id',
                'goal_scores.*.score' => 'required|numeric|min:0|max:100',
                'goal_scores.*.comments' => 'nullable|string',
                'goal_scores.*.evidence_url' => 'nullable|string',
                'competency_scores' => 'required|array',
                'competency_scores.*.competency_id' => 'required|exists:competencies,id',
                'competency_scores.*.score' => 'required|numeric|min:0|max:100',
                'competency_scores.*.comments' => 'nullable|string',
                'overall_comments' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Create or update level score
            $levelScore = AppraisalLevelScore::updateOrCreate(
                [
                    'submission_id' => $submission->id,
                    'reviewer_level' => $submission->current_level,
                ],
                [
                    'tenant_id' => $tenantId,
                    'reviewer_id' => $currentEmployee->id,
                    'comments' => $validated['overall_comments'] ?? null,
                    'submitted_at' => now(),
                ]
            );

            // Delete existing scores for this level (in case of resubmission)
            AppraisalGoalScore::where('level_score_id', $levelScore->id)->delete();
            AppraisalCompetencyScore::where('level_score_id', $levelScore->id)->delete();

            // Save goal scores
            foreach ($validated['goal_scores'] as $goalScoreData) {
                AppraisalGoalScore::create([
                    'tenant_id' => $tenantId,
                    'level_score_id' => $levelScore->id,
                    'employee_deliverable_id' => $goalScoreData['employee_deliverable_id'],
                    'measure_target_id' => $goalScoreData['measure_target_id'],
                    'score' => $goalScoreData['score'],
                    'comments' => $goalScoreData['comments'] ?? null,
                    'evidence_url' => $goalScoreData['evidence_url'] ?? null,
                ]);
            }

            // Save competency scores
            foreach ($validated['competency_scores'] as $compScoreData) {
                AppraisalCompetencyScore::create([
                    'tenant_id' => $tenantId,
                    'level_score_id' => $levelScore->id,
                    'competency_id' => $compScoreData['competency_id'],
                    'score' => $compScoreData['score'],
                    'comments' => $compScoreData['comments'] ?? null,
                ]);
            }

            // Calculate aggregates
            $this->scoringService->updateLevelScoreAggregates($levelScore->id, $tenantId);

            // Update submission status and freeze snapshot of deliverables/competencies if not already captured
            $updateData = ['status' => 'in_progress'];
            if (empty($submission->deliverables_snapshot)) {
                $activeDeliverables = EmployeeDeliverable::where('employee_id', $submission->employee_id)
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->with(['goal', 'details.measureTarget'])
                    ->get();
                $updateData['deliverables_snapshot'] = $activeDeliverables->toArray();
            }
            if (empty($submission->competencies_snapshot)) {
                $activeCompetencies = Competency::where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->get();
                $updateData['competencies_snapshot'] = $activeCompetencies->toArray();
            }

            $submission->update($updateData);

            DB::commit();

            return ApiResponse::success([
                'submission' => $submission->fresh(['levelScores']),
                'level_score' => $levelScore->fresh(['goalScores', 'competencyScores']),
            ], 'Scores submitted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'submitting scores');
        }
    }

    /**
     * Forward submission to next level
     */
    public function forward($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $submission = AppraisalSubmission::where('tenant_id', $tenantId)->findOrFail($id);

            $result = $this->workflowService->forwardToNextLevel($submission);

            if ($result['success']) {
                return ApiResponse::success($result['submission'], $result['message']);
            } else {
                return ApiResponse::error($result['message'], 422);
            }
        } catch (\Exception $e) {
            return $this->handleException($e, 'forwarding submission');
        }
    }

    /**
     * Submit score back to previous level
     */
    public function submitBack(Request $request, $id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $submission = AppraisalSubmission::where('tenant_id', $tenantId)->findOrFail($id);

            $validated = $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            $result = $this->workflowService->submitBackToPreviousLevel($submission, $validated['reason']);

            if ($result['success']) {
                return ApiResponse::success($result['submission'], $result['message']);
            } else {
                return ApiResponse::error($result['message'], 422);
            }
        } catch (\Exception $e) {
            return $this->handleException($e, 'submitting back');
        }
    }

    /**
     * Return submission to employee
     */
    public function returnToEmployee($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $submission = AppraisalSubmission::where('tenant_id', $tenantId)->findOrFail($id);

            $this->workflowService->returnToEmployee($submission);

            return ApiResponse::success(
                $submission->fresh(),
                'Appraisal returned to employee'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'returning submission');
        }
    }

    /**
     * Restart appraisal submission - reset to initial state
     */
    public function restart($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $submission = AppraisalSubmission::where('tenant_id', $tenantId)
                ->with('appraisal')
                ->findOrFail($id);

            // Validate that the appraisal is active
            if ($submission->appraisal->status !== 'active') {
                return ApiResponse::error('Only submissions from active appraisals can be restarted', 422);
            }

            // Delete all level scores for this submission
            AppraisalLevelScore::where('submission_id', $submission->id)->delete();

            // Delete all goal scores
            AppraisalGoalScore::whereHas('levelScore', function ($query) use ($submission) {
                $query->where('submission_id', $submission->id);
            })->delete();

            // Delete all competency scores
            AppraisalCompetencyScore::whereHas('levelScore', function ($query) use ($submission) {
                $query->where('submission_id', $submission->id);
            })->delete();

            // Delete all attachments
            $attachments = AppraisalAttachment::where('submission_id', $submission->id)->get();
            foreach ($attachments as $attachment) {
                if ($attachment->file_path) {
                    $this->fileUploadService->delete($attachment->file_path);
                }
                $attachment->delete();
            }

            // Reset submission to initial state (unfreeze snapshots)
            $submission->update([
                'current_level' => 1,
                'status' => 'pending',
                'submitted_at' => null,
                'completed_at' => null,
                'deliverables_snapshot' => null,
                'competencies_snapshot' => null,
            ]);

            return ApiResponse::success(
                $submission->fresh(),
                'Appraisal restarted successfully'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'restarting submission');
        }
    }

    /**
     * Bulk restart appraisal submissions
     */
    public function bulkRestart(Request $request)
    {
        try {
            $request->validate([
                'submission_ids' => 'required|array',
                'submission_ids.*' => 'required|integer',
            ]);

            $tenantId = Auth::user()->tenant_id;
            $submissionIds = $request->submission_ids;

            $submissions = AppraisalSubmission::where('tenant_id', $tenantId)
                ->with('appraisal')
                ->whereIn('id', $submissionIds)
                ->get();

            if ($submissions->isEmpty()) {
                return ApiResponse::error('No submissions found', 404);
            }

            $restarted = 0;
            $failed = 0;
            $errors = [];

            foreach ($submissions as $submission) {
                // Validate that the appraisal is active
                if ($submission->appraisal->status !== 'active') {
                    $failed++;
                    $errors[] = "Submission ID {$submission->id}: Appraisal is not active";
                    continue;
                }

                try {
                    // Delete all level scores for this submission
                    AppraisalLevelScore::where('submission_id', $submission->id)->delete();

                    // Delete all goal scores
                    AppraisalGoalScore::whereHas('levelScore', function ($query) use ($submission) {
                        $query->where('submission_id', $submission->id);
                    })->delete();

                    // Delete all competency scores
                    AppraisalCompetencyScore::whereHas('levelScore', function ($query) use ($submission) {
                        $query->where('submission_id', $submission->id);
                    })->delete();

                    // Delete all attachments
                    $attachments = AppraisalAttachment::where('submission_id', $submission->id)->get();
                    foreach ($attachments as $attachment) {
                        if ($attachment->file_path) {
                            $this->fileUploadService->delete($attachment->file_path);
                        }
                        $attachment->delete();
                    }

                    // Reset submission to initial state (unfreeze snapshots)
                    $submission->update([
                        'current_level' => 1,
                        'status' => 'pending',
                        'submitted_at' => null,
                        'completed_at' => null,
                        'deliverables_snapshot' => null,
                        'competencies_snapshot' => null,
                    ]);

                    $restarted++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Submission ID {$submission->id}: {$e->getMessage()}";
                }
            }

            return ApiResponse::success([
                'restarted' => $restarted,
                'failed' => $failed,
                'errors' => $errors,
            ], "{$restarted} submission(s) restarted successfully" . ($failed > 0 ? ", {$failed} failed" : ''));
        } catch (\Exception $e) {
            return $this->handleException($e, 'bulk restarting submissions');
        }
    }

    /**
     * Refresh individual submission settings from global defaults
     */
    public function refreshConfiguration($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $submission = AppraisalSubmission::where('tenant_id', $tenantId)->findOrFail($id);

            // Guard: Only allow editing if level 1 (employee) has NOT submitted yet
            if ($submission->current_level !== 1 || $submission->submitted_at !== null) {
                return ApiResponse::error('Settings can only be refreshed before the employee submits their appraisal.', 422);
            }

            // Get global settings
            $globalSettings = PerformanceSetting::where('tenant_id', $tenantId)->first();

            if (!$globalSettings) {
                return ApiResponse::error('Global performance settings not found.', 404);
            }

            // Update submission with global defaults
            $submission->update([
                'reviewer_levels' => $globalSettings->reviewer_levels,
                'reviewer_config' => $globalSettings->reviewer_config,
                'results_weight' => $globalSettings->results_weight,
                'competency_weight' => $globalSettings->competency_weight,
                'final_score_level' => $globalSettings->final_score_level,
                'enforce_submit_back' => $globalSettings->enforce_submit_back,
            ]);

            return ApiResponse::success($this->enrichSubmission($submission->fresh()), 'Appraisal settings refreshed from global defaults');
        } catch (\Exception $e) {
            return $this->handleException($e, 'refreshing submission settings');
        }
    }

    /**
     * Update individual submission settings (Snapshot Override)
     */
    public function updateSettings(Request $request, $id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $submission = AppraisalSubmission::where('tenant_id', $tenantId)->findOrFail($id);

            // Guard: Only allow editing if level 1 (employee) has NOT submitted yet
            if ($submission->current_level !== 1 || $submission->submitted_at !== null) {
                return ApiResponse::error('Settings can only be edited before the employee submits their appraisal.', 422);
            }

            $validated = $request->validate([
                'reviewer_levels' => 'integer|min:2|max:10',
                'reviewer_config' => 'array',
                'results_weight' => 'numeric|min:0|max:100',
                'competency_weight' => 'numeric|min:0|max:100',
                'final_score_level' => 'integer|min:1|max:10',
                'enforce_submit_back' => 'boolean',
            ]);

            // Validate weights sum to 100%
            if (isset($validated['results_weight']) && isset($validated['competency_weight'])) {
                if (abs(($validated['results_weight'] + $validated['competency_weight']) - 100) > 0.01) {
                    return ApiResponse::error('Results weight and competency weight must sum to 100%', 422);
                }
            }

            $submission->update($validated);

            return ApiResponse::success($this->enrichSubmission($submission->fresh()), 'Appraisal settings updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'updating submission settings');
        }
    }

    /**
     * Employee accepts returned appraisal
     */
    public function acceptReturn($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $userId = Auth::id();

            $submission = AppraisalSubmission::where('tenant_id', $tenantId)->findOrFail($id);

            // Verify it's the employee
            $employee = Employee::where('user_id', $userId)->where('tenant_id', $tenantId)->first();
            if ($employee->id !== $submission->employee_id) {
                return ApiResponse::error('Unauthorized', 403);
            }

            if ($submission->status !== 'returned') {
                return ApiResponse::error('Submission is not in returned state', 422);
            }

            $this->workflowService->employeeAcceptReturn($submission);

            return ApiResponse::success(
                $submission->fresh(),
                'Appraisal accepted and forwarded'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'accepting return');
        }
    }

    /**
     * Employee rejects returned appraisal
     */
    public function rejectReturn($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $userId = Auth::id();

            $submission = AppraisalSubmission::where('tenant_id', $tenantId)->findOrFail($id);

            // Verify it's the employee
            $employee = Employee::where('user_id', $userId)->where('tenant_id', $tenantId)->first();
            if ($employee->id !== $submission->employee_id) {
                return ApiResponse::error('Unauthorized', 403);
            }

            if ($submission->status !== 'returned') {
                return ApiResponse::error('Submission is not in returned state', 422);
            }

            $this->workflowService->employeeRejectReturn($submission);

            return ApiResponse::success(
                $submission->fresh(),
                'Appraisal rejected and sent back to manager'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'rejecting return');
        }
    }

    /**
     * Get my pending appraisals (employee or reviewer)
     */
    public function getMyPending()
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $userId = Auth::id();

            $employee = Employee::where('user_id', $userId)->where('tenant_id', $tenantId)->first();
            if (!$employee) {
                return ApiResponse::error('Employee record not found', 404);
            }

            // Get submissions where user is employee and pending
            $mySubmissions = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('employee_id', $employee->id)
                ->whereIn('status', ['pending', 'returned'])
                ->with('appraisal')
                ->get();

            // Get submissions where user is a reviewer at current level
            $reviewSubmissions = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('status', 'in_progress')
                ->with('appraisal', 'employee')
                ->get()
                ->filter(function ($submission) use ($employee, $userId) {
                    $expectedReviewer = $this->workflowService->getReviewerForLevel(
                        $submission,
                        $submission->current_level
                    );

                    if ($expectedReviewer === 'SYSTEM_HR') {
                        // Check if current user is in the "System HR" list in preferences
                        $user = Auth::user();
                        return \App\Models\Preference\Preference::where('tenant_id', $user->tenant_id)
                            ->where('category', 'hr_admins')
                            ->where('key', $user->id)
                            ->where('value', 'like', '%"role":"System HR"%')
                            ->exists();
                    }

                    return $expectedReviewer === $employee->id;
                });

            // Get tracked submissions (all active appraisals for this employee)
            $trackedSubmissions = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('employee_id', $employee->id)
                ->whereIn('status', ['pending', 'in_progress', 'returned'])
                ->with(['appraisal', 'levelScores.reviewer']) // Eager load reviewer for "Under Review" status
                ->get();

            return ApiResponse::success([
                'my_submissions' => $mySubmissions,
                'review_submissions' => $reviewSubmissions->values(),
                'tracked_submissions' => $trackedSubmissions,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching pending appraisals');
        }
    }
    /**
     * Get my completed appraisals (history)
     */
    public function getMyHistory()
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $userId = Auth::id();

            $employee = Employee::where('user_id', $userId)->where('tenant_id', $tenantId)->first();
            if (!$employee) {
                return ApiResponse::error('Employee record not found', 404);
            }

            $history = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('employee_id', $employee->id)
                ->where('status', 'completed')
                ->with(['appraisal', 'levelScores'])
                ->orderBy('completed_at', 'desc')
                ->get();

            // Use ScoringService to append official score
            $scoringService = app(\App\Services\AppraisalScoringService::class);
            $history->each(function ($submission) use ($scoringService) {
                $submission->official_final_score = $scoringService->getOfficialFinalScore($submission);
            });

            return ApiResponse::success($history);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching appraisal history');
        }
    }
    /**
     * Upload an attachment for a submission
     */
    public function uploadAttachment(Request $request, $id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $userId = Auth::id();

            $submission = AppraisalSubmission::where('tenant_id', $tenantId)->findOrFail($id);

            $validated = $request->validate([
                'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:10240', // 10MB max
            ]);

            // Upload file
            $uploadResult = $this->fileUploadService->upload(
                $request->file('file'),
                'performance-attachments',
                [
                    'validation' => ['mimes:pdf,doc,docx,jpg,jpeg,png,zip', 'max:10240'],
                ]
            );

            // Create attachment record
            $attachment = AppraisalAttachment::create([
                'tenant_id' => $tenantId,
                'submission_id' => $submission->id,
                'reviewer_level' => $submission->current_level,
                'file_name' => $request->file('file')->getClientOriginalName(),
                'file_path' => $uploadResult['path'],
                'storage_driver' => $uploadResult['metadata']['driver'] ?? 'local',
                'file_url' => $uploadResult['url'],
                'file_type' => $uploadResult['metadata']['mime_type'],
                'file_size' => $uploadResult['metadata']['size'],
                'uploaded_by' => $userId,
                'uploaded_at' => now(),
            ]);

            return ApiResponse::success([
                'attachment' => $attachment,
                'file_url' => $uploadResult['url'],
            ], 'File uploaded successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'uploading attachment');
        }
    }

    public function deleteAttachment(Request $request, $id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $userId = Auth::id();

            $attachment = AppraisalAttachment::where('tenant_id', $tenantId)->findOrFail($id);

            // Check if user is the uploader
            if ($attachment->uploaded_by !== $userId) {
                return ApiResponse::error('Unauthorized. Only the uploader can delete this attachment.', 403);
            }

            // Delete from storage
            $this->fileUploadService->delete($attachment->file_path);

            // Delete from database
            $attachment->delete();

            return ApiResponse::success(null, 'Attachment deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'deleting attachment');
        }
    }

    /**
     * Enrich submission with relations and tracking info (Replicating AppraisalTrackingController logic)
     */
    protected function enrichSubmission(AppraisalSubmission $submission)
    {
        $tenantId = Auth::user()->tenant_id;

        $submission->load([
            'employee.employmentDetails.position',
            'employee.employmentDetails.department',
            'levelScores' => function ($query) {
                $query->orderBy('reviewer_level', 'desc')->limit(1);
            },
            'levelScores.reviewer',
        ]);

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

        $reviewerChain = $this->workflowService->getReviewerChain(
            $submission->id,
            $submission->employee_id,
            $tenantId,
            10
        );

        $data = $submission->toArray();
        $data['reviewer_chain'] = $reviewerChain;
        $data['current_reviewer_id'] = $currentReviewerId;
        $data['current_reviewer'] = $currentReviewerObj;
        $data['total_levels'] = $submission->reviewer_levels ?? 2;
        $data['progress_percentage'] = $this->calculateProgress($submission);

        return $data;
    }

    /**
     * Replicated from AppraisalTrackingController
     */
    protected function calculateProgress(AppraisalSubmission $submission)
    {
        if ($submission->status === 'completed') {
            return 100;
        }

        if ($submission->status === 'pending') {
            return 0;
        }

        $totalLevels = $submission->reviewer_levels ?? 2;
        $progress = ($submission->current_level / $totalLevels) * 100;

        return min(round($progress, 2), 90);
    }
}
