<?php

namespace App\Models\Performance;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceObjective extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'tenant_id',
        'goal_id',
        'title',
        'description',
        'sequence_order',
    ];

    protected $casts = [
        'sequence_order' => 'integer',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function goal()
    {
        return $this->belongsTo(PerformanceGoal::class, 'goal_id');
    }

    public function measuresTargets()
    {
        return $this->morphMany(PerformanceMeasureTarget::class, 'measurable');
    }

    // Scopes
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForGoal($query, $goalId)
    {
        return $query->where('goal_id', $goalId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence_order');
    }
}
