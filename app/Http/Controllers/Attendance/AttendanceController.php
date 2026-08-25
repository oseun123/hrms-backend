<?php

namespace App\Http\Controllers\Attendance;

use App\Actions\Attendance\AttendanceAction;
use App\Http\Controllers\Controller;
use App\Models\Attendance\Attendance;
use App\Models\Attendance\AttendanceCorrectionRequest;
use App\Models\Attendance\AttendanceCorrectionApproval;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected $action;

    public function __construct(AttendanceAction $action)
    {
        $this->action = $action;
    }

    public function checkStatus()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->action->checkStatus()
        ]);
    }

    public function clock(Request $request)
    {
        $request->validate([
            'type' => 'required|in:check_in,check_out',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'photo' => 'nullable|string' // base64
        ]);

        try {
            $record = $this->action->handleClockAction(
                $request->type,
                $request->latitude,
                $request->longitude,
                $request->photo
            );
            return response()->json([
                'status' => 'success',
                'message' => 'Attendance recorded successfully',
                'data' => $record
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function personalOverview()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->action->personalOverview()
        ]);
    }

    public function summary()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->action->summary()
        ]);
    }

    public function myAttendance(Request $request)
    {
        $user = auth()->user();
        $query = Attendance::where('user_id', $user->id)->orderBy('date', 'desc');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate(20)
        ]);
    }

    public function dailyAttendance(Request $request)
    {
        $query = Attendance::with('user.employee.branch')->orderBy('date', 'desc');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate(20)
        ]);
    }

    public function getWorkSchedule()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->action->getWorkSchedule()
        ]);
    }

    public function setWorkSchedule(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'start_time' => 'required',
            'end_time' => 'required',
            'evening_start_time' => 'nullable',
            'evening_end_time' => 'nullable',
            'arrival_grace' => 'required|integer',
            'departure_grace' => 'required|integer',
            'work_days' => 'nullable|array',
            'overtime_days' => 'nullable|array',
            'is_shift' => 'required|boolean',
            'enable_geofence' => 'required|boolean',
            'geofence_radius' => 'required|integer',
            'enable_webcam' => 'required|boolean',
            'enable_payroll' => 'required|boolean',
            'time_zone' => 'required|string',
        ]);

        $schedule = $this->action->setWorkSchedule($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Work schedule updated successfully',
            'data' => $schedule
        ]);
    }

    public function getApprovalSetting()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->action->getApprovalSetting()
        ]);
    }

    public function setApprovalSetting(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'first_approver' => 'required|string',
            'second_approver' => 'nullable|string',
            'third_approver' => 'nullable|string',
            'lateness_fee_approver' => 'nullable|string',
            'absenteeism_fee_approver' => 'nullable|string',
            'overtime_fee_approver' => 'nullable|string',
            'overtime_time_fee' => 'nullable|boolean',
        ]);

        $setting = $this->action->setApprovalSetting($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Approval settings updated successfully',
            'data' => $setting
        ]);
    }

    public function requestCorrection(Request $request)
    {
        $request->validate([
            'attendance_date' => 'required|date',
            'correction_type' => 'required|in:check_in,check_out,both,forgot_check_out',
            'correct_time' => 'nullable',
            'correct_check_out' => 'nullable',
            'reason' => 'required|string',
            'document' => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:2048'
        ]);

        $user = auth()->user();
        $filePath = null;

        if ($request->hasFile('document')) {
            $filePath = $request->file('document')->store('attendance_corrections', 'public');
        }

        $approvalSetting = $this->action->approvalSetting::first();
        $approverType = $approvalSetting ? $approvalSetting->first_approver : 'primary-line-manager';
        $approverId = null;

        if ($approverType === 'primary-line-manager') {
            $employee = $user->employee;
            $approverId = $employee ? $employee->primary_line_manager_id : null;
        }

        $correction = AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_date' => $request->attendance_date,
            'correction_type' => $request->correction_type,
            'correct_time' => $request->correct_time,
            'correct_check_out' => $request->correct_check_out,
            'reason' => $request->reason,
            'supporting_document' => $filePath ? Storage::url($filePath) : null,
            'status' => 'pending',
            'approver_id' => $approverId,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Correction request submitted successfully',
            'data' => $correction
        ]);
    }

    public function getAttendanceRequests(Request $request)
    {
        $user = auth()->user();
        $query = AttendanceCorrectionRequest::with('user.employee')
            ->where('approver_id', $user->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate(20)
        ]);
    }

    public function trackRequests()
    {
        $user = auth()->user();
        $requests = AttendanceCorrectionRequest::with('approver')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $requests
        ]);
    }

    public function handleApproval(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $user = auth()->user();
        $corr = AttendanceCorrectionRequest::where('id', $id)
            ->where('approver_id', $user->id)
            ->firstOrFail();

        $corr->status = $request->status;
        $corr->save();

        if ($request->status === 'approved') {
            // Apply corrections to Attendance record
            $attendance = Attendance::firstOrCreate([
                'user_id' => $corr->user_id,
                'date' => $corr->attendance_date
            ]);

            if ($corr->correction_type === 'check_in' || $corr->correction_type === 'both') {
                $attendance->check_in = $corr->correct_time;
            }
            if ($corr->correction_type === 'check_out' || $corr->correction_type === 'both') {
                $attendance->check_out = $corr->correct_check_out ?? $corr->correct_time;
            }
            if ($corr->correction_type === 'forgot_check_out') {
                $attendance->check_out = $corr->correct_time;
            }

            $action = new AttendanceAction();
            $action->attendanceService->applyAttendanceCalculations($attendance);
            $attendance->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Request actioned successfully',
            'data' => $corr
        ]);
    }

    public function uploadReferencePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|string'
        ]);

        try {
            $user = auth()->user();
            $image = str_replace('data:image/jpeg;base64,', '', $request->photo);
            $image = str_replace('data:image/png;base64,', '', $image);
            $image = str_replace(' ', '+', $image);
            $imageData = base64_decode($image);

            $fileName = 'attendance_references/' . $user->id . '_' . time() . '.png';
            Storage::disk('public')->put($fileName, $imageData);
            $path = Storage::url($fileName);

            $user->attendance_photo_path = $path;
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Attendance reference photo uploaded successfully',
                'data' => ['attendance_photo_path' => $path]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
