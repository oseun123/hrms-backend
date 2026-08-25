<?php

namespace App\Services\Attendance;

use App\Models\Attendance\Attendance;
use App\Models\Attendance\AttendanceWorkScheduleSetting;
use App\Models\Attendance\AttendanceApprovalSetting;
use App\Models\Attendance\AttendanceCorrectionApproval;
use App\Models\Attendance\AttendanceCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    public $attendance;
    public $workSchedule;
    public $approvalSetting;

    public function __construct()
    {
        $this->attendance = new Attendance();
        $this->workSchedule = new AttendanceWorkScheduleSetting();
        $this->approvalSetting = new AttendanceApprovalSetting();
    }

    public static function calculateSpentTime($checkIn, $checkOut)
    {
        if (!$checkIn || !$checkOut) {
            return null;
        }

        $in  = Carbon::createFromFormat('H:i:s', $checkIn);
        $out = Carbon::createFromFormat('H:i:s', $checkOut);

        if ($out->lessThanOrEqualTo($in)) {
            $out->addDay();
        }

        $diffInSeconds = $out->diffInSeconds($in);
        $hours   = floor($diffInSeconds / 3600);
        $minutes = floor(($diffInSeconds % 3600) / 60);

        return sprintf('%02dh:%02dm', $hours, $minutes);
    }

    public function handleCheckIn($userId, $date, $time, $ip = null, $lat = null, $lng = null, $photo = null)
    {
        return $this->attendance::updateOrCreate(
            ['user_id' => $userId, 'date' => $date],
            [
                'check_in'  => $time,
                'status'    => 'pending',
                'is_absent' => false,
                'is_leave'  => false,
                'ip_address' => $ip,
                'latitude' => $lat,
                'longitude' => $lng,
                'photo_proof_path' => $photo
            ]
        );
    }

    public function handleCheckOut($userId, $date, $time, $ip = null, $lat = null, $lng = null, $photo = null)
    {
        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('date', $date)
            ->whereNull('check_out')
            ->latest('id')
            ->first();

        if (!$attendance) {
            return null;
        }

        $attendance->check_out = $time;
        if ($ip) $attendance->ip_address = $ip;
        if ($lat) $attendance->latitude = $lat;
        if ($lng) $attendance->longitude = $lng;
        if ($photo) $attendance->photo_proof_path = $photo;

        $this->applyAttendanceCalculations($attendance);
        $attendance->save();

        return $attendance;
    }

    public function applyAttendanceCalculations(Attendance $attendance)
    {
        if (!$attendance->check_in || !$attendance->check_out) {
            return;
        }

        $attDate  = Carbon::parse($attendance->date);
        $checkIn  = Carbon::parse($attendance->check_in)->setDateFrom($attDate);
        $checkOut = Carbon::parse($attendance->check_out)->setDateFrom($attDate);

        if ($checkOut->lessThanOrEqualTo($checkIn)) {
            $checkOut->addDay();
        }

        $spentMinutes = $checkOut->diffInMinutes($checkIn);
        $attendance->spent_time = sprintf("%dh:%02dm", floor($spentMinutes / 60), $spentMinutes % 60);

        $schedule = $this->workSchedule::first();
        if (!$schedule) {
            $attendance->status = 'present';
            return;
        }

        $dayName  = strtolower($checkIn->format('l'));
        $shiftStart = null;
        $shiftEnd   = null;
        $shiftType  = 'morning';

        $workDays = $schedule->work_days ?? [];
        $dayConfig = $workDays[$dayName] ?? null;

        if ($dayConfig) {
            $morningStart = Carbon::parse($attendance->date . ' ' . ($dayConfig['morning']['start'] ?? '09:00:00'));
            $morningEnd   = Carbon::parse($attendance->date . ' ' . ($dayConfig['morning']['end'] ?? '17:00:00'));

            if ($morningEnd->lt($morningStart)) {
                $morningEnd->addDay();
            }

            if ($schedule->is_shift && isset($dayConfig['evening'])) {
                $eveningStart = Carbon::parse($attendance->date . ' ' . ($dayConfig['evening']['start'] ?? '17:00:00'));
                $eveningEnd   = Carbon::parse($attendance->date . ' ' . ($dayConfig['evening']['end'] ?? '01:00:00'));

                if ($eveningEnd->lt($eveningStart)) {
                    $eveningEnd->addDay();
                }

                if ($checkIn->between($morningStart->copy()->subHour(), $morningEnd)) {
                    $shiftStart = $morningStart;
                    $shiftEnd   = $morningEnd;
                    $shiftType  = 'morning';
                } elseif ($checkIn->between($eveningStart->copy()->subHour(), $eveningEnd->copy()->addHours(4))) {
                    $shiftStart = $eveningStart;
                    $shiftEnd   = $eveningEnd;
                    $shiftType  = 'evening';
                } else {
                    $shiftStart = $morningStart;
                    $shiftEnd   = $morningEnd;
                }
            } else {
                $shiftStart = $morningStart;
                $shiftEnd   = $morningEnd;
            }
        } else {
            $shiftStart = Carbon::parse($attendance->date . ' ' . $schedule->start_time);
        }

        if ($shiftStart) {
            $lateThreshold = $shiftStart->copy()->addMinutes($schedule->arrival_grace);
            if ($checkIn->gt($lateThreshold)) {
                $attendance->is_late = true;
                $attendance->status = 'late';
            } else {
                $attendance->is_late = false;
                $attendance->status = 'present';
            }
        } else {
            $attendance->status = 'present';
        }

        $attendance->shift_type = $shiftType;
    }

    public static function isWithinGeofence($lat, $lng, $branchLat, $branchLng, $radius = 100)
    {
        if (!$lat || !$lng || !$branchLat || !$branchLng) {
            return false;
        }

        $earthRadius = 6371000; // in meters
        $latDiff = deg2rad($branchLat - $lat);
        $lngDiff = deg2rad($branchLng - $lng);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat)) * cos(deg2rad($branchLat)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance <= $radius;
    }

    public function checkIfTodayIsHoliday($today)
    {
        // Simple mock or query database holidays
        $holidayClass = '\App\Models\Leave\Holiday'; // Or similar
        if (class_exists($holidayClass)) {
            $date = Carbon::parse($today)->format('Y-m-d');
            $holiday = $holidayClass::where('date', $date)->first();
            if ($holiday) {
                return ['isHoliday' => true, 'name' => $holiday->name];
            }
        }
        return ['isHoliday' => false, 'name' => null];
    }
}
