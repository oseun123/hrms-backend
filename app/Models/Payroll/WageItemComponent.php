<?php

namespace App\Models\Payroll;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WageItemComponent extends Model
{
    use HasFactory, Auditable;

    protected $table = 'payroll_wage_item_components';

    protected $fillable = [
        'wage_item_id',
        'component_id',
        'amount_value',
        'calculation_type',
        'percent_value',
        'frequency',
        'payment_month',
    ];

    protected function casts(): array
    {
        return [
            'amount_value' => 'decimal:2',
        ];
    }

    public function wageItem()
    {
        return $this->belongsTo(WageItem::class, 'wage_item_id');
    }

    public function component()
    {
        return $this->belongsTo(SalaryComponent::class, 'component_id');
    }
}
