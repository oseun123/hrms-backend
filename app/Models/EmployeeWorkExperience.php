<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeWorkExperience extends Model
{
    use HasFactory;

    protected $table = 'employee_work_experience';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'company',
        'position',
        'start_date',
        'end_date',
        'responsibilities',
        'reason_for_leaving',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Employee who has this work experience
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Check if this is current employment
     */
    public function getIsCurrentAttribute()
    {
        return is_null($this->end_date);
    }

    /**
     * Get duration in months
     */
    public function getDurationInMonthsAttribute()
    {
        $endDate = $this->end_date ?? now();
        return $this->start_date->diffInMonths($endDate);
    }
}
