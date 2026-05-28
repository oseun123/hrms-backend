<?php

namespace App\Models\Performance;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceMeasureTarget extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'performance_measures_targets';

    protected $fillable = [
        'tenant_id',
        'measurable_type',
        'measurable_id',
        'measure_description',
        'target_description',
        'weightage',
        'uom',
    ];

    protected $casts = [
        'weightage' => 'decimal:2',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function measurable()
    {
        return $this->morphTo();
    }

    public function deliverableDetails()
    {
        return $this->hasMany(EmployeeDeliverableDetail::class, 'measure_target_id');
    }

    public function goalScores()
    {
        return $this->hasMany(AppraisalGoalScore::class, 'measure_target_id');
    }

    // Scopes
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
