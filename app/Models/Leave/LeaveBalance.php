<?php

namespace App\Models\Leave;

use App\Models\BaseModel;
use App\Models\Hris\Employee;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveBalance extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'leave_type_id',
        'year',
        'entitlement',
        'carried_forward',
        'accrued',
        'used',
        'pending_approval',
        'manual_adjustment',
    ];

    protected $appends = ['available_balance'];

    protected $casts = [
        'entitlement' => 'decimal:2',
        'carried_forward' => 'decimal:2',
        'accrued' => 'decimal:2',
        'used' => 'decimal:2',
        'pending_approval' => 'decimal:2',
        'manual_adjustment' => 'decimal:2',
    ];

    /**
     * Ensure pending_approval attribute is never negative when accessed.
     */
    public function getPendingApprovalAttribute($value)
    {
        return max(0, (float)$value);
    }

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

    /**
     * Get the total available balance.
     */
    public function getAvailableBalanceAttribute()
    {
        $total = (float)($this->entitlement ?? 0) +
            (float)($this->carried_forward ?? 0) +
            (float)($this->accrued ?? 0) +
            (float)($this->manual_adjustment ?? 0);

        $used = (float)($this->used ?? 0);

        // Per user request: Available should be Total - Used (Pending is shown separately)
        return $total - $used;
    }

    /**
     * Safely decrement pending_approval, ensuring it doesn't go below zero.
     */
    public function decrementPending(float $amount)
    {
        $this->pending_approval = max(0, (float)$this->pending_approval - $amount);
        return $this->save();
    }

    /**
     * Safely decrement used balance, ensuring it doesn't go below zero.
     */
    public function decrementUsed(float $amount)
    {
        $this->used = max(0, (float)$this->used - $amount);
        return $this->save();
    }
}
