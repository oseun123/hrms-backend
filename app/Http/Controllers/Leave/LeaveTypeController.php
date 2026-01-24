<?php

namespace App\Http\Controllers\Leave;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Leave\LeaveType;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveTypeController extends Controller
{
    use HandlesApiErrors;

    public function index()
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $leaveTypes = LeaveType::where('tenant_id', $tenantId)->get();
            return ApiResponse::success($leaveTypes);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave types');
        }
    }

    public function store(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:leave_types,code,NULL,id,tenant_id,' . $tenantId,
                'description' => 'nullable|string',
                'is_paid' => 'boolean',
                'is_active' => 'boolean',
                'requires_attachment' => 'boolean',
            ]);

            $leaveType = LeaveType::create(array_merge($validated, ['tenant_id' => $tenantId]));
            return ApiResponse::success($leaveType, 'Leave type created successfully', 201);
        } catch (\Exception $e) {
            return $this->handleException($e, 'creating leave type');
        }
    }

    public function show($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $leaveType = LeaveType::where('tenant_id', $tenantId)->findOrFail($id);
            return ApiResponse::success($leaveType);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave type');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $leaveType = LeaveType::where('tenant_id', $tenantId)->findOrFail($id);

            $validated = $request->validate([
                'name' => 'string|max:255',
                'code' => 'string|max:10|unique:leave_types,code,' . $id . ',id,tenant_id,' . $tenantId,
                'description' => 'nullable|string',
                'is_paid' => 'boolean',
                'is_active' => 'boolean',
                'requires_attachment' => 'boolean',
            ]);

            $leaveType->update($validated);
            return ApiResponse::success($leaveType, 'Leave type updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'updating leave type');
        }
    }

    public function destroy($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $leaveType = LeaveType::where('tenant_id', $tenantId)->findOrFail($id);

            if ($leaveType->is_seeded) {
                return ApiResponse::error('Seeded leave types cannot be deleted', 403);
            }

            $leaveType->delete();
            return ApiResponse::success(null, 'Leave type deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'deleting leave type');
        }
    }
}
