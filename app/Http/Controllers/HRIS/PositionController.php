<?php

namespace App\Http\Controllers\HRIS;

use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    /**
     * Display a listing of positions
     */
    public function index(Request $request)
    {
        $query = Position::with(['department', 'level', 'grade', 'reportsTo'])
            ->where('tenant_id', $request->user()->tenant_id);

        // Filter by department
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by level
        if ($request->has('level_id')) {
            $query->where('level_id', $request->level_id);
        }

        // Filter by grade
        if ($request->has('grade_id')) {
            $query->where('grade_id', $request->grade_id);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $positions = $query->get();

        return ApiResponse::success($positions);
    }

    /**
     * Store a newly created position
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'level_id' => 'nullable|exists:levels,id',
            'grade_id' => 'nullable|exists:grades,id',
            'code' => 'required|string|max:50|unique:positions,code',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
            'reports_to' => 'nullable|exists:positions,id',
            'required_qualifications' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['created_by'] = auth()->id();

        $position = Position::create($validated);

        return ApiResponse::created($position->load(['department', 'level', 'grade']), 'Position created successfully');
    }

    /**
     * Display the specified position
     */
    public function show(Request $request, $id)
    {
        $position = Position::with(['department', 'level', 'grade', 'reportsTo', 'subordinates'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        return ApiResponse::success($position);
    }

    /**
     * Update the specified position
     */
    public function update(Request $request, $id)
    {
        $position = Position::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'level_id' => 'nullable|exists:levels,id',
            'grade_id' => 'nullable|exists:grades,id',
            'code' => 'required|string|max:50|unique:positions,code,' . $id,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
            'reports_to' => 'nullable|exists:positions,id',
            'required_qualifications' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Prevent circular reporting
        if (isset($validated['reports_to']) && $validated['reports_to'] == $id) {
            return ApiResponse::error('A position cannot report to itself', 422);
        }

        $validated['updated_by'] = auth()->id();

        $position->update($validated);

        return ApiResponse::success($position->load(['department', 'level', 'grade']), 'Position updated successfully');
    }

    /**
     * Remove the specified position
     */
    public function destroy(Request $request, $id)
    {
        $position = Position::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        // Check if position has subordinates
        if ($position->subordinates()->count() > 0) {
            return ApiResponse::error('Cannot delete position that has subordinate positions', 422);
        }

        // Check if position has employees
        if ($position->employees()->count() > 0) {
            return ApiResponse::error('Cannot delete position that has employees assigned', 422);
        }

        $position->delete();

        return ApiResponse::success(null, 'Position deleted successfully');
    }
}
