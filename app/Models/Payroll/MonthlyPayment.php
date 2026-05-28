<?php

namespace App\Models\Payroll;

use App\Traits\Auditable;
use App\Models\Hris\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyPayment extends Model
{
    use HasFactory, Auditable;

    protected $table = 'payroll_monthly_payments';

    protected $fillable = [
        'batch_payment_id',
        'employee_id',
        'gross_salary',
        'net_salary',
        'tax_amount',
        'total_relief',
        'pension_ee',
        'pension_er',
        'is_payslip_sent',
    ];

    protected function casts(): array
    {
        return [
            'gross_salary' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_relief' => 'decimal:2',
            'pension_ee' => 'decimal:2',
            'pension_er' => 'decimal:2',
            'is_payslip_sent' => 'boolean',
        ];
    }

    public function batchPayment()
    {
        return $this->belongsTo(BatchPayment::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function items()
    {
        return $this->hasMany(MonthlyPaymentItem::class);
    }
}
