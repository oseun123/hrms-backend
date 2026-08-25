<?php

namespace App\Actions\Attendance;

use App\Models\Attendance\Attendance;
use App\Models\Attendance\AttendanceWorkScheduleSetting;
use App\Models\Attendance\AttendanceApprovalSetting;
use App\Models\Attendance\AttendanceCorrectionApproval;
use App\Models\Attendance\AttendanceCorrectionRequest;
use App\Models\User;
use App\Models\Hris\Employee;
use App\Services\Attendance\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttendanceAction
{
    public $attendance;
    public $workSchedule;
    public $approvalSetting;
    public $correctionRequest;
    public $approval;
    public $attendanceService;

    public function __construct()
    {
        $this->attendance = new Attendance();
        $this->workSchedule = new AttendanceWorkScheduleSetting();
        $this->approvalSetting = new AttendanceApprovalSetting();
        $this->correctionRequest = new AttendanceCorrectionRequest();
        $this->approval = new AttendanceCorrectionApproval();
        $this->attendanceService = new AttendanceService();
    }

    public function setWorkSchedule($data)
    {
        return $this->workSchedule::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'arrival_grace' => $data['arrival_grace'] ?? 0,
                'departure_grace' => $data['departure_grace'] ?? 0,
                'work_days' => $data['work_days'] ?? null,
                'overtime_days' => $data['overtime_days'] ?? [],
                'is_shift' => $data['is_shift'] ?? false,
                'enable_geofence' => $data['enable_geofence'] ?? true,
                'geofence_radius' => $data['geofence_radius'] ?? 100,
                'enable_webcam' => $data['enable_webcam'] ?? false,
                'webcam_verification_type' => $data['webcam_verification_type'] ?? 'photo_proof',
                'enable_payroll' => $data['enable_payroll'] ?? false,
                'time_zone' => $data['time_zone'] ?? 'Africa/Lagos',
            ]
        );
    }

    public function getWorkSchedule()
    {
        $workSchedule = $this->workSchedule::first();
        if (!$workSchedule) {
            return [
                'id' => null,
                'arrival_grace' => 0,
                'departure_grace' => 0,
                'work_days' => [],
                'overtime_days' => [],
                'is_shift' => false,
                'enable_geofence' => true,
                'geofence_radius' => 100,
                'enable_webcam' => false,
                'webcam_verification_type' => 'photo_proof',
                'enable_payroll' => false,
                'time_zone' => 'Africa/Lagos',
            ];
        }

        return $workSchedule;
    }

    public function setApprovalSetting($data)
    {
        return $this->approvalSetting::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'first_approver' => $data['first_approver'] ?? 'primary-line-manager',
                'second_approver' => $data['second_approver'] ?? null,
                'third_approver' => $data['third_approver'] ?? null,
                'lateness_fee_approval' => $data['lateness_fee_approver'] ?? 'primary-line-manager',
                'absenteeism_fee_approval' => $data['absenteeism_fee_approver'] ?? 'primary-line-manager',
                'overtime_fee_approval' => $data['overtime_fee_approver'] ?? 'primary-line-manager',
                'overtime_time_fee' => $data['overtime_time_fee'] ?? false,
            ]
        );
    }

    public function getApprovalSetting()
    {
        $setting = $this->approvalSetting::first();
        if (!$setting) {
            return [
                'id' => null,
                'first_approver' => 'primary-line-manager',
                'second_approver' => null,
                'third_approver' => null,
                'lateness_fee_approver' => 'primary-line-manager',
                'absenteeism_fee_approver' => 'primary-line-manager',
                'overtime_fee_approver' => 'primary-line-manager',
                'overtime_time_fee' => false,
            ];
        }

        return [
            'id' => $setting->id,
            'first_approver' => $setting->first_approver,
            'second_approver' => $setting->second_approver,
            'third_approver' => $setting->third_approver,
            'lateness_fee_approver' => $setting->lateness_fee_approval,
            'absenteeism_fee_approver' => $setting->absenteeism_fee_approval,
            'overtime_fee_approver' => $setting->overtime_fee_approval,
            'overtime_time_fee' => $setting->overtime_time_fee,
        ];
    }

    public function personalOverview()
    {
        $user = auth()->user();
        $records = $this->attendance::where('user_id', $user->id)->get();

        $present = $records->where('status', 'present')->count();
        $late = $records->where('status', 'late')->count();
        $absent = $records->where('status', 'absent')->count();
        $leave = $records->where('status', 'leave')->count();

        $pendingRequest = $this->correctionRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        return [
            'present' => $present,
            'late' => $late,
            'early' => $records->where('is_late', false)->whereNotIn('status', ['absent', 'leave'])->count(),
            'absent' => $absent,
            'leave' => $leave,
            'pending_request' => $pendingRequest
        ];
    }

    public function summary()
    {
        // Total active users
        $totalUsers = User::count();
        $pendingRequests = $this->correctionRequest::where('status', 'pending')->count();

        return [
            'total_users' => $totalUsers,
            'pending_requests' => $pendingRequests,
        ];
    }

    public function handleClockAction($type, $lat = null, $lng = null, $webcamBase64 = null)
    {
        $user = auth()->user();
        $schedule = $this->getWorkSchedule();
        $timeZone = $schedule['time_zone'] ?? 'Africa/Lagos';

        $now = Carbon::now($timeZone);
        $date = $now->toDateString();
        $time = $now->format('H:i:s');
        $ip = request()->ip();

        // Check geofence
        if ($schedule['enable_geofence']) {
            $employee = Employee::where('user_id', $user->id)->first();
            $branch = $employee ? $employee->branch : null;
            if ($branch && isset($branch->latitude, $branch->longitude)) {
                $within = AttendanceService::isWithinGeofence($lat, $lng, $branch->latitude, $branch->longitude, $schedule['geofence_radius'] ?? 100);
                if (!$within) {
                    abort(403, "You are outside the required geofence area. Distance limit: " . ($schedule['geofence_radius'] ?? 100) . "m");
                }
            }
        }

        // Save webcam image
        $photoPath = null;
        if ($schedule['enable_webcam'] && $webcamBase64) {
            $image = str_replace('data:image/jpeg;base64,', '', $webcamBase64);
            $image = str_replace('data:image/png;base64,', '', $image);
            $image = str_replace(' ', '+', $image);
            $imageData = base64_decode($image);
            $fileName = 'attendance/' . $user->id . '_' . time() . '.png';
            Storage::disk('public')->put($fileName, $imageData);
            $photoPath = Storage::url($fileName);
        }

        if ($type === 'check_in') {
            $existing = $this->attendance::where('user_id', $user->id)->whereDate('date', $date)->first();
            if ($existing && $existing->check_in) {
                abort(400, 'You have already checked in today.');
            }
            return $this->attendanceService->handleCheckIn($user->id, $date, $time, $ip, $lat, $lng, $photoPath);
        } else {
            // Clock out
            $existing = $this->attendance::where('user_id', $user->id)->whereDate('date', $date)->first();
            if (!$existing || !$existing->check_in) {
                abort(400, 'No check-in record found for today.');
            }
            if ($existing->check_out) {
                abort(400, 'You have already checked out today.');
            }
            return $this->attendanceService->handleCheckOut($user->id, $date, $time, $ip, $lat, $lng, $photoPath);
        }
    }

    public function checkStatus()
    {
        $user = auth()->user();
        $date = Carbon::now()->toDateString();
        $existing = $this->attendance::where('user_id', $user->id)->whereDate('date', $date)->first();

        return [
            'check_in' => $existing && $existing->check_in ? true : false,
            'check_out' => $existing && $existing->check_out ? true : false,
            'check_in_time' => $existing ? $existing->check_in : null,
            'check_out_time' => $existing ? $existing->check_out : null,
            'has_reference_photo' => $user->attendance_photo_path ? true : false,
            'attendance_photo_path' => $user->attendance_photo_path,
        ];
    }
}
