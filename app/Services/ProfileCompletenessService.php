<?php

namespace App\Services;

use App\Models\Hris\Employee;
use App\Models\Hris\EmployeeProfileCompleteness;

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
            'work_experience_completion' => $this->calculateWorkExperience($employee),
            'certification_completion' => $this->calculateCertifications($employee),
            'skills_completion' => $this->calculateSkills($employee),
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

        // Auto-complete onboarding when profile reaches 100%
        if (round($overall, 2) == 100) {
            $onboardingStatus = $employee->onboardingStatus;
            if ($onboardingStatus && !$onboardingStatus->onboarding_completed) {
                $onboardingStatus->update([
                    'onboarding_completed' => true,
                    'onboarding_completed_at' => now(),
                ]);
            }
        }
    }

    /**
     * Get detailed breakdown of profile completeness
     */
    public function getBreakdown(Employee $employee): array
    {
        return [
            'overall_completion' => $employee->profileCompleteness ? $employee->profileCompleteness->overall_completion : 0,
            'personal_details' => $this->getBasicInfoBreakdown($employee),
            'employment_details' => $this->getEmploymentInfoBreakdown($employee),
            'contact_details' => $this->getContactInfoBreakdown($employee),
            'financial_details' => $this->getFinancialInfoBreakdown($employee),
            'medical_details' => $this->getMedicalInfoBreakdown($employee),
            'education' => $this->getCollectionBreakdown($employee->education(), 'education', 3),
            'work_experience' => $this->getCollectionBreakdown($employee->workExperience(), 'work experience', 2),
            'skills' => $this->getCollectionBreakdown($employee->skills(), 'skills', 3),
            'certifications' => $this->getCollectionBreakdown($employee->certifications(), 'certifications', 2),
            'addresses' => $this->getCollectionBreakdown($employee->addresses(), 'addresses', 2),
            'emergency_contacts' => $this->getCollectionBreakdown($employee->emergencyContacts(), 'emergency contacts', 2),
        ];
    }

    private function getBasicInfoBreakdown(Employee $employee): array
    {
        $fields = ['first_name', 'last_name', 'date_of_birth', 'gender', 'marital_status', 'nationality', 'national_id', 'photo'];
        return $this->getFieldsBreakdown($employee, $fields, 'personal information');
    }

    private function getEmploymentInfoBreakdown(Employee $employee): array
    {
        $fields = ['work_email', 'department_id', 'position_id', 'employment_type', 'employment_status', 'hire_date'];
        return $this->getFieldsBreakdown($employee->employmentDetails, $fields, 'employment details');
    }

    private function getContactInfoBreakdown(Employee $employee): array
    {
        $fields = ['personal_email', 'mobile_phone', 'home_phone', 'work_phone', 'preferred_contact_method'];
        return $this->getFieldsBreakdown($employee->contactDetails, $fields, 'contact details');
    }

    private function getFinancialInfoBreakdown(Employee $employee): array
    {
        $fields = ['bank_name', 'account_number', 'account_name', 'current_salary', 'salary_currency', 'payment_frequency'];
        return $this->getFieldsBreakdown($employee->financialDetails, $fields, 'financial details');
    }

    private function getMedicalInfoBreakdown(Employee $employee): array
    {
        $fields = ['blood_group', 'genotype', 'allergies', 'chronic_conditions', 'emergency_medical_info', 'health_insurance_provider', 'health_insurance_number'];
        return $this->getFieldsBreakdown($employee->medicalDetails, $fields, 'medical information');
    }

    private function getFieldsBreakdown($model, array $fields, string $label): array
    {
        if (!$model) {
            return [
                'percentage' => 0,
                'message' => "Please provide $label.",
                'missing_fields' => $fields,
            ];
        }

        $missing = [];
        foreach ($fields as $field) {
            if (empty($model->$field)) {
                $missing[] = $field;
            }
        }

        $percentage = $this->calculateFieldsPercentage($model, $fields);

        return [
            'percentage' => round($percentage, 2),
            'message' => count($missing) > 0 ? 'Please fill in the missing fields.' : 'Section complete.',
            'missing_fields' => $missing,
        ];
    }

    private function getCollectionBreakdown($relation, string $itemName, int $target): array
    {
        $count = $relation->count();
        $percentage = 0;

        if ($target === 3) {
            if ($count >= 3) $percentage = 100;
            elseif ($count === 2) $percentage = 75;
            elseif ($count === 1) $percentage = 50;
        } else {
            if ($count >= 2) $percentage = 100;
            elseif ($count === 1) $percentage = 70;
        }

        $remaining = max(0, $target - $count);
        $message = $remaining > 0 ? "Add $remaining more $itemName to reach 100%." : 'Section complete.';

        return [
            'percentage' => $percentage,
            'message' => $message,
        ];
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
        if (! $employee->employmentDetails) {
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
        if (! $employee->contactDetails) {
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
        if (! $employee->financialDetails) {
            return 0;
        }

        $fields = [
            'bank_name',
            'account_number',
            'account_name',
            'current_salary',
            'salary_currency',
            'payment_frequency',
        ];

        return $this->calculateFieldsPercentage($employee->financialDetails, $fields);
    }

    /**
     * Calculate medical info completeness
     */
    private function calculateMedicalInfo(Employee $employee): float
    {
        if (! $employee->medicalDetails) {
            return 0;
        }

        $fields = [
            'blood_group',
            'genotype',
            'allergies',
            'chronic_conditions',
            'emergency_medical_info',
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
     * Calculate skills completeness
     */
    private function calculateSkills(Employee $employee): float
    {
        $count = $employee->skills()->count();

        if ($count === 0) {
            return 0;
        } elseif ($count === 1) {
            return 50;
        } elseif ($count === 2) {
            return 80;
        } else {
            return 100;
        }
    }

    /**
     * Calculate work experience completeness
     */
    private function calculateWorkExperience(Employee $employee): float
    {
        $count = $employee->workExperience()->count();

        if ($count === 0) {
            return 0;
        } elseif ($count === 1) {
            return 70;
        } else {
            return 100;
        }
    }

    /**
     * Calculate certifications completeness
     */
    private function calculateCertifications(Employee $employee): float
    {
        $count = $employee->certifications()->count();

        if ($count === 0) {
            return 0;
        } elseif ($count === 1) {
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
        if (! $model) {
            return 0;
        }

        $filledCount = 0;
        $totalFields = count($fields);

        foreach ($fields as $field) {
            if (! empty($model->$field)) {
                $filledCount++;
            }
        }

        return ($filledCount / $totalFields) * 100;
    }
}
