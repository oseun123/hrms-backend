<?php

namespace App\Http\Controllers\HRIS;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Hris\Employee;
use App\Models\Hris\IncorrectDetailReport;
use App\Models\User;
use App\Notifications\IncorrectDetailReported;
use App\Traits\HandlesApiErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class IncorrectDetailReportController extends Controller
{
    use HandlesApiErrors;

    /**
     * Submit an incorrect detail report
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $employee = $user->employee;

            if (!$employee) {
                return ApiResponse::error('Employee profile not found', 404);
            }

            $request->validate([
                'section' => 'required|in:personal,employment',
                'field_name' => 'required|string',
                'current_value' => 'nullable|string',
                'reported_correct_value' => 'nullable|string',
                'description' => 'required|string',
            ]);

            DB::beginTransaction();

            $report = IncorrectDetailReport::create([
                'tenant_id' => $user->tenant_id,
                'employee_id' => $employee->id,
                'section' => $request->section,
                'field_name' => $request->field_name,
                'current_value' => $request->current_value,
                'reported_correct_value' => $request->reported_correct_value,
                'description' => $request->description,
                'status' => 'pending',
            ]);

            // Notify all HR users
            $hrUsers = User::hrUsers($user->tenant_id);

            Notification::send($hrUsers, new IncorrectDetailReported($report, $employee));

            DB::commit();

            return ApiResponse::created($report, 'Report submitted successfully. HR will review and update.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to submit report');
        }
    }

    /**
     * Get all incorrect detail reports (HR only)
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();



            $status = $request->query('status');
            $page = $request->query('page', 1);
            $perPage = $request->query('per_page', 15);

            $query = IncorrectDetailReport::with(['employee:id,employee_number,first_name,last_name', 'resolver:id,name'])
                ->where('tenant_id', $user->tenant_id);

            if ($status) {
                $query->where('status', $status);
            }

            $reports = $query->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return ApiResponse::success($reports);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to fetch reports');
        }
    }

    /**
     * Mark report as resolved
     */
    public function resolve(Request $request, $id)
    {
        try {
            $user = Auth::user();



            $request->validate([
                'resolution_notes' => 'nullable|string',
            ]);

            $report = IncorrectDetailReport::where('tenant_id', $user->tenant_id)
                ->where('status', 'pending')
                ->find($id);

            if (!$report) {
                return ApiResponse::notFound('Pending report not found');
            }

            $report->update([
                'status' => 'resolved',
                'resolved_by' => $user->id,
                'resolved_at' => now(),
                'resolution_notes' => $request->resolution_notes,
            ]);

            return ApiResponse::success(null, 'Report marked as resolved');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to resolve report');
        }
    }

    /**
     * Dismiss report
     */
    public function dismiss(Request $request, $id)
    {
        try {
            $user = Auth::user();



            $request->validate([
                'resolution_notes' => 'nullable|string',
            ]);

            $report = IncorrectDetailReport::where('tenant_id', $user->tenant_id)
                ->where('status', 'pending')
                ->find($id);

            if (!$report) {
                return ApiResponse::notFound('Pending report not found');
            }

            $report->update([
                'status' => 'dismissed',
                'resolved_by' => $user->id,
                'resolved_at' => now(),
                'resolution_notes' => $request->resolution_notes,
            ]);

            return ApiResponse::success(null, 'Report dismissed');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to dismiss report');
        }
    }
}
