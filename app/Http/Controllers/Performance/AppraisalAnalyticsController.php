<?php

namespace App\Http\Controllers\Performance;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Performance\AppraisalSubmission;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppraisalAnalyticsController extends Controller
{
    use HandlesApiErrors;

    /**
     * Get cycle completion statistics (Not Started, In Progress, Returned, Completed)
     */
    public function getCycleCompletionStats($appraisalId)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $total = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('appraisal_id', $appraisalId)
                ->count();

            if ($total === 0) {
                return ApiResponse::success([
                    'total' => 0,
                    'completed' => 0,
                    'in_progress' => 0,
                    'pending' => 0,
                    'returned' => 0,
                    'completion_rate' => 0
                ]);
            }

            $stats = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('appraisal_id', $appraisalId)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $completed = $stats['completed'] ?? 0;
            $inProgress = $stats['in_progress'] ?? 0;
            $pending = $stats['pending'] ?? 0;
            $returned = $stats['returned'] ?? 0;

            return ApiResponse::success([
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'pending' => $pending,
                'returned' => $returned,
                'completion_rate' => round(($completed / $total) * 100, 2)
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching cycle completion stats');
        }
    }

    /**
     * Get the distribution (bell curve) of official final scores
     */
    public function getScoreDistribution($appraisalId)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            // Include 'completed' and 'in_progress' where at least one level score exists
            $submissions = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('appraisal_id', $appraisalId)
                ->whereIn('status', ['completed', 'in_progress'])
                ->whereHas('levelScores')
                ->with(['levelScores' => function ($query) {
                    $query->orderBy('reviewer_level', 'desc');
                }])
                ->get();

            $buckets = [
                'needs_improvement' => [
                    'label' => 'Needs Improvement (< 50%)',
                    'count' => 0,
                    'min' => 0,
                    'max' => 49.99
                ],
                'meets_expectations' => [
                    'label' => 'Meets Expectations (50% - 75%)',
                    'count' => 0,
                    'min' => 50,
                    'max' => 75.99
                ],
                'exceeds_expectations' => [
                    'label' => 'Exceeds Expectations (76% - 90%)',
                    'count' => 0,
                    'min' => 76,
                    'max' => 90.99
                ],
                'outstanding' => [
                    'label' => 'Outstanding (> 90%)',
                    'count' => 0,
                    'min' => 91,
                    'max' => 100
                ]
            ];

            $scoringService = app(\App\Services\AppraisalScoringService::class);
            $totalAnalyzed = 0;
            foreach ($submissions as $submission) {
                // Get official final score or fall back to latest available review score
                $score = $scoringService->getOfficialFinalScore($submission);

                if ($score === null) {
                    $latestScoreRecord = $submission->levelScores->first();
                    $score = $latestScoreRecord ? (float) $latestScoreRecord->final_score : null;
                }

                if ($score === null) continue;
                $totalAnalyzed++;

                if ($score < 50) {
                    $buckets['needs_improvement']['count']++;
                } elseif ($score < 76) {
                    $buckets['meets_expectations']['count']++;
                } elseif ($score < 91) {
                    $buckets['exceeds_expectations']['count']++;
                } else {
                    $buckets['outstanding']['count']++;
                }
            }

            // Transform for easier frontend charting
            $chartData = array_values(array_map(function ($key, $data) {
                return [
                    'category' => $data['label'],
                    'count' => $data['count'],
                    'key' => $key
                ];
            }, array_keys($buckets), $buckets));

            return ApiResponse::success([
                'total_analyzed' => $totalAnalyzed,
                'distribution' => $chartData
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching score distribution');
        }
    }

    /**
     * Get average scores by department
     */
    public function getDepartmentAverages($appraisalId)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            // Using eager loading to calculate in PHP. For massive datasets, a complex join would be better.
            $submissions = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('appraisal_id', $appraisalId)
                ->whereIn('status', ['completed', 'in_progress'])
                ->whereHas('levelScores')
                ->with([
                    'employee.employmentDetails.department',
                    'levelScores' => function ($query) {
                        $query->orderBy('reviewer_level', 'desc');
                    }
                ])
                ->get();

            $departments = [];

            $scoringService = app(\App\Services\AppraisalScoringService::class);
            foreach ($submissions as $submission) {
                $deptName = $submission->employee?->employmentDetails?->department?->name ?? 'Unassigned';

                $score = $scoringService->getOfficialFinalScore($submission);
                if ($score === null) {
                    $latestScoreRecord = $submission->levelScores->first();
                    $score = $latestScoreRecord ? (float) $latestScoreRecord->final_score : null;
                }

                if ($score === null) continue;

                if (!isset($departments[$deptName])) {
                    $departments[$deptName] = [
                        'department' => $deptName,
                        'total_score' => 0,
                        'employee_count' => 0
                    ];
                }

                $departments[$deptName]['total_score'] += $score;
                $departments[$deptName]['employee_count']++;
            }

