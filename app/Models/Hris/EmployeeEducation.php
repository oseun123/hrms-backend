<?php

namespace App\Models\Hris;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeEducation extends BaseModel
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'institution',
        'degree',
        'field_of_study',
        'start_date',
        'end_date',
        'grade',
        'is_highest',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_highest' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
