<?php

namespace App\Models\Performance;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeDeliverableDetail extends BaseModel
{
    use HasFactory, Auditable;

    protected $fillable = [
        'tenant_id',
        'employee_deliverable_id',
        'measure_target_id',
        'snapshot_measure',
        'snapshot_target',
        'snapshot_uom',
        'snapshot_weightage',
        'custom_target',
        'custom_weightage',
    ];

    protected $casts = [
        'custom_weightage' => 'decimal:2',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
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

    public function scopeForDeliverable($query, $deliverableId)
    {
        return $query->where('employee_deliverable_id', $deliverableId);
    }
}
