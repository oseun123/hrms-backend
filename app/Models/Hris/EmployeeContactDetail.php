<?php

namespace App\Models\Hris;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeContactDetail extends BaseModel
{
    use Auditable, HasFactory;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'personal_email',
        'work_phone',
        'mobile_phone',
        'home_phone',
        'whatsapp_number',
        'linkedin_url',
        'skype_id',
        'other_contact',
        'preferred_contact_method',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
