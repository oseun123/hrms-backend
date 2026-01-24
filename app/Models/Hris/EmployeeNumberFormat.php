<?php

namespace App\Models\Hris;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeNumberFormat extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'format_name',
        'prefix',
        'include_year',
        'year_format',
        'include_month',
        'month_format',
        'separator',
        'sequence_length',
        'current_sequence',
        'reset_sequence',
        'sample_format',
        'is_active',
        'is_default',
        'created_by',
    ];

    protected $casts = [
        'include_year' => 'boolean',
        'include_month' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sequence_length' => 'integer',
        'current_sequence' => 'integer',
    ];

    /**
     * Get the creator of this format
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the sequences for this format
     */
    public function sequences()
    {
        return $this->hasMany(EmployeeNumberSequence::class, 'format_id');
    }

    /**
     * Scope to get active formats
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get default format for a tenant
     */
    public function scopeDefault($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId)
            ->where('is_default', true)
            ->where('is_active', true);
    }

    /**
     * Generate a preview of what the next employee number would look like
     */
    public function generatePreview($currentSequence = null): string
    {
        $parts = [];

        // Add prefix
        if ($this->prefix) {
            $parts[] = $this->prefix;
        }

        // Add year
        if ($this->include_year) {
            $year = now()->format($this->year_format === 'YYYY' ? 'Y' : 'y');
            $parts[] = $year;
        }

        // Add month
        if ($this->include_month) {
            $month = now()->format($this->month_format === 'MM' ? 'm' : 'n');
            $parts[] = $month;
        }

        // Add sequence
        $sequence = $currentSequence ?? ($this->current_sequence + 1);
        $parts[] = str_pad($sequence, $this->sequence_length, '0', STR_PAD_LEFT);

        return implode($this->separator, $parts);
    }
}
