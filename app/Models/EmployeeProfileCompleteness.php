<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeProfileCompleteness extends Model
{
    use HasFactory;

    protected $table = 'employee_profile_completeness';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'overall_completion',
        'basic_info_completion',
        'employment_completion',
        'contact_completion',
        'financial_completion',
        'medical_completion',
        'address_completion',
        'emergency_contact_completion',
        'education_completion',
        'last_calculated_at',
    ];

    protected $casts = [
        'overall_completion' => 'decimal:2',
        'basic_info_completion' => 'decimal:2',
        'employment_completion' => 'decimal:2',
        'contact_completion' => 'decimal:2',
        'financial_completion' => 'decimal:2',
        'medical_completion' => 'decimal:2',
        'address_completion' => 'decimal:2',
        'emergency_contact_completion' => 'decimal:2',
        'education_completion' => 'decimal:2',
        'last_calculated_at' => 'datetime',
    ];

    /**
     * Employee this completeness record belongs to
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Check if profile is complete
     */
    public function getIsCompleteAttribute()
    {
        return $this->overall_completion >= 100;
    }

    /**
     * Scope for incomplete profiles
     */
    public function scopeIncomplete($query)
    {
        return $query->where('overall_completion', '<', 100);
    }

    /**
     * Scope for profiles below threshold
     */
    public function scopeBelowThreshold($query, $threshold = 50)
    {
        return $query->where('overall_completion', '<', $threshold);
    }
}
