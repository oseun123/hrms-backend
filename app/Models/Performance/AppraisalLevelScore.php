<?php

namespace App\Models\Performance;

use App\Models\BaseModel;
use App\Models\Hris\Employee;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppraisalLevelScore extends BaseModel
{
    use HasFactory, Auditable;

    protected $fillable = [
        'tenant_id',
        'submission_id',
        'reviewer_level',
        'reviewer_id',
        'goals_score',
        'goals_weighted_score',
        'competency_score',
        'competency_weighted_score',
        'final_score',
        'comments',
        'submitted_at',
    ];

    protected $casts = [
        'reviewer_level' => 'integer',
        'goals_score' => 'decimal:2',
        'goals_weighted_score' => 'decimal:2',
        'competency_score' => 'decimal:2',
        'competency_weighted_score' => 'decimal:2',
        'final_score' => 'decimal:2',
        'submitted_at' => 'datetime',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function submission()
    {
        return $this->belongsTo(AppraisalSubmission::class, 'submission_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(Employee::class, 'reviewer_id');
    }

    public function goalScores()
    {
        return $this->hasMany(AppraisalGoalScore::class, 'level_score_id');
    }

    public function competencyScores()
    {
        return $this->hasMany(AppraisalCompetencyScore::class, 'level_score_id');
    }

    // Scopes
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForSubmission($query, $submissionId)
    {
        return $query->where('submission_id', $submissionId);
    }

    public function scopeForLevel($query, $level)
    {
        return $query->where('reviewer_level', $level);
    }

    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at');
    }
}
