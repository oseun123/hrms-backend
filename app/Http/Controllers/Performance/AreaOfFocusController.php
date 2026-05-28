<?php

namespace App\Http\Controllers\Performance;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Performance\GoalAreaOfFocus;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AreaOfFocusController extends Controller
{
    use HandlesApiErrors;

    public function index()
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $areasOfFocus = GoalAreaOfFocus::where('tenant_id', $tenantId)->get();
            return ApiResponse::success($areasOfFocus);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching areas of focus');
        }
    }

    public function store(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            $areaOfFocus = GoalAreaOfFocus::create(array_merge($validated, [
                'tenant_id' => $tenantId,
                'is_seeded' => false,
            ]));

            return ApiResponse::success($areaOfFocus, 'Area of focus created successfully', 201);
        } catch (\Exception $e) {
            return $this->handleException($e, 'creating area of focus');
        }
    }

    public function show($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $areaOfFocus = GoalAreaOfFocus::where('tenant_id', $tenantId)->findOrFail($id);
            return ApiResponse::success($areaOfFocus);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching area of focus');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $areaOfFocus = GoalAreaOfFocus::where('tenant_id', $tenantId)->findOrFail($id);

            if ($areaOfFocus->is_seeded) {
                return ApiResponse::error('Seeded areas of focus cannot be edited', 403);
            }

            $validated = $request->validate([
                'name' => 'string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            $areaOfFocus->update($validated);
            return ApiResponse::success($areaOfFocus, 'Area of focus updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'updating area of focus');
        }
    }

    public function destroy($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $areaOfFocus = GoalAreaOfFocus::where('tenant_id', $tenantId)->findOrFail($id);

            if ($areaOfFocus->is_seeded) {
                return ApiResponse::error('Seeded areas of focus cannot be deleted', 403);
            }

            // Check if it has goals
            if ($areaOfFocus->goals()->count() > 0) {
                return ApiResponse::error('Cannot delete area of focus with existing goals', 422);
            }

            $areaOfFocus->delete();
            return ApiResponse::success(null, 'Area of focus deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'deleting area of focus');
        }
    }
}
