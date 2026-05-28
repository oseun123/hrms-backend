<?php

namespace App\Http\Controllers\Leave;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Leave\LeaveWorkflow;
use App\Models\Leave\LeaveWorkflowLevel;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaveWorkflowController extends Controller
{
    use HandlesApiErrors;

    public function index()
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $workflows = LeaveWorkflow::with('levels.specificApprover')
                ->where('tenant_id', $tenantId)
                ->get();
            return ApiResponse::success($workflows);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave workflows');
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
                'levels' => 'required|array|min:1',
                'levels.*.level' => 'required|integer|min:1',
                'levels.*.approver_type' => 'required|string|in:manager,team_lead,secondary_manager,hr,specific_employee',
                'levels.*.approver_id' => 'required_if:levels.*.approver_type,specific_employee|nullable|exists:employees,id,tenant_id,' . $tenantId,
            ]);

            DB::beginTransaction();

            $workflow = LeaveWorkflow::create([
                'tenant_id' => $tenantId,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['levels'] as $levelData) {
                LeaveWorkflowLevel::create([
                    'tenant_id' => $tenantId,
                    'leave_workflow_id' => $workflow->id,
                    'level' => $levelData['level'],
                    'approver_type' => $levelData['approver_type'],
                    'approver_id' => $levelData['approver_id'] ?? null,
                ]);
            }

            DB::commit();

            return ApiResponse::success($workflow->load('levels'), 'Leave workflow created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'creating leave workflow');
        }
    }

    public function show($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $workflow = LeaveWorkflow::with('levels.specificApprover')
                ->where('tenant_id', $tenantId)
                ->findOrFail($id);
            return ApiResponse::success($workflow);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave workflow');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $workflow = LeaveWorkflow::where('tenant_id', $tenantId)->findOrFail($id);

            $validated = $request->validate([
                'name' => 'string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
                'levels' => 'array',
                'levels.*.level' => 'required|integer|min:1',
                'levels.*.approver_type' => 'required|string|in:manager,team_lead,secondary_manager,hr,specific_employee',
                'levels.*.approver_id' => 'required_if:levels.*.approver_type,specific_employee|nullable|exists:employees,id,tenant_id,' . $tenantId,
            ]);

            DB::beginTransaction();

            $workflow->update($validated);

            if (isset($validated['levels'])) {
                // Delete existing levels and recreate
                $workflow->levels()->delete();
                foreach ($validated['levels'] as $levelData) {
                    LeaveWorkflowLevel::create([
                        'tenant_id' => $tenantId,
                        'leave_workflow_id' => $workflow->id,
                        'level' => $levelData['level'],
                        'approver_type' => $levelData['approver_type'],
                        'approver_id' => $levelData['approver_id'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return ApiResponse::success($workflow->load('levels'), 'Leave workflow updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'updating leave workflow');
        }
    }

    public function destroy($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $workflow = LeaveWorkflow::where('tenant_id', $tenantId)->findOrFail($id);

            // Check if workflow is in use
            if ($workflow->policies()->exists()) {
                return ApiResponse::error('Cannot delete workflow as it is currently assigned to one or more leave policies', 400);
            }

            $workflow->delete();
            return ApiResponse::success(null, 'Leave workflow deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'deleting leave workflow');
        }
    }
}
