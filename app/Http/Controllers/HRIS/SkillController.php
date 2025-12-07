<?php

namespace App\Http\Controllers\HRIS;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    /**
     * Display a listing of skills
     */
    public function index(Request $request)
    {
        $query = Skill::query()
            ->where('tenant_id', $request->user()->tenant_id);

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $skills = $query->get();

        return ApiResponse::success($skills);
    }

    /**
     * Store a newly created skill
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;

        $skill = Skill::create($validated);

        return ApiResponse::created($skill, 'Skill created successfully');
    }

    /**
     * Display the specified skill
     */
    public function show(Request $request, $id)
    {
        $skill = Skill::with('employees')
            ->where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        return ApiResponse::success($skill);
    }

    /**
     * Update the specified skill
     */
    public function update(Request $request, $id)
    {
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
    }

    /**
     * Remove the specified skill
     */
    public function destroy(Request $request, $id)
    {
        $skill = Skill::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        // Check if skill is assigned to employees
        if ($skill->employees()->count() > 0) {
            return ApiResponse::error('Cannot delete skill that is assigned to employees', 422);
        }

        $skill->delete();

        return ApiResponse::success(null, 'Skill deleted successfully');
    }
}
