<?php

namespace App\Models\Performance;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class AppraisalAttachment extends BaseModel
{
    use HasFactory, Auditable;

    protected $fillable = [
        'tenant_id',
        'submission_id',
        'reviewer_level',
        'file_name',
        'file_path',
        'storage_driver',
        'file_url',
        'file_type',
        'file_size',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'reviewer_level' => 'integer',
        'file_size' => 'integer',
        'uploaded_at' => 'datetime',
    ];

    protected $appends = ['file_url_generated'];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function submission()
    {
        return $this->belongsTo(AppraisalSubmission::class, 'submission_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Accessors
    public function getFileUrlGeneratedAttribute()
    {
        if ($this->file_url) {
            return $this->file_url;
        }

        if ($this->file_path) {
            return Storage::disk('public')->url($this->file_path);
        }

        return null;
    }

    // Scopes
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForSubmission($query, $submissionId)
    {
        return $query->where('submission_id', $submissionId);
    }

    public function scopeForLevel($query, $level)
    {
        return $query->where('reviewer_level', $level);
    }
}
