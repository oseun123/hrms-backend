<?php

namespace App\Models\Performance;

use App\Models\BaseModel;
use App\Models\Hris\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDeliverable extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'goal_id',
        'snapshot_title',
        'snapshot_description',
        'snapshot_goal_type',
        'assigned_by',
        'assigned_at',
        'is_active',
        'activated_at',
        'activated_by',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function goal()
    {
        return $this->belongsTo(PerformanceGoal::class, 'goal_id');
    }

    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function activatedByUser()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function details()
    {
        return $this->hasMany(EmployeeDeliverableDetail::class);
    }

    public function goalScores()
    {
        return $this->hasMany(AppraisalGoalScore::class);
    }

    // Scopes
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
}
