<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'change_type',
        'effective_date',
        'previous_value',
        'new_value',
        'reason',
        'notes',
        'approved_by',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'previous_value' => 'array',
        'new_value' => 'array',
    ];

    /**
     * Employee this history belongs to
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * User who approved this change
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * User who created this record
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for specific change type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('change_type', $type);
    }
}
