<?php

namespace App\Models\Leave;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveApproval extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'tenant_id',
        'leave_request_id',
        'approver_id',
        'level',
        'status',
        'comments',
        'nudged_at',
        'actioned_at',
    ];

    protected $casts = [
        'level' => 'integer',
        'nudged_at' => 'datetime',
        'actioned_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
