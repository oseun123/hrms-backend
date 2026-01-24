<?php

namespace App\Models\Hris;

use App\Models\Tenant;
use App\Models\User;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncorrectDetailReport extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'employee_id',
        'section',
        'field_name',
        'current_value',
        'reported_correct_value',
        'description',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the report
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the employee who reported the issue
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who resolved the report
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Scope to get pending reports
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get resolved reports
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope to get dismissed reports
     */
    public function scopeDismissed($query)
    {
        return $query->where('status', 'dismissed');
    }
}
