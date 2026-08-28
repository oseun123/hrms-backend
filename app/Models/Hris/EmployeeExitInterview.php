<?php

namespace App\Models\Hris;

use App\Models\BaseModel;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeExitInterview extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'employee_exit_interviews';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'interviewer_id',
        'interview_date',
        'primary_reason_for_leaving',
        'secondary_reasons',
        'overall_experience_rating',
        'management_rating',
        'compensation_rating',
        'work_life_balance_rating',
        'growth_opportunities_rating',
        'culture_rating',
        'what_went_well',
        'what_could_improve',
        'additional_comments',
        'handover_completed',
        'assets_returned',
        'rehire_eligibility',
        'rehire_notes',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'interview_date' => 'date',
            'secondary_reasons' => 'array',
            'overall_experience_rating' => 'integer',
            'management_rating' => 'integer',
            'compensation_rating' => 'integer',
            'work_life_balance_rating' => 'integer',
            'growth_opportunities_rating' => 'integer',
            'culture_rating' => 'integer',
            'handover_completed' => 'boolean',
            'assets_returned' => 'boolean',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function interviewer()
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }
}
