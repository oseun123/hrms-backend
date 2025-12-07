<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'document_type_id',
        'document_name',
        'file_path',
        'file_url',
        'file_size',
        'mime_type',
        'storage_driver',
        'cloudinary_public_id',
        'file_metadata',
        'issue_date',
        'expiry_date',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'file_size' => 'integer',
        'file_metadata' => 'array',
    ];

    /**
     * Employee who owns this document
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Document type
     */
    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * User who uploaded this document
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope for expired documents
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }

    /**
     * Scope for expiring soon (within 30 days)
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }
}
