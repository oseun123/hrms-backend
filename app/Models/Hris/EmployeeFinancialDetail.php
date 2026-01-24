<?php

namespace App\Models\Hris;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeFinancialDetail extends BaseModel
{
    use Auditable, HasFactory;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'bank_name',
        'bank_branch',
        'account_number',
        'account_name',
        'account_type',
        'swift_code',
        'iban',
        'tax_id',
        'tax_status',
        'social_security_number',
        'pension_number',
        'insurance_number',
        'current_salary',
        'salary_currency',
        'payment_frequency',
        'payment_method',
    ];

    protected $casts = [
        'current_salary' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
