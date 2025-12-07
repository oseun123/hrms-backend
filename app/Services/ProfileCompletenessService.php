<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeProfileCompleteness;

class ProfileCompletenessService
{
    /**
     * Calculate profile completeness for an employee
     */
    public function calculate(Employee $employee): void
    {
        $percentages = [
            'basic_info_completion' => $this->calculateBasicInfo($employee),
            'employment_completion' => $this->calculateEmploymentInfo($employee),
            'contact_completion' => $this->calculateContactInfo($employee),
            'financial_completion' => $this->calculateFinancialInfo($employee),
            'medical_completion' => $this->calculateMedicalInfo($employee),
            'education_completion' => $this->calculateEducation($employee),
            'address_completion' => $this->calculateAddresses($employee),
            'emergency_contact_completion' => $this->calculateEmergencyContacts($employee),
        ];

        // Calculate overall percentage (average of all sections)
        $overall = array_sum($percentages) / count($percentages);

        // Update or create profile completeness record
        EmployeeProfileCompleteness::updateOrCreate(
            ['employee_id' => $employee->id],
            array_merge($percentages, [
                'tenant_id' => $employee->tenant_id,
                'overall_completion' => round($overall, 2),
                'last_calculated_at' => now(),
            ])
        );
    }

    /**
     * Calculate basic info completeness
     */
    private function calculateBasicInfo(Employee $employee): float
    {
        $fields = [
            'first_name',
            'last_name',
            'date_of_birth',
            'gender',
            'marital_status',
            'nationality',
            'national_id',
            'photo',
        ];

        return $this->calculateFieldsPercentage($employee, $fields);
    }

    /**
     * Calculate employment info completeness
     */
    private function calculateEmploymentInfo(Employee $employee): float
    {
        if (!$employee->employmentDetails) {
            return 0;
        }

        $fields = [
            'work_email',
            'department_id',
            'position_id',
            'employment_type',
            'employment_status',
            'hire_date',
        ];

        return $this->calculateFieldsPercentage($employee->employmentDetails, $fields);
    }

    /**
     * Calculate contact info completeness
     */
    private function calculateContactInfo(Employee $employee): float
    {
        if (!$employee->contactDetails) {
            return 0;
        }

        $fields = [
            'personal_email',
            'mobile_phone',
            'home_phone',
            'work_phone',
            'preferred_contact_method',
        ];

        return $this->calculateFieldsPercentage($employee->contactDetails, $fields);
    }

    /**
     * Calculate financial info completeness
     */
    private function calculateFinancialInfo(Employee $employee): float
    {
        if (!$employee->financialDetails) {
            return 0;
        }

        $fields = [
            'bank_name',
            'account_number',
            'account_name',
            'basic_salary',
            'currency',
            'payment_frequency',
        ];

        return $this->calculateFieldsPercentage($employee->financialDetails, $fields);
    }

    /**
     * Calculate medical info completeness
     */
    private function calculateMedicalInfo(Employee $employee): float
    {
        if (!$employee->medicalDetails) {
            return 0;
        }

        $fields = [
            'blood_type',
            'genotype',
            'allergies',
            'chronic_conditions',
            'emergency_medical_contact',
            'health_insurance_provider',
            'health_insurance_number',
        ];

        return $this->calculateFieldsPercentage($employee->medicalDetails, $fields);
    }

    /**
     * Calculate education completeness
     */
    private function calculateEducation(Employee $employee): float
    {
        $educationCount = $employee->education()->count();

        if ($educationCount === 0) {
            return 0;
        } elseif ($educationCount === 1) {
            return 50;
        } elseif ($educationCount === 2) {
            return 75;
        } else {
            return 100;
        }
    }

    /**
     * Calculate addresses completeness
     */
    private function calculateAddresses(Employee $employee): float
    {
        $addressCount = $employee->addresses()->count();

        if ($addressCount === 0) {
            return 0;
        } elseif ($addressCount === 1) {
            return 70;
        } else {
            return 100;
        }
    }

    /**
     * Calculate emergency contacts completeness
     */
    private function calculateEmergencyContacts(Employee $employee): float
    {
        $contactCount = $employee->emergencyContacts()->count();

        if ($contactCount === 0) {
            return 0;
        } elseif ($contactCount === 1) {
            return 70;
        } else {
            return 100;
        }
    }

    /**
     * Calculate percentage of filled fields
     */
    private function calculateFieldsPercentage($model, array $fields): float
    {
        if (!$model) {
            return 0;
        }

        $filledCount = 0;
        $totalFields = count($fields);

        foreach ($fields as $field) {
            if (!empty($model->$field)) {
                $filledCount++;
            }
        }

        return ($filledCount / $totalFields) * 100;
    }
}
