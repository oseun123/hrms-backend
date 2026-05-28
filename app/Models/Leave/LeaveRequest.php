<?php

namespace App\Models\Leave;

use App\Models\BaseModel;
use App\Models\Hris\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'duration_days',
        'reason',
        'attachment_path',
        'status',
        'decline_reason',
        'applied_at',
        'cancelled_by',
        'cancelled_at',
        'request_leave_allowance',
    ];

    protected $appends = ['attachment_url'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'duration_days' => 'decimal:2',
        'applied_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'request_leave_allowance' => 'boolean',
    ];


    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvals()
    {
        return $this->hasMany(LeaveApproval::class);
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function getAttachmentUrlAttribute()
    {
        if ($this->attachment_path) {
            return app(\App\Services\FileUploadService::class)->getUrl($this->attachment_path);
        }
        return null;
    }
}
