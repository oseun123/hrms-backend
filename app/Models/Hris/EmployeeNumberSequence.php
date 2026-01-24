<?php

namespace App\Models\Hris;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeNumberSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'format_id',
        'year',
        'month',
        'last_sequence',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'last_sequence' => 'integer',
    ];

    /**
     * Get the format this sequence belongs to
     */
    public function format()
    {
        return $this->belongsTo(EmployeeNumberFormat::class, 'format_id');
    }

    /**
     * Get or create a sequence for the given format and period
     */
    public static function getOrCreateSequence($formatId, $tenantId, $resetSequence)
    {
        $year = null;
        $month = null;

        if ($resetSequence === 'yearly') {
            $year = now()->year;
        } elseif ($resetSequence === 'monthly') {
            $year = now()->year;
            $month = now()->month;
        }

        return static::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'format_id' => $formatId,
                'year' => $year,
                'month' => $month,
            ],
            [
                'last_sequence' => 0,
            ]
        );
    }
}
