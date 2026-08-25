<?php

namespace App\Models\Attendance;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceWorkScheduleSetting extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'start_time',
        'end_time',
        'evening_start_time',
        'evening_end_time',
        'arrival_grace',
        'departure_grace',
        'work_days',
        'overtime_days',
        'is_shift',
        'enable_payroll',
        'enable_geofence',
        'geofence_radius',
        'enable_webcam',
        'webcam_verification_type',
        'time_zone'
    ];

    protected $casts = [
        'work_days' => 'array',
        'overtime_days' => 'array',
        'is_shift' => 'boolean',
        'enable_payroll' => 'boolean',
        'enable_geofence' => 'boolean',
        'enable_webcam' => 'boolean',
    ];
}
