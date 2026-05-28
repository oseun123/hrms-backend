<?php

namespace App\Models\Payroll;

use App\Traits\Auditable;
use App\Models\User;
use App\Models\BaseModel;
use App\Models\Hris\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayGroup extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'payroll_pay_groups';

    protected $fillable = [
        'tenant_id',
        'name',
        'annual_gross',
        'annual_rent',
        'tax_scheme_id',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'annual_gross' => 'decimal:2',
            'annual_rent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function taxScheme()
    {
        return $this->belongsTo(TaxScheme::class);
    }

    public function wageItems()
    {
        return $this->belongsToMany(WageItem::class, 'payroll_pay_group_wage_items', 'pay_group_id', 'wage_item_id');
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'payroll_employee_pay_groups', 'pay_group_id', 'employee_id');
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
