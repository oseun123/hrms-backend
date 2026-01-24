<?php

namespace App\Models\Leave;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeavePolicy extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'tenant_id',
        'leave_type_id',
        'leave_group_id',
        'entitlement_days',
        'accrual_frequency',
        'is_prorated',
        'allow_carry_forward',
        'max_carry_forward_days',
        'carry_forward_expiry_months',
        'allow_encashment',
        'max_encashment_days',
        'min_service_days',
        'max_consecutive_days',
        'notice_period_days',
        'allow_negative_balance',
        'max_negative_days',
        'include_public_holidays',
        'include_weekends',
        'allow_backdated_leave',
        'max_backdated_days',
        'approval_stages',
        'requires_hr_approval',
        'leave_workflow_id',
        'is_active',
    ];

    protected $casts = [
        'entitlement_days' => 'decimal:2',
        'is_prorated' => 'boolean',
        'allow_carry_forward' => 'boolean',
        'max_carry_forward_days' => 'decimal:2',
        'allow_encashment' => 'boolean',
        'max_encashment_days' => 'decimal:2',
        'allow_negative_balance' => 'boolean',
        'max_negative_days' => 'decimal:2',
        'include_public_holidays' => 'boolean',
        'include_weekends' => 'boolean',
        'allow_backdated_leave' => 'boolean',
        'requires_hr_approval' => 'boolean',
        'approval_stages' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function leaveGroup()
    {
        return $this->belongsTo(LeaveGroup::class);
    }

    public function workflow()
    {
        return $this->belongsTo(LeaveWorkflow::class, 'leave_workflow_id');
    }
}
