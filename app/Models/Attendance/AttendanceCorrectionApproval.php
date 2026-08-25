<?php

namespace App\Models\Attendance;

use App\Models\User;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceCorrectionApproval extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'attendance_correction_request_id',
        'approver_id',
        'level',
        'status',
        'actioned_at'
    ];

    protected $casts = [
        'actioned_at' => 'datetime'
    ];

    public function request()
    {
        return $this->belongsTo(AttendanceCorrectionRequest::class, 'attendance_correction_request_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
