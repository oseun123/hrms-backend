<?php

namespace App\Models\Hris;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'is_required',
        'requires_expiry',
        'allowed_extensions',
        'max_file_size',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'requires_expiry' => 'boolean',
        'is_active' => 'boolean',
        'max_file_size' => 'integer',
    ];

    /**
     * Documents of this type
     */
    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    /**
     * Scope for active document types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for required documents
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}
