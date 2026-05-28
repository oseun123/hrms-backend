<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\Position;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    use HandlesApiErrors;

    /**
     * Display a listing of positions
     */
    public function index(Request $request)
    {
        try {
            $query = Position::with(['department', 'level', 'grade', 'reportsTo'])
                ->withCount('employees')
                ->where('tenant_id', $request->user()->tenant_id);

            // Filter by department
            if ($request->filled('department_id')) {
                $query->where('department_id', $request->department_id);
            }

            // Filter by level
            if ($request->filled('level_id')) {
                $query->where('level_id', $request->level_id);
            }

            // Filter by grade
            if ($request->filled('grade_id')) {
                $query->where('grade_id', $request->grade_id);
            }

            // Filter by active status
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
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

            if ($request->hasAny(['per_page', 'page'])) {
                $positions = $query->paginate($request->get('per_page', 15));
            } else {
                $positions = $query->get();
            }

            return ApiResponse::success($positions);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching positions');
        }
    }

    /**
     * Store a newly created position
     */
    public function store(Request $request)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $validated = $request->validate([
                'department_id' => 'required|exists:departments,id,tenant_id,' . $tenantId,
                'level_id' => 'nullable|exists:levels,id,tenant_id,' . $tenantId,
                'grade_id' => 'nullable|exists:grades,id,tenant_id,' . $tenantId,
                'code' => 'required|string|max:50|unique:positions,code',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'min_salary' => 'nullable|numeric|min:0',
                'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
                'reports_to' => 'nullable|exists:positions,id,tenant_id,' . $tenantId,
                'required_qualifications' => 'nullable|string',
                'responsibilities' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            $validated['tenant_id'] = $tenantId;
            $validated['created_by'] = auth()->id();

            $position = Position::create($validated);

            return ApiResponse::created($position->load(['department', 'level', 'grade']), 'Position created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Position creation');
        }
    }

    /**
     * Display the specified position
     */
    public function show(Request $request, $id)
    {
        try {
            $position = Position::with(['department', 'level', 'grade', 'reportsTo', 'subordinates'])
                ->where('tenant_id', $request->user()->tenant_id)
                ->findOrFail($id);

            // Fetch employees in this position with required relations
            $employees = \App\Models\Hris\Employee::whereHas('employmentDetails', function ($query) use ($id) {
                $query->where('position_id', $id);
            })->with(['user', 'employmentDetails.position', 'employmentDetails.department'])->get();

            $position->setRelation('employees', $employees);

            return ApiResponse::success($position);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching position');
        }
    }

    /**
     * Update the specified position
     */
    public function update(Request $request, $id)
    {
        try {
            $tenantId = $request->user()->tenant_id;
            $position = Position::where('tenant_id', $tenantId)
                ->findOrFail($id);

            $validated = $request->validate([
                'department_id' => 'required|exists:departments,id,tenant_id,' . $tenantId,
                'level_id' => 'nullable|exists:levels,id,tenant_id,' . $tenantId,
                'grade_id' => 'nullable|exists:grades,id,tenant_id,' . $tenantId,
                'code' => 'required|string|max:50|unique:positions,code,' . $id,
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'min_salary' => 'nullable|numeric|min:0',
                'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
                'reports_to' => 'nullable|exists:positions,id,tenant_id,' . $tenantId,
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
        } catch (\Exception $e) {
            return $this->handleException($e, 'Position update');
        }
    }

    /**
     * Remove the specified position
     */
    public function destroy(Request $request, $id)
    {
        try {
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
        } catch (\Exception $e) {
            return $this->handleException($e, 'Position deletion');
        }
    }
}
