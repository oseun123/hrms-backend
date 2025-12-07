<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class EmployeeEducation extends Model
{
    use HasFactory, SoftDeletes, Auditable;

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
