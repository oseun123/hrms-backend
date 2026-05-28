<?php

namespace App\Models\Performance;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppraisalCompetencyScore extends BaseModel
{
    use HasFactory, Auditable;

    protected $fillable = [
        'tenant_id',
        'level_score_id',
        'competency_id',
        'score',
        'comments',
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

    public function competency()
    {
        return $this->belongsTo(Competency::class);
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

    public function scopeForCompetency($query, $competencyId)
    {
        return $query->where('competency_id', $competencyId);
    }
}
