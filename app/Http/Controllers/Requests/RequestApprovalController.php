<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\Requests\RequestApproval;
use App\Models\Requests\RequestSubmission;
use App\Services\RequestService;
use App\Notifications\RequestStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RequestApprovalController extends Controller
{
    protected $requestService;

    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
    }

    public function pending(Request $request)
    {
        $user = Auth::user();

        $query = RequestApproval::where('approver_id', $user->id)
            ->where('status', 'pending')
            ->whereHas('submission', function ($q) use ($user) {
                $q->where('tenant_id', $user->tenant_id)
                  ->where('status', 'in_progress')
                  ->whereColumn('request_submissions.current_level', 'request_approvals.level');
            })
            ->with(['submission.template', 'submission.employee.user', 'submission.employee.employmentDetails']);

        if ($request->has('search')) {
            $query->whereHas('submission', function ($q) use ($request) {
                $q->where('reference_number', 'like', '%' . $request->search . '%')
                  ->orWhereHas('employee', function ($eq) use ($request) {
                      $eq->where('first_name', 'like', '%' . $request->search . '%')
                         ->orWhere('last_name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get()
        ]);
    }

    public function action(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,declined',
            'comments' => 'nullable|string',
        ]);

        $user = Auth::user();
        $approval = RequestApproval::where('approver_id', $user->id)
            ->where('id', $id)
            ->where('status', 'pending')
            ->with('submission')
            ->firstOrFail();

        return DB::transaction(function () use ($request, $approval) {
            $approval->update([
                'status' => $request->status,
                'comments' => $request->comments,
                'actioned_at' => now(),
            ]);

            $submission = $approval->submission;

            if ($request->status === 'approved') {
                // Advance to next level
                $this->requestService->advanceApproval($submission);
            } else {
                // Mark submission as declined
                $submission->update([
                    'status' => 'declined',
                    'completed_at' => now(),
                ]);

                // Notify requester of decline
                if ($requester = $submission->employee->user) {
                    $requester->notify(new RequestStatusUpdated($submission, 'declined', $request->comments));
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Approval processed successfully.'
            ]);
        });
    }

    public function history(Request $request)
    {
        $user = Auth::user();

        $query = RequestApproval::where('approver_id', $user->id)
            ->whereIn('status', ['approved', 'declined'])
            ->with(['submission.template', 'submission.employee.user', 'submission.employee.employmentDetails']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->whereHas('submission', function ($q) use ($request) {
                $q->where('reference_number', 'like', '%' . $request->search . '%');
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderBy('actioned_at', 'desc')->paginate($request->get('per_page', 10))
        ]);
    }
}
