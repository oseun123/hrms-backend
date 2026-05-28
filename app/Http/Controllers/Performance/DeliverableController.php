<?php

namespace App\Http\Controllers\Performance;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\Employee;
use App\Models\Performance\EmployeeDeliverable;
use App\Services\DeliverableActivationService;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliverableController extends Controller
{
    use HandlesApiErrors;

    protected $deliverableService;

    public function __construct(DeliverableActivationService $deliverableService)
    {
        $this->deliverableService = $deliverableService;
    }

    /**
     * Get all deliverables (admin view - can select any employee)
     */
    public function index(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $query = EmployeeDeliverable::where('tenant_id', $tenantId)
                ->with(['employee', 'goal.areaOfFocus', 'details.measureTarget', 'assignedByUser']);

            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            $deliverables = $query->get();
            return ApiResponse::success($deliverables);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching deliverables');
        }
    }

    /**
     * Get team deliverables (manager view - auto-loads team members)
     */
    public function getTeamDeliverables()
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $userId = Auth::id();

            // Get current user's employee record
            $employee = Employee::where('user_id', $userId)->where('tenant_id', $tenantId)->first();
            if (!$employee) {
                return ApiResponse::error('Employee record not found', 404);
            }

            // Get team members
            $teamMembers = $this->deliverableService->getTeamMembers($employee->id, $tenantId);
            $teamMemberIds = $teamMembers->pluck('id')->toArray();

            // Get deliverables for team members
            $deliverables = EmployeeDeliverable::where('tenant_id', $tenantId)
                ->whereIn('employee_id', $teamMemberIds)
                ->with(['employee', 'goal.areaOfFocus', 'details.measureTarget'])
                ->get();

            return ApiResponse::success([
                'team_members' => $teamMembers,
                'deliverables' => $deliverables,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching team deliverables');
        }
    }

    /**
     * Get my deliverables (employee view)
     */
    public function getMyDeliverables()
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $userId = Auth::id();

            $employee = Employee::where('user_id', $userId)->where('tenant_id', $tenantId)->first();
            if (!$employee) {
                return ApiResponse::error('Employee record not found', 404);
            }

            $deliverables = EmployeeDeliverable::where('tenant_id', $tenantId)
                ->where('employee_id', $employee->id)
                ->with(['goal.areaOfFocus', 'details.measureTarget'])
                ->get();

            return ApiResponse::success($deliverables);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching my deliverables');
        }
    }

    /**
     * Assign goal to employee(s)
     */
    public function assign(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $userId = Auth::id();

            $validated = $request->validate([
                'employee_ids' => 'required|array',
                'employee_ids.*' => 'required|exists:employees,id',
                'goal_id' => 'required|exists:performance_goals,id',
                'measure_target_ids' => 'nullable|array',
                'measure_target_ids.*' => 'exists:performance_measures_targets,id',
            ]);

            $results = [];
            foreach ($validated['employee_ids'] as $employeeId) {
                $deliverable = $this->deliverableService->assignGoalToEmployee(
                    $employeeId,
                    $validated['goal_id'],
                    $tenantId,
                    $userId,
                    $validated['measure_target_ids'] ?? []
                );
                $results[] = $deliverable;
            }

            return ApiResponse::success($results, 'Goal(s) assigned successfully', 201);
        } catch (\Exception $e) {
            return $this->handleException($e, 'assigning goal');
        }
    }

    /**
     * Activate deliverables
     */
    public function activate(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $userId = Auth::id();

            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'deliverable_ids' => 'required|array',
                'deliverable_ids.*' => 'exists:employee_deliverables,id',
            ]);

            $result = $this->deliverableService->activateDeliverables(
                $validated['employee_id'],
                $tenantId,
                $validated['deliverable_ids'],
                $userId
            );

            if ($result['success']) {
                return ApiResponse::success($result, $result['message']);
            } else {
                return ApiResponse::error($result['message'], 422);
            }
        } catch (\Exception $e) {
            return $this->handleException($e, 'activating deliverables');
        }
    }

    /**
     * Deactivate deliverables
     */
    public function deactivate(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $userId = Auth::id();

            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'deliverable_ids' => 'required|array',
                'deliverable_ids.*' => 'exists:employee_deliverables,id',
            ]);

            $count = EmployeeDeliverable::where('tenant_id', $tenantId)
                ->where('employee_id', $validated['employee_id'])
                ->whereIn('id', $validated['deliverable_ids'])
                ->update([
                    'is_active' => false,
                    'activated_at' => null,
                    'activated_by' => null
                ]);

            return ApiResponse::success(['count' => $count], "Successfully deactivated $count deliverable(s).");
        } catch (\Exception $e) {
            return $this->handleException($e, 'deactivating deliverables');
        }
    }

    /**
     * Check if deliverables can be activated
     */
    public function checkActivation(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'deliverable_ids' => 'nullable|array',
                'deliverable_ids.*' => 'exists:employee_deliverables,id',
            ]);

            $result = $this->deliverableService->canActivateDeliverables(
                $validated['employee_id'],
                $tenantId,
                $validated['deliverable_ids'] ?? []
            );

            return ApiResponse::success($result);
        } catch (\Exception $e) {
            return $this->handleException($e, 'checking activation');
        }
    }

    /**
     * Delete deliverable
     */
    public function destroy($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $deliverable = EmployeeDeliverable::where('tenant_id', $tenantId)->findOrFail($id);

            if ($deliverable->is_active) {
                return ApiResponse::error('Cannot delete active deliverable. Deactivate it first.', 422);
            }

            $deliverable->delete();
            return ApiResponse::success(null, 'Deliverable deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'deleting deliverable');
        }
    }

    /**
     * Bulk delete deliverables
     */
    public function bulkDestroy(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:employee_deliverables,id',
            ]);

            $deliverables = EmployeeDeliverable::where('tenant_id', $tenantId)
                ->whereIn('id', $validated['ids'])
                ->get();

            $deletedCount = 0;
            $failedCount = 0;

            foreach ($deliverables as $deliverable) {
                if ($deliverable->is_active) {
                    $failedCount++;
                    continue;
                }
                $deliverable->delete();
                $deletedCount++;
            }

            if ($deletedCount === 0 && $failedCount > 0) {
                return ApiResponse::error('Cannot delete active deliverables. Deactivate them first.', 422);
            }

            return ApiResponse::success([
                'deleted' => $deletedCount,
                'failed' => $failedCount
            ], "Successfully deleted $deletedCount deliverable(s). $failedCount failed (active).");
        } catch (\Exception $e) {
            return $this->handleException($e, 'bulk deleting deliverables');
        }
    }
}
