<?php

namespace App\Models\Hris;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeMedicalDetail extends BaseModel
{
    use Auditable, HasFactory;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'blood_group',
        'genotype',
        'height',
        'weight',
        'allergies',
        'chronic_conditions',
        'medications',
        'disabilities',
        'health_insurance_provider',
        'health_insurance_number',
        'health_insurance_expiry',
        'emergency_medical_info',
        'last_medical_checkup',
        'next_medical_checkup',
        'doctor_name',
        'doctor_phone',
        'hospital_preference',
    ];

    protected $casts = [
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'health_insurance_expiry' => 'date',
        'last_medical_checkup' => 'date',
        'next_medical_checkup' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