            // Calculate averages
            $chartData = array_map(function ($dept) {
                return [
                    'department' => $dept['department'],
                    'average_score' => round($dept['total_score'] / $dept['employee_count'], 2),
                    'employee_count' => $dept['employee_count']
                ];
            }, array_values($departments));

            // Sort by highest average score
            usort($chartData, function ($a, $b) {
                return $b['average_score'] <=> $a['average_score'];
            });

            return ApiResponse::success($chartData);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching department averages');
        }
    }

    /**
     * Get top and bottom performers
     */
    public function getTopBottomPerformers($appraisalId)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $submissions = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('appraisal_id', $appraisalId)
                ->whereIn('status', ['completed', 'in_progress'])
                ->whereHas('levelScores')
                ->with([
                    'employee.employmentDetails.department',
                    'employee.employmentDetails.position',
                    'levelScores' => function ($query) {
                        $query->orderBy('reviewer_level', 'desc');
                    }
                ])
                ->get()
                ->map(function ($submission) {
                    $scoringService = app(\App\Services\AppraisalScoringService::class);
                    $score = $scoringService->getOfficialFinalScore($submission);
                    if ($score === null) {
                        $latestScoreRecord = $submission->levelScores->first();
                        $score = $latestScoreRecord ? (float) $latestScoreRecord->final_score : null;
                    }

                    return [
                        'employee_id' => $submission->employee_id,
                        'name' => $submission->employee?->full_name ?? 'N/A',
                        'department' => $submission->employee?->employmentDetails?->department?->name ?? 'N/A',
                        'position' => $submission->employee?->employmentDetails?->position?->title ?? 'N/A',
                        'score' => $score !== null ? (float) round($score, 2) : 0,
                        'photo_url' => $submission->employee->photo_url ?? null
                    ];
                })
                ->filter(fn($item) => $item['score'] > 0) // Filter out submissions with no valid score
                ->sortByDesc('score')
                ->values(); // Reset array keys

            // Calculate 10%
            $totalCount = $submissions->count();
            if ($totalCount === 0) {
                return ApiResponse::success(['top' => [], 'bottom' => []]);
            }

            // At least 1, max 10
            $tenPercent = max(1, min(10, (int) ceil($totalCount * 0.10)));

            $top = $submissions->take($tenPercent)->values();

            // For bottom, take from the end. If total == 1, don't show same in bottom
            $bottom = $totalCount > 1 ? $submissions->take(-$tenPercent)->values() : collect([]);

            return ApiResponse::success([
                'top' => $top,
                'bottom' => $bottom,
                'analyzed_count' => $totalCount,
                'percent_bracket' => "Top/Bottom {$tenPercent} employee(s)"
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching top/bottom performers');
        }
    }

    /**
     * Get Goals vs Competencies average breakdown
     */
    public function getGoalsVsCompetencies($appraisalId)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $submissions = AppraisalSubmission::where('tenant_id', $tenantId)
                ->where('appraisal_id', $appraisalId)
                ->whereIn('status', ['completed', 'in_progress'])
                ->whereHas('levelScores')
                ->with(['levelScores' => function ($query) {
                    $query->orderBy('reviewer_level', 'desc');
                }])
                ->get();

            $totalEmployees = $submissions->count();

            if ($totalEmployees === 0) {
                return ApiResponse::success([
                    'goals_average' => 0,
                    'competencies_average' => 0,
                    'total_evaluated' => 0,
                    'chart_data' => []
                ]);
            }

            $totalGoalsScore = 0;
            $totalCompetenciesScore = 0;

            $scoringService = app(\App\Services\AppraisalScoringService::class);
            $totalAnalyzed = 0;
            foreach ($submissions as $submission) {
                // Try official final score level first
                $finalScoreLevel = $submission->final_score_level ?? 2;
                $scoreRecord = $submission->levelScores->where('reviewer_level', $finalScoreLevel)->first();

                // Fallback to latest available level if official level not scored yet
                if (!$scoreRecord) {
                    $scoreRecord = $submission->levelScores->first();
                }

                if ($scoreRecord) {
                    $totalGoalsScore += (float) ($scoreRecord->goals_weighted_score ?? 0);
                    $totalCompetenciesScore += (float) ($scoreRecord->competency_weighted_score ?? 0);
                    $totalAnalyzed++;
                }
            }

            $goalsAvg = $totalAnalyzed > 0 ? round($totalGoalsScore / $totalAnalyzed, 2) : 0;
            $compAvg = $totalAnalyzed > 0 ? round($totalCompetenciesScore / $totalAnalyzed, 2) : 0;

            $chartData = [
                [
                    'category' => 'Goals (Results)',
                    'score' => $goalsAvg
                ],
                [
                    'category' => 'Competencies (Behavior)',
                    'score' => $compAvg
                ]
            ];

            return ApiResponse::success([
                'goals_average' => $goalsAvg,
                'competencies_average' => $compAvg,
                'total_evaluated' => $totalAnalyzed,
                'chart_data' => $chartData
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching goals vs competencies analytics');
        }
    }
}
