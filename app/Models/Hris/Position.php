<?php

namespace App\Models\Hris;

use App\Models\User;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'department_id',
        'level_id',
        'grade_id',
        'code',
        'title',
        'description',
        'min_salary',
        'max_salary',
        'reports_to',
        'required_qualifications',
        'responsibilities',
        'is_active',
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
            'min_salary' => 'decimal:2',
            'max_salary' => 'decimal:2',
        ];
    }

    /**
     * Department this position belongs to
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Level of this position
     */
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Grade of this position
     */
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Position this role reports to
     */
    public function reportsTo()
    {
        return $this->belongsTo(Position::class, 'reports_to');
    }

    /**
     * Positions that report to this position
     */
    public function subordinates()
    {
        return $this->hasMany(Position::class, 'reports_to');
    }

    /**
     * Employees in this position (through employment details)
     */
    public function employees()
    {
        return $this->hasManyThrough(
            Employee::class,
            EmployeeEmploymentDetail::class,
            'position_id',  // Foreign key on employee_employment_details table
            'id',           // Foreign key on employees table
            'id',           // Local key on positions table
            'employee_id'   // Local key on employee_employment_details table
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
     * Scope for active positions
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
