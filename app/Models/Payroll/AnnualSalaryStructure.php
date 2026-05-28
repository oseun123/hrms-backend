<?php

namespace App\Models\Payroll;

use App\Traits\Auditable;
use App\Models\BaseModel;
use App\Models\Hris\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnnualSalaryStructure extends BaseModel
{
    use HasFactory, Auditable;

    protected $table = 'payroll_annual_salary_structures';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'pay_group_id',
        'wage_item_id',
        'total_annual_gross',
        'total_annual_taxable',
        'total_annual_tax',
        'total_annual_pension_ee',
        'total_annual_pension_er',
        'total_annual_relief',
        'total_annual_net',
        'status',
        'is_altered',
    ];

    protected function casts(): array
    {
        return [
            'total_annual_gross' => 'decimal:2',
            'total_annual_taxable' => 'decimal:2',
            'total_annual_tax' => 'decimal:2',
            'total_annual_pension_ee' => 'decimal:2',
            'total_annual_pension_er' => 'decimal:2',
            'total_annual_relief' => 'decimal:2',
            'total_annual_net' => 'decimal:2',
            'is_altered' => 'boolean',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payGroup()
    {
        return $this->belongsTo(PayGroup::class);
    }

    public function wageItem()
    {
        return $this->belongsTo(WageItem::class);
    }

    public function items()
    {
        return $this->hasMany(AnnualSalaryStructureItem::class, 'annual_salary_structure_id');
    }
}
