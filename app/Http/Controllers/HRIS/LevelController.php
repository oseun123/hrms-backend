<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\Level;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;

class LevelController extends Controller
{
    use HandlesApiErrors;

    /**
     * Display a listing of levels
     */
    public function index(Request $request)
    {
        try {
            $query = Level::query()
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

            $levels = $query->withCount('employees')->paginate($request->get('per_page', 25));

            return ApiResponse::success($levels);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching levels');
        }
    }

    /**
     * Store a newly created level
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:levels,code',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'rank' => 'required|integer',
                'is_active' => 'boolean',
            ]);

            $validated['tenant_id'] = $request->user()->tenant_id;
            $validated['created_by'] = auth()->id();

            $level = Level::create($validated);

            return ApiResponse::created($level, 'Level created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Level creation');
        }
    }

    /**
     * Display the specified level
     */
    public function show(Request $request, $id)
    {
        try {
            $level = Level::with('positions')
                ->where('tenant_id', $request->user()->tenant_id)
                ->findOrFail($id);

            // Fetch employees belonging to positions with this level
            $employees = \App\Models\Hris\Employee::whereHas('employmentDetails.position', function ($query) use ($id) {
                $query->where('level_id', $id);
            })->with(['user', 'employmentDetails.position', 'employmentDetails.department'])->get();

            $level->setRelation('employees', $employees);

            return ApiResponse::success($level);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching level');
        }
    }

    /**
     * Update the specified level
     */
    public function update(Request $request, $id)
    {
        try {
            $level = Level::where('tenant_id', $request->user()->tenant_id)
                ->findOrFail($id);

            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:levels,code,' . $id,
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'rank' => 'required|integer',
                'is_active' => 'boolean',
            ]);

            $validated['updated_by'] = auth()->id();

            $level->update($validated);

            return ApiResponse::success($level, 'Level updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Level update');
        }
    }

    /**
     * Remove the specified level
     */
    public function destroy(Request $request, $id)
    {
        try {
            $level = Level::where('tenant_id', $request->user()->tenant_id)
                ->findOrFail($id);

            // Check if level is assigned to positions
            if ($level->positions()->count() > 0) {
                return ApiResponse::error('Cannot delete level that is assigned to positions', 422);
            }

            $level->delete();

            return ApiResponse::success(null, 'Level deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Level deletion');
        }
    }
}
