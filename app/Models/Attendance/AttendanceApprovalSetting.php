<?php

namespace App\Models\Attendance;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceApprovalSetting extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'first_approver',
        'second_approver',
        'third_approver',
        'lateness_fee_approval',
        'absenteeism_fee_approval',
        'overtime_fee_approval',
        'overtime_time_fee'
    ];

    protected $casts = [
        'overtime_time_fee' => 'boolean'
    ];
}
