<?php

namespace App\Models\Hris;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeOnboardingStatus extends BaseModel
{
    use HasFactory;

    protected $table = 'employee_onboarding_status';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'user_created',
        'welcome_email_sent',
        'welcome_email_sent_at',
        'password_reset_sent',
        'password_reset_sent_at',
        'first_login_completed',
        'first_login_at',
        'profile_completed',
        'onboarding_completed',
        'onboarding_completed_at',
    ];

    protected $casts = [
        'user_created' => 'boolean',
        'welcome_email_sent' => 'boolean',
        'welcome_email_sent_at' => 'datetime',
        'password_reset_sent' => 'boolean',
        'password_reset_sent_at' => 'datetime',
        'first_login_completed' => 'boolean',
        'first_login_at' => 'datetime',
        'profile_completed' => 'boolean',
        'onboarding_completed' => 'boolean',
        'onboarding_completed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
