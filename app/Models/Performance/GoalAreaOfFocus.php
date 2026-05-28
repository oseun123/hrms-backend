<?php

namespace App\Models\Performance;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoalAreaOfFocus extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'goal_areas_of_focus';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'is_seeded',
        'is_active',
    ];

    protected $casts = [
        'is_seeded' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function goals()
    {
        return $this->hasMany(PerformanceGoal::class, 'area_of_focus_id');
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
