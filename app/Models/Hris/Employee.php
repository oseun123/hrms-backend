<?php

namespace App\Models\Hris;

use App\Models\BaseModel;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

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

    // Relationships
    public function leaveRequests()
    {
        return $this->hasMany(\App\Models\Leave\LeaveRequest::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(\App\Models\Leave\LeaveBalance::class);
    }

    public function leaveAdjustments()
    {
        return $this->hasMany(\App\Models\Leave\LeaveAdjustment::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'date_of_birth' => 'date',
        ];
    }

    protected $appends = ['full_name', 'photo_url'];

    // Accessors
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function getPhotoUrlAttribute()
    {
        if (!$this->photo) {
            return null;
        }

        // If it's already a URL, return it
        if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
            return $this->photo;
        }

        // Otherwise, assume it's a path in storage
        return asset('storage/' . $this->photo);
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

    /**
     * Retrieve the model for a bound value.
     * This ensures route model binding is scoped to the current tenant.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Get the authenticated user's tenant_id
        $tenantId = auth()->user() ? auth()->user()->tenant_id : null;

        if (! $tenantId) {
            return null;
        }

        // Scope the query to the current tenant
        return static::where('tenant_id', $tenantId)
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }
}
