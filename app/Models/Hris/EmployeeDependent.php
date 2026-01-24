<?php

namespace App\Models\Hris;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDependent extends BaseModel
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'name',
        'relationship',
        'date_of_birth',
        'gender',
        'national_id',
        'is_student',
        'is_disabled',
        'is_beneficiary',
        'beneficiary_percentage',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_student' => 'boolean',
        'is_disabled' => 'boolean',
        'is_beneficiary' => 'boolean',
        'beneficiary_percentage' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
