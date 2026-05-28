<?php

namespace App\Models\Performance;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppraisalGoalScore extends BaseModel
{
    use HasFactory, Auditable;

    protected $fillable = [
        'tenant_id',
        'level_score_id',
        'employee_deliverable_id',
        'measure_target_id',
        'score',
        'comments',
        'evidence_url',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function levelScore()
    {
        return $this->belongsTo(AppraisalLevelScore::class, 'level_score_id');
    }

    public function employeeDeliverable()
    {
        return $this->belongsTo(EmployeeDeliverable::class);
    }

    public function measureTarget()
    {
        return $this->belongsTo(PerformanceMeasureTarget::class, 'measure_target_id');
    }

    // Scopes
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForLevelScore($query, $levelScoreId)
    {
        return $query->where('level_score_id', $levelScoreId);
    }
}
