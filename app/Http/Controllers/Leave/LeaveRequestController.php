<?php

namespace App\Http\Controllers\Leave;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Leave\LeaveRequest;
use App\Models\Leave\LeaveType;
use App\Models\Leave\LeavePolicy;
use App\Models\Leave\LeaveBalance;
use App\Models\Hris\Employee;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\LeaveRequestCancelled;
use Illuminate\Support\Facades\Notification;

class LeaveRequestController extends Controller
{
    use HandlesApiErrors;

    protected \App\Services\Leave\LeaveService $leaveService;
    protected \App\Services\FileUploadService $fileUploadService;

    public function __construct(
        \App\Services\Leave\LeaveService $leaveService,
        \App\Services\FileUploadService $fileUploadService
    ) {
        $this->leaveService = $leaveService;
        $this->fileUploadService = $fileUploadService;
    }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $query = LeaveRequest::with(['leaveType', 'employee'])
                ->where('tenant_id', $tenantId);

            // If not HR/Admin, only show own requests
            if (!$user->is_hr) {
                $employee = Employee::where('user_id', $user->id)->first();
                if ($employee) {
                    $query->where('employee_id', $employee->id);
                } else {
                    $query->whereRaw('1=0'); // No results if no employee record
                }
            } elseif ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date]);
            }

            $requests = $query->orderBy('applied_at', 'desc')->paginate($request->get('per_page', 15));
            return ApiResponse::success($requests);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave requests');
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;

            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'leave_type_id' => 'required|exists:leave_types,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'reason' => 'nullable|string',
                'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            // Check if leave type requires attachment
            $leaveType = LeaveType::findOrFail($validated['leave_type_id']);
            if ($leaveType->requires_attachment && !$request->hasFile('attachment')) {
                return ApiResponse::error('This leave type requires an attachment to be uploaded', 400);
            }

            // 1. Get Leave Policy
            $employee = Employee::with('employmentDetails')->findOrFail($validated['employee_id']);
            $leaveGroupId = $employee->employmentDetails->leave_group_id;

            if (!$leaveGroupId) {
                return ApiResponse::error('Employee does not have a leave group assigned', 400);
            }

            $policy = LeavePolicy::where('leave_group_id', $leaveGroupId)
                ->where('leave_type_id', $validated['leave_type_id'])
                ->where('is_active', true)
                ->first();

            if (!$policy) {
                return ApiResponse::error('No active leave policy found for this employee and leave type', 400);
            }

            // 2. Calculate duration using LeaveService
            $duration = $this->leaveService->calculateLeaveDays(
                $tenantId,
                $validated['start_date'],
                $validated['end_date'],
                $policy->include_weekends,
                $policy->include_public_holidays
            );

            if ($duration === 0) {
                return ApiResponse::error('The selected dates do not contain any working days', 400);
            }

            // check for conflicts (Overlapping leaves)
            $hasConflict = \App\Models\Leave\LeaveRequest::where('employee_id', $validated['employee_id'])
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhere(function ($q) use ($validated) {
                            $q->where('start_date', '<=', $validated['start_date'])
                                ->where('end_date', '>=', $validated['end_date']);
                        });
                })
                ->exists();

            if ($hasConflict) {
                return ApiResponse::error('You already have a leave request or approved leave for the selected dates', 400);
            }

            // 3. Check Policy Constraints
            $policyService = app(\App\Services\Leave\LeavePolicyService::class);
            $policyValidation = $policyService->validatePolicy(
                $employee,
                $policy,
                $validated['start_date'],
                $validated['end_date'],
                $duration
            );

            if (!$policyValidation['is_valid']) {
                return ApiResponse::error($policyValidation['message'], 400);
            }

            // 4. Check available balance using LeaveBalanceService for consistency
            $leaveYearService = app(\App\Services\LeaveYearService::class);
            $leaveBalanceService = app(\App\Services\Leave\LeaveBalanceService::class);

            $leaveYear = $leaveYearService->getLeaveYearForDate(
                \Carbon\Carbon::parse($validated['start_date']),
                $tenantId
            );

            $balance = $leaveBalanceService->getEffectiveBalance($employee, $policy, $leaveYear);
            $available = $balance->available_balance;

            if (!$policy->allow_negative_balance && $duration > $available) {
                return ApiResponse::error("Insufficient leave balance. Available: {$available} days, Requested: {$duration} days.", 400);
            }

            // 5. Handle Attachment
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $uploadResult = $this->fileUploadService->upload(
                    $request->file('attachment'),
                    'leave_attachments',
                    [
                        'validation' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048']
                    ]
                );
                $attachmentPath = $uploadResult['path'];
            }

            $leaveRequest = LeaveRequest::create([
                'tenant_id' => $tenantId,
                'employee_id' => $validated['employee_id'],
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'duration_days' => $duration,
                'reason' => $validated['reason'],
                'attachment_path' => $attachmentPath,
                'status' => 'pending',
                'applied_at' => now(),
            ]);

            // Update pending_approval balance
            if ($balance->exists) {
                $balance->increment('pending_approval', $duration);
            } else {
                // Create the balance record if it doesn't exist
                LeaveBalance::create([
                    'tenant_id' => $tenantId,
                    'employee_id' => $validated['employee_id'],
                    'leave_type_id' => $validated['leave_type_id'],
                    'year' => $leaveYear,
                    'entitlement' => (float) $policy->entitlement_days,
                    'carried_forward' => 0,
                    'accrued' => 0,
                    'used' => 0,
                    'pending_approval' => $duration,
                    'manual_adjustment' => 0,
                ]);
            }

            // Initialize Approval Chain
            $approvalService = app(\App\Services\Leave\LeaveApprovalService::class);
            $approvalService->initializeApprovalChain($leaveRequest, $policy);

            return ApiResponse::success($leaveRequest, 'Leave request submitted successfully', 201);
        } catch (\Exception $e) {
            return $this->handleException($e, 'submitting leave request');
        }
    }

    public function show($id)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $leaveRequest = LeaveRequest::with(['leaveType', 'employee', 'approvals.approver'])
                ->where('tenant_id', $tenantId)
                ->findOrFail($id);
            return ApiResponse::success($leaveRequest);
        } catch (\Exception $e) {
            return $this->handleException($e, 'fetching leave request details');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;
            $leaveRequest = LeaveRequest::where('tenant_id', $tenantId)->findOrFail($id);

            // Only pending or approved requests can be modified
            if (!in_array($leaveRequest->status, ['pending', 'approved'])) {
                return ApiResponse::error('Only pending or approved requests can be modified', 400);
            }

            $validated = $request->validate([
                'leave_type_id' => 'required|exists:leave_types,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'reason' => 'nullable|string',
                'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            // 1. Get Leave Policy
            $employee = Employee::with('employmentDetails')->findOrFail($leaveRequest->employee_id);
            $leaveGroupId = $employee->employmentDetails->leave_group_id;
            $policy = LeavePolicy::where('leave_group_id', $leaveGroupId)
                ->where('leave_type_id', $validated['leave_type_id'])
                ->where('is_active', true)
                ->first();

            if (!$policy) {
                return ApiResponse::error('No active leave policy found', 400);
            }

            // 2. Calculate new duration
            $newDuration = $this->leaveService->calculateLeaveDays(
                $tenantId,
                $validated['start_date'],
                $validated['end_date'],
                $policy->include_weekends,
                $policy->include_public_holidays
            );

            if ($newDuration === 0) {
                return ApiResponse::error('The selected dates do not contain any working days', 400);
            }

            // 3. Check for conflicts (excluding current request)
            $hasConflict = LeaveRequest::where('employee_id', $leaveRequest->employee_id)
                ->where('id', '!=', $id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhere(function ($q) use ($validated) {
                            $q->where('start_date', '<=', $validated['start_date'])
                                ->where('end_date', '>=', $validated['end_date']);
                        });
                })->exists();

            if ($hasConflict) {
                return ApiResponse::error('New dates conflict with another request', 400);
            }

            // 4. Check Policy Validation (min service, etc.)
            $policyService = app(\App\Services\Leave\LeavePolicyService::class);
            $policyValidation = $policyService->validatePolicy($employee, $policy, $validated['start_date'], $validated['end_date'], $newDuration);
            if (!$policyValidation['is_valid']) {
                return ApiResponse::error($policyValidation['message'], 400);
            }

            DB::beginTransaction();

            // 5. Revert balance if it was approved or pending
            $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id) // Use old leave type for reversal
                ->where('year', Carbon::parse($leaveRequest->start_date)->year)
                ->first();

            if ($balance) {
                if ($leaveRequest->status === 'approved') {
                    $balance->decrement('used', (float) $leaveRequest->duration_days);
                } elseif ($leaveRequest->status === 'pending') {
                    $balance->decrement('pending_approval', (float) $leaveRequest->duration_days);
                }
            }

            // 6. Check available balance (now including the reverted days if it was approved)
            $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $validated['leave_type_id'])
                ->where('year', Carbon::parse($validated['start_date'])->year)
                ->first();

            $available = $balance ? $balance->available_balance : 0;
            if (!$policy->allow_negative_balance && $newDuration > $available) {
                DB::rollBack();
                return ApiResponse::error("Insufficient leave balance. Available: {$available} days, Requested: {$newDuration} days.", 400);
            }

            // 7. Handle Attachment
            $attachmentPath = $leaveRequest->attachment_path;
            if ($request->hasFile('attachment')) {
                // Delete old attachment if exists
                if ($leaveRequest->attachment_path) {
                    try {
                        $this->fileUploadService->delete($leaveRequest->attachment_path);
                    } catch (\Exception $e) {
                        // Log but don't fail if old file deletion fails
                        Log::warning("Failed to delete old leave attachment: {$e->getMessage()}");
                    }
                }

                $uploadResult = $this->fileUploadService->upload(
                    $request->file('attachment'),
                    'leave_attachments',
                    [
                        'validation' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048']
                    ]
                );
                $attachmentPath = $uploadResult['path'];
            }

            // 8. Update request
            $leaveRequest->update([
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'duration_days' => $newDuration,
                'reason' => $validated['reason'],
                'attachment_path' => $attachmentPath,
                'status' => 'pending', // Re-verify on modification
            ]);

            // Update pending_approval for the new request
            $newBalance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $validated['leave_type_id'])
                ->where('year', Carbon::parse($validated['start_date'])->year)
                ->first();

            if ($newBalance) {
                $newBalance->increment('pending_approval', (float) $newDuration);
            } else {
                // Create if doesn't exist (e.g. if leave type changed to one without balance record)
                LeaveBalance::create([
                    'tenant_id' => $tenantId,
                    'employee_id' => $leaveRequest->employee_id,
                    'leave_type_id' => $validated['leave_type_id'],
                    'year' => Carbon::parse($validated['start_date'])->year,
                    'entitlement' => (float) $policy->entitlement_days,
                    'pending_approval' => (float) $newDuration,
                    // ... other fields default to 0
                ]);
            }

            // 9. Reset/Re-initialize approval chain
            $leaveRequest->approvals()->delete();
            $approvalService = app(\App\Services\Leave\LeaveApprovalService::class);
            $approvalService->initializeApprovalChain($leaveRequest, $policy);

            DB::commit();
            return ApiResponse::success($leaveRequest, 'Leave request updated and sent for re-approval');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'updating leave request');
        }
    }

    public function cancel($id)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;
            $leaveRequest = LeaveRequest::where('tenant_id', $tenantId)->findOrFail($id);

            if ($leaveRequest->status === 'approved' || $leaveRequest->status === 'pending') {
                DB::beginTransaction();

                if ($leaveRequest->status === 'approved') {
                    $this->reverseBalance($leaveRequest);
                } elseif ($leaveRequest->status === 'pending') {
                    $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
                        ->where('leave_type_id', $leaveRequest->leave_type_id)
                        ->where('year', Carbon::parse($leaveRequest->start_date)->year)
                        ->first();
                    if ($balance) {
                        $balance->decrement('pending_approval', (float) $leaveRequest->duration_days);
                    }
                }

                // Notify current pending approvers before updating status
                $pendingApprovals = $leaveRequest->approvals()->where('status', 'pending')->with('approver')->get();
                foreach ($pendingApprovals as $approval) {
                    if ($approval->approver) {
                        $approval->approver->notify(new LeaveRequestCancelled($leaveRequest));
                    }
                }

                $leaveRequest->update([
                    'status' => 'cancelled',
                    'cancelled_by' => $user->id,
                    'cancelled_at' => now(),
                ]);

                DB::commit();
                return ApiResponse::success(null, 'Leave request cancelled successfully');
            }

            return ApiResponse::error('This request cannot be cancelled in its current status', 400);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'cancelling leave request');
        }
    }

    protected function reverseBalance(LeaveRequest $leaveRequest)
    {
        $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', Carbon::parse($leaveRequest->start_date)->year)
            ->first();

        if ($balance) {
            $balance->decrement('used', (float) $leaveRequest->duration_days);
        }
    }

    public function partialCancel(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->tenant_id;
            $leaveRequest = LeaveRequest::where('tenant_id', $tenantId)->findOrFail($id);

            if ($leaveRequest->status !== 'approved') {
                return ApiResponse::error('Only approved leave requests can be partially cancelled', 400);
            }

            $validated = $request->validate([
                'new_start_date' => 'required|date|after_or_equal:' . $leaveRequest->start_date->format('Y-m-d'),
                'new_end_date' => 'required|date|before_or_equal:' . $leaveRequest->end_date->format('Y-m-d') . '|after_or_equal:new_start_date',
                'reason' => 'nullable|string',
            ]);

            // 1. Get Leave Policy
            $employee = Employee::with('employmentDetails')->findOrFail($leaveRequest->employee_id);
            $policy = LeavePolicy::where('leave_group_id', $employee->employmentDetails->leave_group_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->first();

            if (!$policy) {
                return ApiResponse::error('Policy not found', 400);
            }

            // 2. Calculate new duration
            $newDuration = $this->leaveService->calculateLeaveDays(
                $tenantId,
                $validated['new_start_date'],
                $validated['new_end_date'],
                $policy->include_weekends,
                $policy->include_public_holidays
            );

            $oldDuration = $leaveRequest->duration_days;
            $diff = $oldDuration - $newDuration;

            if ($diff <= 0) {
                return ApiResponse::error('The new range must be shorter than the original range', 400);
            }

            DB::beginTransaction();

            // 3. Restore balance diff
            $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('year', Carbon::parse($leaveRequest->start_date)->year)
                ->first();

            if ($balance) {
                $balance->decrement('used', (float) $diff);
            }

            // 4. Update request
            $leaveRequest->update([
                'start_date' => $validated['new_start_date'],
                'end_date' => $validated['new_end_date'],
                'duration_days' => $newDuration,
                'status' => 'approved', // Keep as approved
                'reason' => ($leaveRequest->reason ? $leaveRequest->reason . "\n" : "") . "Partial Cancellation: " . ($validated['reason'] ?? 'No reason provided'),
            ]);

            DB::commit();
            return ApiResponse::success($leaveRequest, "Partial cancellation successful. {$diff} days restored to balance.");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'partially cancelling leave request');
        }
    }

    public function calculateDuration(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'leave_type_id' => 'required|exists:leave_types,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $tenantId = Auth::user()->tenant_id;
            // 1. Get Leave Policy to know if we should include weekends/holidays
            $employee = Employee::with('employmentDetails')->findOrFail($validated['employee_id']);
            $leaveGroupId = $employee->employmentDetails->leave_group_id;

            if (!$leaveGroupId) {
                return ApiResponse::error('Employee does not have a leave group assigned', 400);
            }

            $policy = LeavePolicy::where('leave_group_id', $leaveGroupId)
                ->where('leave_type_id', $validated['leave_type_id'])
                ->where('is_active', true)
                ->first();

            if (!$policy) {
                return ApiResponse::error('No active leave policy found for this employee and leave type', 400);
            }

            $duration = $this->leaveService->calculateLeaveDays(
                $tenantId,
                $validated['start_date'],
                $validated['end_date'],
                $policy->include_weekends,
                $policy->include_public_holidays
            );

            return ApiResponse::success(['duration' => $duration]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'calculating leave duration');
        }
    }
}
