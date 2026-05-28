<?php

namespace App\Models\Payroll;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxBand extends Model
{
    use HasFactory, Auditable;

    protected $table = 'payroll_tax_bands';

    protected $fillable = [
        'tax_scheme_id',
        'lower_limit',
        'upper_limit',
        'rate_percentage',
        'flat_amount',
    ];

    protected function casts(): array
    {
        return [
            'lower_limit' => 'decimal:2',
            'upper_limit' => 'decimal:2',
            'rate_percentage' => 'decimal:2',
            'flat_amount' => 'decimal:2',
        ];
    }

    public function taxScheme()
    {
        return $this->belongsTo(TaxScheme::class);
    }
}
