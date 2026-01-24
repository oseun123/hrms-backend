<?php

namespace App\Models\Leave;

use App\Models\BaseModel;
use App\Models\Hris\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveAdjustment extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'leave_type_id',
        'adjustment_amount',
        'type',
        'reason',
        'adjusted_by',
    ];

    protected $casts = [
        'adjustment_amount' => 'decimal:2',
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

    public function adjuster()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
