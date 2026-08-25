<?php

namespace App\Models\Attendance;

use App\Models\User;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'attendance_date',
        'correction_type',
        'old_time',
        'correct_time',
        'old_check_out',
        'correct_check_out',
        'reason',
        'supporting_document',
        'status',
        'approver_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function approvals()
    {
        return $this->hasMany(AttendanceCorrectionApproval::class);
    }
}
