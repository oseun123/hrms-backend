<?php

namespace App\Http\Controllers\Performance;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Performance\Competency;
use App\Services\AppraisalScoringService;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompetencyController extends Controller
{
    use HandlesApiErrors;

    protected $scoringService;

    public function __construct(AppraisalScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }

    public function index()
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $competencies = Competency::where('tenant_id', $tenantId)->get();

            $totalWeightage = $competencies->sum('weightage');

            return ApiResponse::success([
                'competencies' => $competencies,
                'total_weightage' => round($totalWeightage, 2),
                'is_valid' => abs($totalWeightage - 100) < 0.01,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching competencies');
        }
    }

    public function store(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'weightage' => 'required|numeric|min:0|max:100',
                'is_active' => 'boolean',
            ]);

            $competency = Competency::create(array_merge($validated, [
                'tenant_id' => $tenantId,
                'is_seeded' => false,
            ]));

            return ApiResponse::success($competency, 'Competency created successfully', 201);
        } catch (\Exception $e) {
            return $this->handleException($e, 'creating competency');
        }
    }

    public function show($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $competency = Competency::where('tenant_id', $tenantId)->findOrFail($id);
            return ApiResponse::success($competency);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching competency');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $competency = Competency::where('tenant_id', $tenantId)->findOrFail($id);

            if ($competency->is_seeded && $request->has(['name', 'description'])) {
                return ApiResponse::error('Seeded competency name and description cannot be edited', 403);
            }

            $validated = $request->validate([
                'name' => 'string|max:255',
                'description' => 'nullable|string',
                'weightage' => 'numeric|min:0|max:100',
                'is_active' => 'boolean',
            ]);

            $competency->update($validated);
            return ApiResponse::success($competency, 'Competency updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'updating competency');
        }
    }

    public function updateBulkWeightages(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $validated = $request->validate([
                'competencies' => 'required|array',
                'competencies.*.id' => 'required|exists:competencies,id',
                'competencies.*.weightage' => 'required|numeric|min:0|max:100',
            ]);

            // Validate that weightages sum to 100
            $totalWeightage = collect($validated['competencies'])->sum('weightage');
            if (abs($totalWeightage - 100) > 0.01) {
                return ApiResponse::error('Total weightage must equal 100%. Current total: ' . round($totalWeightage, 2) . '%', 422);
            }

            // Update each competency
            foreach ($validated['competencies'] as $competencyData) {
                Competency::where('tenant_id', $tenantId)
                    ->where('id', $competencyData['id'])
                    ->update(['weightage' => $competencyData['weightage']]);
            }

            $competencies = Competency::where('tenant_id', $tenantId)->get();
            return ApiResponse::success($competencies, 'Competency weightages updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'updating competency weightages');
        }
    }

    public function destroy($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $competency = Competency::where('tenant_id', $tenantId)->findOrFail($id);

            if ($competency->is_seeded) {
                return ApiResponse::error('Seeded competencies cannot be deleted', 403);
            }

            $competency->delete();
            return ApiResponse::success(null, 'Competency deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'deleting competency');
        }
    }
}
