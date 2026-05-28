<?php

namespace App\Models\Payroll;

use App\Traits\Auditable;
use App\Models\User;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BatchPayment extends BaseModel
{
    use HasFactory, Auditable;

    protected $table = 'payroll_batch_payments';

    protected $fillable = [
        'tenant_id',
        'pay_group_id',
        'month',
        'year',
        'batch_name',
        'status',
        'authorized_at',
        'authorized_by',
    ];

    protected function casts(): array
    {
        return [
            'authorized_at' => 'datetime',
        ];
    }

    public function payGroup()
    {
        return $this->belongsTo(PayGroup::class);
    }

    public function monthlyPayments()
    {
        return $this->hasMany(MonthlyPayment::class);
    }

    public function authorizer()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
