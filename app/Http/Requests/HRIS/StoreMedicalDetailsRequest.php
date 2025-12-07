<?php

namespace App\Http\Requests\HRIS;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalDetailsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'genotype' => 'nullable|in:AA,AS,SS,AC,SC',
            'height' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'allergies' => 'nullable|string',
            'chronic_conditions' => 'nullable|string',
            'medications' => 'nullable|string',
            'disabilities' => 'nullable|string',
            'health_insurance_provider' => 'nullable|string|max:255',
            'health_insurance_number' => 'nullable|string|max:50',
            'health_insurance_expiry' => 'nullable|date',
            'emergency_medical_info' => 'nullable|string',
            'last_medical_checkup' => 'nullable|date',
            'next_medical_checkup' => 'nullable|date|after:today',
            'doctor_name' => 'nullable|string|max:255',
            'doctor_phone' => 'nullable|string|max:20',
            'hospital_preference' => 'nullable|string|max:255',
        ];
    }
}
