<?php

namespace App\Models\Payroll;

use App\Traits\Auditable;
use App\Models\User;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxScheme extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'payroll_tax_schemes';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'employee_pension_percentage',
        'employer_pension_percentage',
        'apply_cra',
        'apply_rent_relief',
        'rent_relief_max_amount',
        'rent_relief_percentage',
        'is_active',
        'is_system_defined',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'employee_pension_percentage' => 'decimal:2',
            'employer_pension_percentage' => 'decimal:2',
            'apply_cra' => 'boolean',
            'apply_rent_relief' => 'boolean',
            'rent_relief_max_amount' => 'decimal:2',
            'rent_relief_percentage' => 'decimal:2',
            'is_active' => 'boolean',
            'is_system_defined' => 'boolean',
        ];
    }

    public function bands()
    {
        return $this->hasMany(TaxBand::class);
    }

    public function payGroups()
    {
        return $this->hasMany(PayGroup::class);
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
