<?php

namespace App\Http\Controllers\Performance;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Performance\PerformanceGoal;
use App\Models\Performance\PerformanceObjective;
use App\Models\Performance\PerformanceMeasureTarget;
use App\Imports\Performance\GoalImport;
use App\Exports\Performance\SimpleGoalTemplateExport;
use App\Exports\Performance\ComplexGoalTemplateExport;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class GoalController extends Controller
{
    use HandlesApiErrors;

    public function index()
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $goals = PerformanceGoal::where('tenant_id', $tenantId)
                ->with(['areaOfFocus', 'objectives.measuresTargets', 'measuresTargets'])
                ->get();
            return ApiResponse::success($goals);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching goals');
        }
    }

    public function store(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $validated = $request->validate([
                'area_of_focus_id' => 'required|exists:goal_areas_of_focus,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'goal_type' => 'required|in:simple,complex',
                'is_active' => 'boolean',
                // For simple goals
                'measures' => 'required_if:goal_type,simple|array',
                'measures.*.measure_description' => 'required|string',
                'measures.*.target_description' => 'required|string',
                'measures.*.weightage' => 'required|numeric|min:0|max:100',
                'measures.*.uom' => 'nullable|string',
                // For complex goals
                'objectives' => 'required_if:goal_type,complex|array',
                'objectives.*.title' => 'required|string',
                'objectives.*.description' => 'nullable|string',
                'objectives.*.measures' => 'required|array',
                'objectives.*.measures.*.measure_description' => 'required|string',
                'objectives.*.measures.*.target_description' => 'required|string',
                'objectives.*.measures.*.weightage' => 'required|numeric|min:0|max:100',
                'objectives.*.measures.*.uom' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $goal = PerformanceGoal::create([
                'tenant_id' => $tenantId,
                'area_of_focus_id' => $validated['area_of_focus_id'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'goal_type' => $validated['goal_type'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            if ($validated['goal_type'] === 'simple') {
                // Create measures directly for the goal
                foreach ($validated['measures'] as $measureData) {
                    PerformanceMeasureTarget::create([
                        'tenant_id' => $tenantId,
                        'measurable_type' => PerformanceGoal::class,
                        'measurable_id' => $goal->id,
                        'measure_description' => $measureData['measure_description'],
                        'target_description' => $measureData['target_description'],
                        'weightage' => $measureData['weightage'],
                        'uom' => $measureData['uom'] ?? null,
                    ]);
                }
            } else {
                // Create objectives and their measures
                foreach ($validated['objectives'] as $index => $objectiveData) {
                    $objective = PerformanceObjective::create([
                        'tenant_id' => $tenantId,
                        'goal_id' => $goal->id,
                        'title' => $objectiveData['title'],
                        'description' => $objectiveData['description'] ?? null,
                        'sequence_order' => $index + 1,
                    ]);

                    foreach ($objectiveData['measures'] as $measureData) {
                        PerformanceMeasureTarget::create([
                            'tenant_id' => $tenantId,
                            'measurable_type' => PerformanceObjective::class,
                            'measurable_id' => $objective->id,
                            'measure_description' => $measureData['measure_description'],
                            'target_description' => $measureData['target_description'],
                            'weightage' => $measureData['weightage'],
                            'uom' => $measureData['uom'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            return ApiResponse::success(
                $goal->load(['objectives.measuresTargets', 'measuresTargets']),
                'Goal created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'creating goal');
        }
    }

    public function show($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $goal = PerformanceGoal::where('tenant_id', $tenantId)
                ->with(['areaOfFocus', 'objectives.measuresTargets', 'measuresTargets'])
                ->findOrFail($id);
            return ApiResponse::success($goal);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching goal');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $goal = PerformanceGoal::where('tenant_id', $tenantId)->findOrFail($id);

            $validated = $request->validate([
                'area_of_focus_id' => 'exists:goal_areas_of_focus,id',
                'title' => 'string|max:255',
                'description' => 'nullable|string',
                'goal_type' => 'required|in:simple,complex',
                'is_active' => 'boolean',

                // Simple Goals
                'measures' => 'required_if:goal_type,simple|array',
                'measures.*.id' => 'nullable|integer',
                'measures.*.measure_description' => 'required_if:goal_type,simple|string',
                'measures.*.target_description' => 'required_if:goal_type,simple|string',
                'measures.*.weightage' => 'required_if:goal_type,simple|numeric|min:0|max:100',
                'measures.*.uom' => 'nullable|string',

                // Complex Goals
                'objectives' => 'required_if:goal_type,complex|array',
                'objectives.*.id' => 'nullable|integer',
                'objectives.*.title' => 'required_if:goal_type,complex|string',
                'objectives.*.description' => 'nullable|string',
                'objectives.*.measures' => 'required_if:goal_type,complex|array',
                'objectives.*.measures.*.id' => 'nullable|integer',
                'objectives.*.measures.*.measure_description' => 'required_if:goal_type,complex|string',
                'objectives.*.measures.*.target_description' => 'required_if:goal_type,complex|string',
                'objectives.*.measures.*.weightage' => 'required_if:goal_type,complex|numeric|min:0|max:100',
                'objectives.*.measures.*.uom' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $goal->update([
                'area_of_focus_id' => $validated['area_of_focus_id'] ?? $goal->area_of_focus_id,
                'title' => $validated['title'] ?? $goal->title,
                'description' => $validated['description'] ?? $goal->description,
                'goal_type' => $validated['goal_type'],
                'is_active' => $validated['is_active'] ?? $goal->is_active,
            ]);

            if ($validated['goal_type'] === 'simple') {
                // Handle Simple Goal Measures
                $existingMeasureIds = $goal->measuresTargets()->pluck('id')->toArray();
                $processedMeasureIds = [];

                foreach ($validated['measures'] as $measureData) {
                    if (isset($measureData['id']) && in_array($measureData['id'], $existingMeasureIds)) {
                        // Update existing
                        PerformanceMeasureTarget::where('id', $measureData['id'])->update([
                            'measure_description' => $measureData['measure_description'],
                            'target_description' => $measureData['target_description'],
                            'weightage' => $measureData['weightage'],
                            'uom' => $measureData['uom'] ?? null,
                        ]);
                        $processedMeasureIds[] = $measureData['id'];
                    } else {
                        // Create new
                        $newMeasure = PerformanceMeasureTarget::create([
                            'tenant_id' => $tenantId,
                            'measurable_type' => PerformanceGoal::class,
                            'measurable_id' => $goal->id,
                            'measure_description' => $measureData['measure_description'],
                            'target_description' => $measureData['target_description'],
                            'weightage' => $measureData['weightage'],
                            'uom' => $measureData['uom'] ?? null,
                        ]);
                        $processedMeasureIds[] = $newMeasure->id;
                    }
                }

                // Delete removed measures
                $measuresToDelete = array_diff($existingMeasureIds, $processedMeasureIds);
                if (!empty($measuresToDelete)) {
                    PerformanceMeasureTarget::whereIn('id', $measuresToDelete)->delete();
                }
            } else {
                // Handle Complex Goal Objectives
                $existingObjectiveIds = $goal->objectives()->pluck('id')->toArray();
                $processedObjectiveIds = [];

                foreach ($validated['objectives'] as $index => $objectiveData) {
                    $objectiveId = $objectiveData['id'] ?? null;
                    $objective = null;

                    if ($objectiveId && in_array($objectiveId, $existingObjectiveIds)) {
                        // Update Objective
                        $objective = PerformanceObjective::find($objectiveId);
                        $objective->update([
                            'title' => $objectiveData['title'],
                            'description' => $objectiveData['description'] ?? null,
                            'sequence_order' => $index + 1,
                        ]);
                        $processedObjectiveIds[] = $objectiveId;
                    } else {
                        // Create Objective
                        $objective = PerformanceObjective::create([
                            'tenant_id' => $tenantId,
                            'goal_id' => $goal->id,
                            'title' => $objectiveData['title'],
                            'description' => $objectiveData['description'] ?? null,
                            'sequence_order' => $index + 1,
                        ]);
                        $processedObjectiveIds[] = $objective->id;
                    }

                    // Handle Measures for this Objective
                    // Note: If objective is new, all measures are new. If objective exists, some might be updates.
                    $objMeasuresData = $objectiveData['measures'] ?? [];

                    // If the objective was just created, it has no existing measures.
                    // If it was updated, we need to check its existing measures.
                    $existingObjMeasureIds = $objectiveId ? $objective->measuresTargets()->pluck('id')->toArray() : [];
                    $processedObjMeasureIds = [];

                    foreach ($objMeasuresData as $measureData) {
                        if (isset($measureData['id']) && in_array($measureData['id'], $existingObjMeasureIds)) {
                            PerformanceMeasureTarget::where('id', $measureData['id'])->update([
                                'measure_description' => $measureData['measure_description'],
                                'target_description' => $measureData['target_description'],
                                'weightage' => $measureData['weightage'],
                                'uom' => $measureData['uom'] ?? null,
                            ]);
                            $processedObjMeasureIds[] = $measureData['id'];
                        } else {
                            $newMeasure = PerformanceMeasureTarget::create([
                                'tenant_id' => $tenantId,
                                'measurable_type' => PerformanceObjective::class,
                                'measurable_id' => $objective->id,
                                'measure_description' => $measureData['measure_description'],
                                'target_description' => $measureData['target_description'],
                                'weightage' => $measureData['weightage'],
                                'uom' => $measureData['uom'] ?? null,
                            ]);
                            $processedObjMeasureIds[] = $newMeasure->id;
                        }
                    }

                    // Delete removed measures for this objective
                    $objMeasuresToDelete = array_diff($existingObjMeasureIds, $processedObjMeasureIds);
                    if (!empty($objMeasuresToDelete)) {
                        PerformanceMeasureTarget::whereIn('id', $objMeasuresToDelete)->delete();
                    }
                }

                // Delete removed objectives
                $objectivesToDelete = array_diff($existingObjectiveIds, $processedObjectiveIds);
                if (!empty($objectivesToDelete)) {
                    // Manually delete measures of deleted objectives to be safe
                    // Assuming we can find them by measurable_id/type
                    $deletedObjectives = PerformanceObjective::whereIn('id', $objectivesToDelete)->get();
                    foreach ($deletedObjectives as $delObj) {
                        $delObj->measuresTargets()->delete();
                    }
                    PerformanceObjective::whereIn('id', $objectivesToDelete)->delete();
                }
            }

            DB::commit();
            return ApiResponse::success($goal->load(['objectives.measuresTargets', 'measuresTargets']), 'Goal updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'updating goal');
        }
    }

    public function destroy($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $goal = PerformanceGoal::where('tenant_id', $tenantId)->findOrFail($id);

            // Check if goal is assigned to employees
            if ($goal->employeeDeliverables()->count() > 0) {
                return ApiResponse::error('Cannot delete goal that is assigned to employees', 422);
            }

            $goal->delete();
            return ApiResponse::success(null, 'Goal deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'deleting goal');
        }
    }

    /**
     * Download the goal import template.
     */
    public function downloadSimpleTemplate()
    {
        return Excel::download(new SimpleGoalTemplateExport, 'simple_goal_template.xlsx');
    }

    public function downloadComplexTemplate()
    {
        return Excel::download(new ComplexGoalTemplateExport, 'complex_goal_template.xlsx');
    }

    /**
     * Import goals from an Excel file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new GoalImport();
            Excel::import($import, $request->file('file'));

            return ApiResponse::success([
                'summary' => [
                    'total' => $import->getTotalRows(),
                    'success' => $import->getSuccessCount(),
                    'failed' => $import->getFailedCount(),
                ],
                'errors' => $import->getErrors(),
            ], 'Import processed successfully');
        } catch (\Exception $e) {
            Log::error('Goal bulk import failed: ' . $e->getMessage());
            return $this->handleException($e, 'importing goals');
        }
    }

    /**
     * Duplicate an existing goal.
     */
    public function duplicate($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $original = PerformanceGoal::where('tenant_id', $tenantId)
                ->with(['objectives.measuresTargets', 'measuresTargets'])
                ->findOrFail($id);

            DB::beginTransaction();

            // 1. Clone Goal
            $newGoal = $original->replicate();
            $newGoal->title = $original->title . ' (Copy)';
            $newGoal->save();

            // 2. Clone Direct Measures (for simple goals)
            foreach ($original->measuresTargets as $measure) {
                $newMeasure = $measure->replicate();
                $newMeasure->measurable_id = $newGoal->id;
                $newMeasure->save();
            }

            // 3. Clone Objectives and their Measures (for complex goals)
            foreach ($original->objectives as $objective) {
                $newObjective = $objective->replicate();
                $newObjective->goal_id = $newGoal->id;
                $newObjective->save();

                foreach ($objective->measuresTargets as $measure) {
                    $newMeasure = $measure->replicate();
                    $newMeasure->measurable_id = $newObjective->id;
                    $newMeasure->save();
                }
            }

            DB::commit();

            return ApiResponse::success(
                $newGoal->load(['areaOfFocus', 'objectives.measuresTargets', 'measuresTargets']),
                'Goal duplicated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'duplicating goal');
        }
    }
    public function bulkDestroy(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:performance_goals,id',
            ]);

            $tenantId = Auth::user()->tenant_id;
            PerformanceGoal::where('tenant_id', $tenantId)
                ->whereIn('id', $request->ids)
                ->delete();

            return ApiResponse::success(null, 'Selected goals deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'bulk deleting goals');
        }
    }
}
