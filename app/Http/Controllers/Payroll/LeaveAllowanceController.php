<?php

namespace App\Http\Controllers\Payroll;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Payroll\LeaveAllowanceRequest;
use App\Models\User;
use App\Notifications\LeaveAllowanceApprovedNotification;
use App\Notifications\LeaveAllowanceDeclinedNotification;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaveAllowanceController extends Controller
{
    use HandlesApiErrors;

    /**
     * List all leave allowance requests with filters and pagination.
     */
    public function index(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $query = LeaveAllowanceRequest::with(['employee', 'leaveRequest.leaveType', 'approver', 'batch', 'monthlyPayment'])
                ->where('tenant_id', $tenantId);

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by leave year
            if ($request->has('leave_year') && $request->leave_year) {
                $query->where('leave_year', $request->leave_year);
            }

            // Filter by month (based on leave request start date)
            if ($request->has('month') && $request->month) {
                $query->whereHas('leaveRequest', function ($q) use ($request) {
                    $q->whereMonth('start_date', $request->month);
                });
            }

            if ($request->has('year') && $request->year) {
                $boundaries = app(\App\Services\LeaveYearService::class)->getLeaveYearBoundaries((int)$request->year, $tenantId);
                $query->whereHas('leaveRequest', function ($q) use ($boundaries) {
                    $q->whereBetween('start_date', [$boundaries['start'], $boundaries['end']]);
                });
            }

            $requests = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return ApiResponse::success($requests);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave allowance requests');
        }
    }

    /**
     * Get a single leave allowance request.
     */
    public function show($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $request = LeaveAllowanceRequest::with(['employee', 'leaveRequest.leaveType', 'approver', 'batch'])
                ->where('tenant_id', $tenantId)
                ->findOrFail($id);

            return ApiResponse::success($request);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave allowance request details');
        }
    }

    /**
     * Approve a leave allowance request.
     */
    public function approve($id)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $leaveAllowanceRequest = LeaveAllowanceRequest::where('tenant_id', $tenantId)
                ->findOrFail($id);

            if ($leaveAllowanceRequest->status !== 'pending') {
                return ApiResponse::error('Only pending requests can be approved', 400);
            }

            DB::beginTransaction();

            $leaveAllowanceRequest->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            // Notify the employee
            $employeeUser = $leaveAllowanceRequest->employee->user;
            if ($employeeUser) {
                $employeeUser->notify(new LeaveAllowanceApprovedNotification($leaveAllowanceRequest));
            }

            DB::commit();

            return ApiResponse::success($leaveAllowanceRequest, 'Leave allowance request approved successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'approving leave allowance request');
        }
    }

    /**
     * Decline a leave allowance request.
     */
    public function decline(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:1000',
            ]);

            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $leaveAllowanceRequest = LeaveAllowanceRequest::where('tenant_id', $tenantId)
                ->findOrFail($id);

            if ($leaveAllowanceRequest->status !== 'pending') {
                return ApiResponse::error('Only pending requests can be declined', 400);
            }

            DB::beginTransaction();

            $leaveAllowanceRequest->update([
                'status' => 'declined',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'decline_reason' => $validated['reason'],
            ]);

            // Notify the employee
            $employeeUser = $leaveAllowanceRequest->employee->user;
            if ($employeeUser) {
                $employeeUser->notify(new LeaveAllowanceDeclinedNotification($leaveAllowanceRequest));
            }

            DB::commit();

            return ApiResponse::success($leaveAllowanceRequest, 'Leave allowance request declined');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'declining leave allowance request');
        }
    }

    /**
     * Get summary statistics for dashboard.
     */
    public function summary()
    {
        try {
            $tenantId = Auth::user()->tenant_id;

            $summary = [
                'pending' => LeaveAllowanceRequest::where('tenant_id', $tenantId)->pending()->count(),
                'approved' => LeaveAllowanceRequest::where('tenant_id', $tenantId)->approved()->count(),
                'declined' => LeaveAllowanceRequest::where('tenant_id', $tenantId)->declined()->count(),
                'paid' => LeaveAllowanceRequest::where('tenant_id', $tenantId)->where('status', 'paid')->count(),
                'pending_payment' => LeaveAllowanceRequest::where('tenant_id', $tenantId)
                    ->approved()
                    ->notPaid()
                    ->count(),
            ];

            return ApiResponse::success($summary);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave allowance summary');
        }
    }
}
