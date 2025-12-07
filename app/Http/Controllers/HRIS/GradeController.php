<?php

namespace App\Http\Controllers\HRIS;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    /**
     * Display a listing of grades
     */
    public function index(Request $request)
    {
        $query = Grade::query()
            ->where('tenant_id', $request->user()->tenant_id);

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
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

        $grades = $query->get();

        return ApiResponse::success($grades);
    }

    /**
     * Store a newly created grade
     */
    public function store(Request $request)
    {
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
    }

    /**
     * Display the specified grade
     */
    public function show(Request $request, $id)
    {
        $grade = Grade::with('positions')
            ->where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        return ApiResponse::success($grade);
    }

    /**
     * Update the specified grade
     */
    public function update(Request $request, $id)
    {
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
    }

    /**
     * Remove the specified grade
     */
    public function destroy(Request $request, $id)
    {
        $grade = Grade::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        // Check if grade is assigned to positions
        if ($grade->positions()->count() > 0) {
            return ApiResponse::error('Cannot delete grade that is assigned to positions', 422);
        }

        $grade->delete();

        return ApiResponse::success(null, 'Grade deleted successfully');
    }
}
