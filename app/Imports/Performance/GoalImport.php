<?php

namespace App\Imports\Performance;

use App\Models\Performance\PerformanceGoal;
use App\Models\Performance\PerformanceObjective;
use App\Models\Performance\PerformanceMeasureTarget;
use App\Models\Performance\GoalAreaOfFocus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GoalImport implements ToCollection, WithHeadingRow
{
    private $totalRows = 0;
    private $successCount = 0;
    private $failedCount = 0;
    private $errors = [];

    // State tracking during import
    private $currentGoal = null;
    private $currentObjective = null;

    public function collection(Collection $rows)
    {
        $tenantId = Auth::user()->tenant_id;
        $createdBy = Auth::user()->id;

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                // Skip essentially empty rows
                $rowArray = $row->toArray();
                if (empty(array_filter($rowArray))) {
                    continue;
                }

                $this->totalRows++;

                try {
                    $this->processRow($row, $tenantId, $createdBy, $rowNumber);
                    $this->successCount++;
                } catch (\Exception $e) {
                    $this->failedCount++;
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'error' => $e->getMessage()
                    ];
                    Log::warning("Goal Import error at row {$rowNumber}: " . $e->getMessage());
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Goal Import catastrophic failure: ' . $e->getMessage());
            $this->errors[] = ['row' => 'Global', 'error' => $e->getMessage()];
        }
    }

    private function processRow($row, $tenantId, $createdBy, $rowNumber)
    {
        // 1. Resolve Goal
        if (!empty($row['goal_title'])) {
            // Check if it matches the current goal to prevent duplicates (grouping)
            if ($this->currentGoal && $this->currentGoal->title === $row['goal_title']) {
                // Same goal, do nothing (reuse $this->currentGoal)
            } else {
                // New Goal
                $areaId = $this->resolveArea($row['area_of_focus'], $tenantId);

                $this->currentGoal = PerformanceGoal::create([
                    'tenant_id' => $tenantId,
                    'area_of_focus_id' => $areaId,
                    'title' => $row['goal_title'],
                    'description' => $row['description'] ?? null,
                    'goal_type' => strtolower($row['structure_type'] ?? 'simple'),
                    'is_active' => true,
                ]);
                // successCount is incremented in the main loop

                $this->currentObjective = null; // Reset objective for new goal
            }
        } elseif (!$this->currentGoal) {
            // No goal title and no current goal (first row empty?)
            throw new \Exception("Goal Title is missing for row {$rowNumber}. A goal must be defined first.");
        }

        // 2. Resolve Objective (for complex goals)
        if ($this->currentGoal->goal_type === 'complex') {
            if (!empty($row['objective_title'])) {
                // Check if matches current objective
                if ($this->currentObjective && $this->currentObjective->title === $row['objective_title']) {
                    // Reuse current objective
                } else {
                    $this->currentObjective = PerformanceObjective::create([
                        'tenant_id' => $tenantId,
                        'goal_id' => $this->currentGoal->id,
                        'title' => $row['objective_title'],
                        'description' => $row['objective_description'] ?? null,
                    ]);
                }
            }
            // If objective title is empty, we keep the previous $this->currentObjective (Excel merge behavior)
            // UNLESS we just switched goals. (Handled by reset above)
        }

        // 3. Create Measure
        if (!empty($row['measure_description'])) {
            $measurable = ($this->currentGoal->goal_type === 'complex' && $this->currentObjective)
                ? $this->currentObjective
                : $this->currentGoal;

            PerformanceMeasureTarget::create([
                'tenant_id' => $tenantId,
                'measurable_type' => get_class($measurable),
                'measurable_id' => $measurable->id,
                'measure_description' => $row['measure_description'],
                'target_description' => $row['target_description'] ?? 'N/A',
                'uom' => $row['unit_of_measure_uom'] ?? null,
                'weightage' => (float)($row['weightage'] ?? 100),
            ]);
        }
    }

    private function resolveArea($name, $tenantId)
    {
        if (empty($name)) return null;

        $area = GoalAreaOfFocus::where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        if (!$area) {
            $area = GoalAreaOfFocus::create([
                'tenant_id' => $tenantId,
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name),
            ]);
        }

        return $area->id;
    }

    public function getTotalRows()
    {
        return $this->totalRows;
    }
    public function getSuccessCount()
    {
        return $this->successCount;
    }
    public function getFailedCount()
    {
        return $this->failedCount;
    }
    public function getErrors()
    {
        return $this->errors;
    }
}
