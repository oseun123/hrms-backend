<?php

namespace App\Models\Performance;

use App\Models\BaseModel;
use App\Models\Hris\Employee;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppraisalReviewerConfig extends BaseModel
{
    use HasFactory, Auditable;

    protected $fillable = [
        'tenant_id',
        'level_number',
        'reviewer_type',
        'custom_reviewer_id',
        'is_required',
    ];

    protected $casts = [
        'level_number' => 'integer',
        'is_required' => 'boolean',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customReviewer()
    {
        return $this->belongsTo(Employee::class, 'custom_reviewer_id');
    }

    // Scopes
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForLevel($query, $level)
    {
        return $query->where('level_number', $level);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}
