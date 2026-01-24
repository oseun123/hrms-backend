<?php

namespace App\Models\Hris;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'category',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Employees with this skill
     */
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_skills')
            ->withPivot('proficiency_level', 'years_of_experience', 'last_used', 'is_certified', 'certification_name', 'certification_date')
            ->withTimestamps();
    }

    /**
     * Scope for active skills
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for tenant filtering
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
