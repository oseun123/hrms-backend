<?php

namespace App\Services;

use App\Models\Performance\AppraisalCompetencyScore;
use App\Models\Performance\AppraisalGoalScore;
use App\Models\Performance\AppraisalLevelScore;
use App\Models\Performance\AppraisalSubmission;
use App\Models\Performance\Competency;
use App\Models\Performance\EmployeeDeliverable;
use App\Models\Performance\PerformanceSetting;

class AppraisalScoringService
{
    /**
     * Calculate the aggregate goals score from individual measure scores
     *
     * @param int $levelScoreId
     * @return float
     */
    public function calculateGoalsScore($levelScoreId)
    {
        $goalScores = AppraisalGoalScore::where('level_score_id', $levelScoreId)
            ->with('measureTarget', 'employeeDeliverable.details')
            ->get();

        if ($goalScores->isEmpty()) {
            return 0;
        }

        $totalWeightedScore = 0;
        $totalWeightage = 0;

        foreach ($goalScores as $goalScore) {
            // Get the weightage for this measure/target
            $weightage = $goalScore->measureTarget->weightage ?? 0;

            // Check if there's a custom weightage in deliverable details
            if ($goalScore->employeeDeliverable) {
                $deliverableDetail = $goalScore->employeeDeliverable->details()
                    ->where('measure_target_id', $goalScore->measure_target_id)
                    ->first();

                if ($deliverableDetail && $deliverableDetail->custom_weightage) {
                    $weightage = $deliverableDetail->custom_weightage;
                }
            }

            $totalWeightedScore += ($goalScore->score * $weightage);
            $totalWeightage += $weightage;
        }

        // Avoid division by zero
        if ($totalWeightage == 0) {
            return 0;
        }

        // Return weighted average score
        return round($totalWeightedScore / $totalWeightage, 2);
    }

    /**
     * Calculate the aggregate competency score
     *
     * @param int $levelScoreId
     * @param int $tenantId
     * @return float
     */
    public function calculateCompetencyScore($levelScoreId, $tenantId)
    {
        $competencyScores = AppraisalCompetencyScore::where('level_score_id', $levelScoreId)
            ->with('competency')
            ->get();

        if ($competencyScores->isEmpty()) {
            return 0;
        }

        $totalWeightedScore = 0;
        $totalWeightage = 0;

        foreach ($competencyScores as $compScore) {
            $weightage = $compScore->competency->weightage ?? 0;
            $totalWeightedScore += ($compScore->score * $weightage);
            $totalWeightage += $weightage;
        }

        // Avoid division by zero
        if ($totalWeightage == 0) {
            return 0;
        }

        // Return weighted average score normalized to percentage (assuming 5-point scale)
        $averageScore = $totalWeightedScore / $totalWeightage;
        return round(($averageScore / 5) * 100, 2);
    }

    /**
     * Calculate the final score using the evaluation model
     *
     * @param float $goalsScore
     * @param float $competencyScore
     * @param int $tenantId
     * @return float
     */
    public function calculateFinalScore($submissionId, $goalsScore, $competencyScore)
    {
        $submission = AppraisalSubmission::findOrFail($submissionId);

        $resultsWeight = $submission->results_weight ?? 70;
        $competencyWeight = $submission->competency_weight ?? 30;

        // Final Score = (Goals Score × Results Weight%) + (Competency Score × Competency Weight%)
        $finalScore = ($goalsScore * ($resultsWeight / 100)) + ($competencyScore * ($competencyWeight / 100));

        return round($finalScore, 2);
    }

    /**
     * Update level score with calculated aggregates
     *
     * @param int $levelScoreId
     * @param int $tenantId
     * @return AppraisalLevelScore
     */
    public function updateLevelScoreAggregates($levelScoreId, $tenantId)
    {
        $levelScore = AppraisalLevelScore::with('submission')->findOrFail($levelScoreId);
        $submission = $levelScore->submission;

        $resultsWeight = $submission->results_weight ?? 70;
        $competencyWeight = $submission->competency_weight ?? 30;

        // Calculate raw percentage scores (0-100)
        $goalsScore = $this->calculateGoalsScore($levelScoreId);
        $competencyScore = $this->calculateCompetencyScore($levelScoreId, $tenantId);

        // Calculate weighted scores based on settings
        $goalsWeightedScore = round($goalsScore * ($resultsWeight / 100), 2);
        $competencyWeightedScore = round($competencyScore * ($competencyWeight / 100), 2);

        // Calculate final score (sum of weighted scores)
        $finalScore = round($goalsWeightedScore + $competencyWeightedScore, 2);

        $levelScore->update([
            'goals_score' => $goalsScore,                           // 100% version
            'goals_weighted_score' => $goalsWeightedScore,          // Converted version (e.g., 70%)
            'competency_score' => $competencyScore,                 // 100% version
            'competency_weighted_score' => $competencyWeightedScore, // Converted version (e.g., 30%)
            'final_score' => $finalScore,                           // Overall (sum of weighted)
        ]);

        return $levelScore;
    }

    /**
     * Get the official final score based on configured level
     *
     * @param AppraisalSubmission $submission
     * @return float|null
     */
    public function getOfficialFinalScore(AppraisalSubmission $submission)
    {
        $finalScoreLevel = $submission->final_score_level ?? 2;

        $levelScore = AppraisalLevelScore::where('submission_id', $submission->id)
            ->where('reviewer_level', $finalScoreLevel)
            ->first();

        return $levelScore->final_score ?? null;
    }

    /**
     * Validate that deliverable weightages sum to 100%
     *
     * @param int $employeeId
     * @param int $tenantId
     * @return bool
     */
    public function validateDeliverableWeightage($employeeId, $tenantId)
    {
        $deliverables = EmployeeDeliverable::where('employee_id', $employeeId)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('details.measureTarget')
            ->get();

        $totalWeightage = 0;

        foreach ($deliverables as $deliverable) {
            foreach ($deliverable->details as $detail) {
                // Use custom weightage if available, otherwise use default
                $weightage = $detail->custom_weightage ?? $detail->measureTarget->weightage ?? 0;
                $totalWeightage += $weightage;
            }
        }

        return abs($totalWeightage - 100) < 0.01; // Allow for small floating point differences
    }

    /**
     * Validate that competency weightages sum to 100%
     *
     * @param int $tenantId
     * @return bool
     */
    public function validateCompetencyWeightage($tenantId)
    {
        $competencies = Competency::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $totalWeightage = $competencies->sum('weightage');

        return abs($totalWeightage - 100) < 0.01; // Allow for small floating point differences
    }
}
