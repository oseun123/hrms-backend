<?php

namespace App\Models\Performance;

use App\Models\BaseModel;
use App\Models\Hris\Employee;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppraisalSubmission extends BaseModel
{
    use HasFactory, Auditable;

    protected $fillable = [
        'tenant_id',
        'appraisal_id',
        'employee_id',
        'current_level',
        'status',
        'submitted_at',
        'completed_at',
        'reviewer_levels',
        'reviewer_config',
        'results_weight',
        'competency_weight',
        'final_score_level',
        'enforce_submit_back',
    ];

    protected $casts = [
        'current_level' => 'integer',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'reviewer_levels' => 'integer',
        'reviewer_config' => 'array',
        'results_weight' => 'decimal:2',
        'competency_weight' => 'decimal:2',
        'final_score_level' => 'integer',
        'enforce_submit_back' => 'boolean',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function levelScores()
    {
        return $this->hasMany(AppraisalLevelScore::class, 'submission_id');
    }

    public function attachments()
    {
        return $this->hasMany(AppraisalAttachment::class, 'submission_id');
    }

    // Scopes
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForAppraisal($query, $appraisalId)
    {
        return $query->where('appraisal_id', $appraisalId);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    public function scopeAtLevel($query, $level)
    {
        return $query->where('current_level', $level);
    }
}
