<?php

namespace App\Models\Performance;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Competency extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'weightage',
        'is_seeded',
        'is_active',
    ];

    protected $casts = [
        'weightage' => 'decimal:2',
        'is_seeded' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function ratingScales()
    {
        return $this->hasMany(CompetencyRatingScale::class);
    }

    public function competencyScores()
    {
        return $this->hasMany(AppraisalCompetencyScore::class);
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

    public function scopeSeeded($query)
    {
        return $query->where('is_seeded', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_seeded', false);
    }
}
