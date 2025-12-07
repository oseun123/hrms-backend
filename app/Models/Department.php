<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'code',
        'name',
        'description',
        'manager_id',
        'cost_center',
        'location',
        'email',
        'phone',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Parent department
     */
    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    /**
     * Child departments
     */
    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /**
     * Department manager
     */
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Employees in this department (through employment details)
     */
    public function employees()
    {
        return $this->hasManyThrough(
            Employee::class,
            EmployeeEmploymentDetail::class,
            'department_id', // Foreign key on employee_employment_details table
            'id',            // Foreign key on employees table
            'id',            // Local key on departments table
            'employee_id'    // Local key on employee_employment_details table
        );
    }

    /**
     * Positions in this department
     */
    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    /**
     * User who created this record
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated this record
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for active departments
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
