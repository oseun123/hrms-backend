<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'employee_number',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender',
        'marital_status',
        'nationality',
        'national_id',
        'passport_number',
        'photo',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'date_of_birth' => 'date',
    ];

    protected $appends = ['full_name'];

    // Accessors
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employmentDetails()
    {
        return $this->hasOne(EmployeeEmploymentDetail::class);
    }

    public function contactDetails()
    {
        return $this->hasOne(EmployeeContactDetail::class);
    }

    public function financialDetails()
    {
        return $this->hasOne(EmployeeFinancialDetail::class);
    }

    public function medicalDetails()
    {
        return $this->hasOne(EmployeeMedicalDetail::class);
    }

    public function addresses()
    {
        return $this->hasMany(EmployeeAddress::class);
    }

    public function emergencyContacts()
    {
        return $this->hasMany(EmployeeEmergencyContact::class);
    }

    public function dependents()
    {
        return $this->hasMany(EmployeeDependent::class);
    }

    public function education()
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    public function workExperience()
    {
        return $this->hasMany(EmployeeWorkExperience::class);
    }

    public function skills()
    {
        return $this->hasMany(EmployeeSkill::class);
    }

    public function certifications()
    {
        return $this->hasMany(EmployeeCertification::class);
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function history()
    {
        return $this->hasMany(EmployeeHistory::class);
    }

    public function customFields()
    {
        return $this->hasMany(EmployeeCustomField::class);
    }

    public function profileCompleteness()
    {
        return $this->hasOne(EmployeeProfileCompleteness::class)->from('employee_profile_completeness');
    }

    public function onboardingStatus()
    {
        return $this->hasOne(EmployeeOnboardingStatus::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
