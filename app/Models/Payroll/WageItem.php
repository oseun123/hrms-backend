<?php

namespace App\Models\Payroll;

use App\Traits\Auditable;
use App\Models\User;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class WageItem extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'payroll_wage_items';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'is_active',
        'has_leave_allowance',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'has_leave_allowance' => 'boolean',
        ];
    }


    public function components()
    {
        return $this->hasMany(WageItemComponent::class, 'wage_item_id');
    }

    public function payGroups()
    {
        return $this->belongsToMany(PayGroup::class, 'payroll_pay_group_wage_items', 'wage_item_id', 'pay_group_id');
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
