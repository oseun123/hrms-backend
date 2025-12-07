<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class EmployeeEmploymentDetail extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'work_email',
        'department_id',
        'position_id',
        'manager_id',
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
    ];

    protected $casts = [
        'hire_date' => 'date',
        'probation_end_date' => 'date',
        'confirmation_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'termination_date' => 'date',
        'remote_work_eligible' => 'boolean',
    ];

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
}
