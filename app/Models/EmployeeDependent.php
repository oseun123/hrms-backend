<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class EmployeeDependent extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'name',
        'relationship',
        'date_of_birth',
        'gender',
        'national_id',
        'is_beneficiary',
        'beneficiary_percentage',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_beneficiary' => 'boolean',
        'beneficiary_percentage' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
