<?php

namespace App\Models\Performance;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceGoal extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'tenant_id',
        'area_of_focus_id',
        'title',
        'description',
        'goal_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function areaOfFocus()
    {
        return $this->belongsTo(GoalAreaOfFocus::class, 'area_of_focus_id');
    }

    public function objectives()
    {
        return $this->hasMany(PerformanceObjective::class, 'goal_id');
    }

    public function measuresTargets()
    {
        return $this->morphMany(PerformanceMeasureTarget::class, 'measurable');
    }

    public function employeeDeliverables()
    {
        return $this->hasMany(EmployeeDeliverable::class, 'goal_id');
    }

    // Scopes
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSimple($query)
    {
        return $query->where('goal_type', 'simple');
    }

    public function scopeComplex($query)
    {
        return $query->where('goal_type', 'complex');
    }
}
