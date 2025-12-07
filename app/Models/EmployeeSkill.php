<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'skill_id',
        'proficiency_level',
        'years_of_experience',
        'last_used',
        'is_certified',
        'certification_name',
        'certification_date',
    ];

    protected $casts = [
        'is_certified' => 'boolean',
        'years_of_experience' => 'decimal:1',
        'last_used' => 'date',
        'certification_date' => 'date',
    ];

    /**
     * Employee who has this skill
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * The skill
     */
    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }
}
