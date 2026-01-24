<?php

namespace App\Http\Controllers\Leave;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Leave\LeaveApproval;
use App\Models\Leave\LeaveRequest;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaveApprovalController extends Controller
{
    use HandlesApiErrors;

    public function pending(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            // Show requests where this user is the next approver
            // AND the leave request itself is not cancelled
            $query = LeaveApproval::with(['leaveRequest.employee', 'leaveRequest.leaveType'])
                ->where('tenant_id', $tenantId)
                ->where('approver_id', $user->id)
                ->where('status', 'pending')
                ->whereHas('leaveRequest', function ($query) {
                    $query->where('status', '!=', 'cancelled');
                });

            // Filtering
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereHas('leaveRequest', function ($q) use ($request) {
                    $q->whereBetween('start_date', [$request->start_date, $request->end_date]);
                });
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('leaveRequest.employee', function ($q) use ($search) {
                    $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$search}%")
                        ->orWhere('employee_number', 'LIKE', "%{$search}%");
                });
            }

            $pendingApprovals = $query->get();

            return ApiResponse::success($pendingApprovals);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching pending approvals');
        }
    }

    public function action(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $validated = $request->validate([
                'status' => 'required|in:approved,declined',
                'comments' => 'nullable|string',
            ]);

            $approval = LeaveApproval::where('tenant_id', $tenantId)
                ->where('approver_id', $user->id)
                ->findOrFail($id);

            DB::beginTransaction();

            $approval->update([
                'status' => $validated['status'],
                'comments' => $validated['comments'] ?? null,
                'actioned_at' => now(),
            ]);

            $leaveRequest = $approval->leaveRequest;

            $approvalService = app(\App\Services\Leave\LeaveApprovalService::class);

            if ($validated['status'] === 'declined') {
                $approvalService->handleDecline($leaveRequest, $validated['comments'] ?? null);
            } else {
                // Progress chain using LeaveApprovalService
                $approvalService->progressChain($leaveRequest, $approval);
            }

            DB::commit();

            return ApiResponse::success(null, 'Leave action processed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'processing leave action');
        }
    }

    public function history(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $query = LeaveApproval::with(['leaveRequest.employee', 'leaveRequest.leaveType'])
                ->where('tenant_id', $tenantId)
                ->where('approver_id', $user->id)
                ->whereIn('status', ['approved', 'declined']);

            // Filtering
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereHas('leaveRequest', function ($q) use ($request) {
                    $q->whereBetween('start_date', [$request->start_date, $request->end_date]);
                });
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('leaveRequest.employee', function ($q) use ($search) {
                    $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$search}%")
                        ->orWhere('employee_number', 'LIKE', "%{$search}%");
                });
            }

            $perPage = $request->input('per_page', 10);
            $history = $query->orderBy('actioned_at', 'desc')->paginate($perPage);

            return ApiResponse::success($history);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching approval history');
        }
    }

    public function nudge($id)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $approval = LeaveApproval::where('tenant_id', $tenantId)
                ->where('status', 'pending')
                ->findOrFail($id);

            // Verify the nudge is coming from the employee who owns the request
            if ($approval->leaveRequest->employee->user_id !== $user->id) {
                return ApiResponse::error('Unauthorized nudge attempt', 403);
            }

            if ($approval->nudged_at) {
                return ApiResponse::error('You have already nudged this approver', 422);
            }

            $approval->update([
                'nudged_at' => now(),
            ]);

            // Notify the approver
            $approver = $approval->approver;
            if ($approver) {
                $approver->notify(new \App\Notifications\LeaveApprovalNudge($approval));
            }

            return ApiResponse::success(null, 'Approver has been nudged successfully');
        } catch (\Exception $e) {
            return $this->handleException($e, 'nudging approver');
        }
    }
}
