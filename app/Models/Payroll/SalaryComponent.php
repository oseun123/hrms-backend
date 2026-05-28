<?php

namespace App\Models\Payroll;

use App\Traits\Auditable;
use App\Models\User;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryComponent extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'payroll_salary_components';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'type',
        'category',
        'is_taxable',
        'is_tax_deductible',
        'calculation_type',
        'amount_value',
        'formula',
        'is_active',
        'is_system_defined',
        'show_on_payslip',
        'is_pensionable',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_taxable' => 'boolean',
            'is_tax_deductible' => 'boolean',
            'amount_value' => 'decimal:2',
            'is_active' => 'boolean',
            'is_system_defined' => 'boolean',
            'show_on_payslip' => 'boolean',
            'is_pensionable' => 'boolean',
        ];
    }

    public function payGroups()
    {
        return $this->belongsToMany(PayGroup::class, 'payroll_pay_group_components', 'component_id', 'pay_group_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
