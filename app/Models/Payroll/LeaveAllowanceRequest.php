<?php

namespace App\Models\Payroll;

use App\Models\BaseModel;
use App\Models\Hris\Employee;
use App\Models\Leave\LeaveRequest;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveAllowanceRequest extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'leave_allowance_requests';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'leave_request_id',
        'annual_structure_item_id',
        'amount',
        'leave_year',
        'status',
        'approved_by',
        'approved_at',
        'decline_reason',
        'batch_payment_id',
        'monthly_payment_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function annualStructureItem()
    {
        return $this->belongsTo(AnnualSalaryStructureItem::class, 'annual_structure_item_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function batch()
    {
        return $this->belongsTo(BatchPayment::class, 'batch_payment_id');
    }

    public function monthlyPayment()
    {
        return $this->belongsTo(MonthlyPayment::class, 'monthly_payment_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    public function scopeNotPaid($query)
    {
        return $query->whereNull('batch_payment_id');
    }

    public function scopeForLeaveYear($query, string $leaveYear)
    {
        return $query->where('leave_year', $leaveYear);
    }
}
