<?php

namespace App\Models\Payroll;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyPaymentItem extends Model
{
    use HasFactory, Auditable;

    protected $table = 'payroll_monthly_payment_items';

    protected $fillable = [
        'monthly_payment_id',
        'component_id',
        'amount',
        'is_one_time',
        'reason',
        'added_by',
        'added_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_one_time' => 'boolean',
        ];
    }

    public function monthlyPayment()
    {
        return $this->belongsTo(MonthlyPayment::class);
    }

    public function component()
    {
        return $this->belongsTo(SalaryComponent::class, 'component_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by');
    }
}
