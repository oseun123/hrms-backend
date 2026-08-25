<?php

namespace App\Models\Hris;

use App\Models\User;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'address',
        'city',
        'state',
        'country',
        'email',
        'phone',
        'is_active',
        'is_default',
        'latitude',
        'longitude',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Contact persons for this branch
     */
    public function contactPersons()
    {
        return $this->belongsToMany(Employee::class, 'branch_contact_persons', 'branch_id', 'employee_id')
                    ->withTimestamps();
    }

    /**
     * Employees in this branch (through employment details)
     */
    public function employees()
    {
        return $this->hasManyThrough(
            Employee::class,
            EmployeeEmploymentDetail::class,
            'branch_id',   // Foreign key on employee_employment_details table
            'id',          // Foreign key on employees table
            'id',          // Local key on branches table
            'employee_id'  // Local key on employee_employment_details table
        );
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
     * Scope for active branches
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
