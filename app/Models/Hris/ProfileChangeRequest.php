<?php

namespace App\Models\Hris;

use App\Models\Tenant;
use App\Models\User;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileChangeRequest extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'employee_id',
        'section',
        'action',
        'current_data',
        'proposed_data',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'decline_reason',
        'notes',
    ];

    protected $casts = [
        'current_data' => 'array',
        'proposed_data' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the change request
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the employee who submitted the request
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who reviewed the request
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope to get pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get declined requests
     */
    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    /**
     * Check if employee has pending request for a section
     */
    public static function hasPendingRequest(int $employeeId, string $section): bool
    {
        return static::where('employee_id', $employeeId)
            ->where('section', $section)
            ->where('status', 'pending')
            ->exists();
    }
}
