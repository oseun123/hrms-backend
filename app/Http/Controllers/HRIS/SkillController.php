<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\Skill;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    use HandlesApiErrors;

    /**
     * Get unique skill categories
     */
    public function categories(Request $request)
    {
        try {
            $categories = Skill::where('tenant_id', $request->user()->tenant_id)
                ->distinct()
                ->pluck('category')
                ->filter() // Remove nulls
                ->values(); // Reset keys

            return ApiResponse::success($categories);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching skill categories');
        }
    }

    /**
     * Display a listing of skills
     */
    public function index(Request $request)
    {
        try {
            $query = Skill::withCount('employees')
                ->where('tenant_id', $request->user()->tenant_id);

            // Filter by category
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            // Filter by active status
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->hasAny(['per_page', 'page'])) {
                $skills = $query->paginate($request->get('per_page', 15));
            } else {
                $skills = $query->get();
            }

            return ApiResponse::success($skills);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching skills');
        }
    }

    /**
     * Store a newly created skill
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category' => 'nullable|string|max:100',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            $validated['tenant_id'] = $request->user()->tenant_id;

            $skill = Skill::create($validated);

            return ApiResponse::created($skill, 'Skill created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Skill creation');
        }
    }

    /**
     * Display the specified skill
     */
    public function show(Request $request, $id)
    {
        try {
            $skill = Skill::with('employees')
                ->where('tenant_id', $request->user()->tenant_id)
                ->findOrFail($id);

            return ApiResponse::success($skill);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Fetching skill');
        }
    }

    /**
     * Update the specified skill
     */
    public function update(Request $request, $id)
    {
        try {
            $skill = Skill::where('tenant_id', $request->user()->tenant_id)
                ->findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category' => 'nullable|string|max:100',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            $skill->update($validated);

            return ApiResponse::success($skill, 'Skill updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Updating skill');
        }
    }

    /**
     * Remove the specified skill
     */
    public function destroy(Request $request, $id)
    {
        try {
            $skill = Skill::where('tenant_id', $request->user()->tenant_id)
                ->findOrFail($id);

            // Check if skill is assigned to employees
            if ($skill->employees()->count() > 0) {
                return ApiResponse::error('Cannot delete skill that is assigned to employees', 422);
            }

            $skill->delete();

            return ApiResponse::success(null, 'Skill deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Skill deletion');
        }
    }
}
