<?php

namespace App\Models\Hris;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeCertification extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'certification_name',
        'issuing_organization',
        'issue_date',
        'expiry_date',
        'credential_id',
        'credential_url',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Employee who has this certification
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Check if certification is expired
     */
    public function getIsExpiredAttribute()
    {
        if (is_null($this->expiry_date)) {
            return false;
        }

        return $this->expiry_date->isPast();
    }

    /**
     * Scope for expired certifications
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }

    /**
     * Scope for valid certifications
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiry_date')
                ->orWhere('expiry_date', '>=', now());
        });
    }
}
