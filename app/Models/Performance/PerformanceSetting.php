<?php

namespace App\Models\Performance;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerformanceSetting extends BaseModel
{
    use HasFactory, Auditable;

    protected $fillable = [
        'tenant_id',
        'cycle_type',
        'reviewer_levels',
        'final_score_level',
        'results_weight',
        'competency_weight',
        'goal_structure',
        'enforce_submit_back',
        'reviewer_config',
    ];

    protected $casts = [
        'reviewer_levels' => 'integer',
        'final_score_level' => 'integer',
        'results_weight' => 'decimal:2',
        'competency_weight' => 'decimal:2',
        'enforce_submit_back' => 'boolean',
        'reviewer_config' => 'array',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
