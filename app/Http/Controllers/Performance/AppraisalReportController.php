<?php

namespace App\Http\Controllers\Performance;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Performance\AppraisalSubmission;
use App\Models\Performance\AppraisalLevelScore;
use App\Models\Performance\AppraisalCompetencyScore;
use App\Models\Hris\Department;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppraisalReportController extends Controller
{
    use HandlesApiErrors;

    /**
     * Get cycle status report
     */
    public function cycleStatusReport(Request $request, $appraisalId)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $departmentId = $request->input('department_id');

            $query = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('appraisal_id', $appraisalId)
                ->with(['employee.employmentDetails.department', 'employee.employmentDetails.position']);

            if ($departmentId) {
                $query->whereHas('employee.employmentDetails', function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                });
            }

            $report = $query->get()->map(function ($submission) {
                return [
                    'key' => (string)$submission->id,
                    'employee_number' => $submission->employee?->employee_number ?? 'N/A',
                    'employee_name' => $submission->employee?->full_name ?? 'N/A',
                    'department' => $submission->employee?->employmentDetails?->department?->name ?? 'N/A',
                    'position' => $submission->employee?->employmentDetails?->position?->title ?? 'N/A',
                    'status' => ucfirst(str_replace('_', ' ', $submission->status)),
                    'current_level' => $submission->current_level,
                    'progress_percentage' => round(($submission->current_level / ($submission->reviewer_levels ?: 1)) * 100, 2),
                    'submitted_at' => $submission->submitted_at ? $submission->submitted_at->format('Y-m-d H:i') : null,
                    'completed_at' => $submission->completed_at ? $submission->completed_at->format('Y-m-d H:i') : null,
                ];
            });

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching cycle status report');
        }
    }

    /**
     * Get performance league table (rankings)
     */
    public function performanceLeagueTable(Request $request, $appraisalId)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $limit = $request->input('limit', 100);

            $scoringService = app(\App\Services\AppraisalScoringService::class);

            $submissions = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('appraisal_id', $appraisalId)
                ->whereIn('status', ['completed', 'in_progress'])
                ->whereHas('levelScores')
                ->with(['employee.employmentDetails.department', 'levelScores' => function ($q) {
                    $q->orderBy('reviewer_level', 'desc');
                }])
                ->get();

            $report = $submissions->map(function ($submission) use ($scoringService) {
                $score = $scoringService->getOfficialFinalScore($submission);
                if ($score === null) {
                    $latestScoreRecord = $submission->levelScores->first();
                    $score = $latestScoreRecord ? (float) $latestScoreRecord->final_score : null;
                }

                if ($score === null) return null;

                return [
                    'key' => (string)$submission->id,
                    'employee_number' => $submission->employee?->employee_number ?? 'N/A',
                    'employee_name' => $submission->employee?->full_name ?? 'N/A',
                    'department' => $submission->employee?->employmentDetails?->department?->name ?? 'N/A',
                    'final_score' => round((float)$score, 2),
                    'rating' => $this->getRatingLabel((float)$score),
                ];
            })
                ->filter()
                ->sortByDesc('final_score')
                ->values();

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching league table');
        }
    }

    /**
     * Get departmental performance comparison
     */
    public function departmentalPerformance(Request $request, $appraisalId)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $submissions = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('appraisal_id', $appraisalId)
                ->whereIn('status', ['completed', 'in_progress'])
                ->whereHas('levelScores')
                ->with(['employee.employmentDetails.department', 'levelScores' => function ($q) {
                    $q->orderBy('reviewer_level', 'desc');
                }])
                ->get();

            $depts = [];
            $scoringService = app(\App\Services\AppraisalScoringService::class);

            foreach ($submissions as $submission) {
                $dept = $submission->employee?->employmentDetails?->department?->name ?? 'Unassigned';

                $score = $scoringService->getOfficialFinalScore($submission);
                if ($score === null) {
                    $latestScoreRecord = $submission->levelScores->first();
                    $score = $latestScoreRecord ? (float) $latestScoreRecord->final_score : null;
                }

                if ($score === null) continue;

                if (!isset($depts[$dept])) {
                    $depts[$dept] = ['department' => $dept, 'total' => 0, 'count' => 0, 'min' => 100, 'max' => 0];
                }

                $depts[$dept]['total'] += $score;
                $depts[$dept]['count']++;
                $depts[$dept]['min'] = min($depts[$dept]['min'], $score);
                $depts[$dept]['max'] = max($depts[$dept]['max'], $score);
            }

            $report = array_map(function ($d) {
                return [
                    'department' => $d['department'],
                    'employee_count' => $d['count'],
                    'average_score' => round($d['total'] / $d['count'], 2),
                    'min_score' => round($d['min'], 2),
                    'max_score' => round($d['max'], 2),
                ];
            }, array_values($depts));

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching departmental performance report');
        }
    }

    /**
     * Get competency gap analysis
     */
    public function competencyGapAnalysis(Request $request, $appraisalId)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            // Average scores per competency across all completed submissions
            $report = AppraisalCompetencyScore::join('appraisal_submissions', 'appraisal_competency_scores.submission_id', '=', 'appraisal_submissions.id')
                ->join('performance_competencies', 'appraisal_competency_scores.competency_id', '=', 'performance_competencies.id')
                ->where('appraisal_submissions.tenant_id', $tenantId)
                ->where('appraisal_submissions.appraisal_id', $appraisalId)
                ->whereIn('appraisal_submissions.status', ['completed', 'in_progress'])
                ->where('appraisal_competency_scores.reviewer_level', DB::raw('appraisal_submissions.final_score_level'))
                ->select(
                    'performance_competencies.name as competency',
                    DB::raw('AVG(appraisal_competency_scores.score) as average_rating'),
                    DB::raw('COUNT(*) as evaluation_count')
                )
                ->groupBy('performance_competencies.name')
                ->get()
                ->map(function ($row) {
                    $target = 4.0; // Assume a generic target of 4.0 for now, or fetch from config
                    return [
                        'competency' => $row->competency,
                        'average_rating' => round((float)$row->average_rating, 2),
                        'target_rating' => $target,
                        'gap' => round($target - (float)$row->average_rating, 2),
                        'evaluation_count' => $row->evaluation_count,
                    ];
                });

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching competency gap analysis');
        }
    }

    /**
     * Get pending reviews (exception report)
     */
    public function pendingReviews(Request $request, $appraisalId)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $report = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('appraisal_id', $appraisalId)
                ->whereIn('status', ['pending', 'in_progress', 'returned'])
                ->with(['employee.employmentDetails.department', 'employee.employmentDetails.manager'])
                ->get()
                ->map(function ($submission) {
                    $daysPending = $submission->updated_at ? now()->diffInDays($submission->updated_at) : 0;
                    return [
                        'key' => (string)$submission->id,
                        'employee_name' => $submission->employee?->full_name ?? 'N/A',
                        'department' => $submission->employee?->employmentDetails?->department?->name ?? 'N/A',
                        'status' => ucfirst($submission->status),
                        'current_level' => $submission->current_level,
                        'days_pending' => $daysPending,
                        'manager' => $submission->employee?->employmentDetails?->manager?->full_name ?? 'N/A',
                    ];
                })
                ->sortByDesc('days_pending')
                ->values();

            return ApiResponse::success($report);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching pending reviews report');
        }
    }

    private function getRatingLabel($score)
    {
        if ($score >= 90) return 'Outstanding';
        if ($score >= 75) return 'Exceeds Expectations';
        if ($score >= 50) return 'Meets Expectations';
        return 'Needs Improvement';
    }
}
