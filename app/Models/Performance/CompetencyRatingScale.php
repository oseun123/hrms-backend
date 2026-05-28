<?php

namespace App\Models\Performance;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompetencyRatingScale extends BaseModel
{
    use HasFactory, Auditable;

    protected $fillable = [
        'tenant_id',
        'competency_id',
        'scale_value',
        'scale_label',
        'scale_description',
    ];

    protected $casts = [
        'scale_value' => 'integer',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
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

    public function scopeForCompetency($query, $competencyId)
    {
        return $query->where('competency_id', $competencyId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('scale_value');
    }
}
