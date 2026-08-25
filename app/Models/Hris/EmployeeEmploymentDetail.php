<?php

namespace App\Models\Hris;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeEmploymentDetail extends BaseModel
{
    use Auditable, HasFactory;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'work_email',
        'department_id',
        'branch_id',
        'position_id',
        'manager_id',
        'team_lead_id',
        'secondary_manager_id',
        'employment_type',
        'employment_status',
        'hire_date',
        'probation_end_date',
        'probation_status',
        'confirmation_date',
        'contract_start_date',
        'contract_end_date',
        'termination_date',
        'termination_type',
        'termination_reason',
        'notice_period_days',
        'work_location',
        'work_schedule',
        'shift',
        'remote_work_eligible',
        'leave_group_id',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'probation_end_date' => 'date',
            'confirmation_date' => 'date',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'termination_date' => 'date',
            'remote_work_eligible' => 'boolean',
        ];
    }

    public function leaveGroup()
    {
        return $this->belongsTo(\App\Models\Leave\LeaveGroup::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function teamLead()
    {
        return $this->belongsTo(Employee::class, 'team_lead_id');
    }

    public function secondaryManager()
    {
        return $this->belongsTo(Employee::class, 'secondary_manager_id');
    }
}
