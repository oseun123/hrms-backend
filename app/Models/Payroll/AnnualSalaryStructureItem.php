<?php

namespace App\Models\Payroll;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualSalaryStructureItem extends Model
{
    use HasFactory, Auditable;

    protected $table = 'payroll_annual_salary_structure_items';

    protected $fillable = [
        'annual_salary_structure_id',
        'component_id',
        'annual_amount',
        'frequency',
        'payment_month',
    ];

    protected function casts(): array
    {
        return [
            'annual_amount' => 'decimal:2',
        ];
    }

    public function annualStructure()
    {
        return $this->belongsTo(AnnualSalaryStructure::class, 'annual_salary_structure_id');
    }

    public function component()
    {
        return $this->belongsTo(SalaryComponent::class, 'component_id');
    }
}
