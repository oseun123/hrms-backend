<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\Grade;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    use HandlesApiErrors;

    /**
     * Display a listing of grades
     */
    public function index(Request $request)
    {
        try {
            $query = Grade::query()
                ->where('tenant_id', $request->user()->tenant_id);

            // Filter by active status
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Order by rank
            $query->orderBy('rank');

            $grades = $query->withCount('employees')->paginate($request->get('per_page', 25));

            return ApiResponse::success($grades);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching grades');
        }
    }

    /**
     * Store a newly created grade
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:grades,code',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'min_salary' => 'nullable|numeric|min:0',
                'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
                'rank' => 'required|integer',
                'is_active' => 'boolean',
            ]);

            $validated['tenant_id'] = $request->user()->tenant_id;
            $validated['created_by'] = auth()->id();

            $grade = Grade::create($validated);

            return ApiResponse::created($grade, 'Grade created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Grade creation');
        }
    }

    /**
     * Display the specified grade
     */
    public function show(Request $request, $id)
    {
        try {
            $grade = Grade::with('positions')
                ->where('tenant_id', $request->user()->tenant_id)
                ->findOrFail($id);

            // Fetch employees belonging to positions with this grade
            $employees = \App\Models\Hris\Employee::whereHas('employmentDetails.position', function ($query) use ($id) {
                $query->where('grade_id', $id);
            })->with(['user', 'employmentDetails.position', 'employmentDetails.department'])->get();

            $grade->setRelation('employees', $employees);

            return ApiResponse::success($grade);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching grade');
        }
    }

    /**
     * Update the specified grade
     */
    public function update(Request $request, $id)
    {
        try {
            $grade = Grade::where('tenant_id', $request->user()->tenant_id)
                ->findOrFail($id);

            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:grades,code,' . $id,
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'min_salary' => 'nullable|numeric|min:0',
                'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
                'rank' => 'required|integer',
                'is_active' => 'boolean',
            ]);

            $validated['updated_by'] = auth()->id();

            $grade->update($validated);

            return ApiResponse::success($grade, 'Grade updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Grade update');
        }
    }

    /**
     * Remove the specified grade
     */
    public function destroy(Request $request, $id)
    {
        try {
            $grade = Grade::where('tenant_id', $request->user()->tenant_id)
                ->findOrFail($id);

            // Check if grade is assigned to positions
            if ($grade->positions()->count() > 0) {
                return ApiResponse::error('Cannot delete grade that is assigned to positions', 422);
            }

            $grade->delete();

            return ApiResponse::success(null, 'Grade deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Grade deletion');
        }
    }
}
