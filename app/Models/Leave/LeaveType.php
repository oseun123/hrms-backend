<?php

namespace App\Models\Leave;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'is_paid',
        'is_active',
        'requires_attachment',
        'is_seeded',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
        'requires_attachment' => 'boolean',
        'is_seeded' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function policies()
    {
        return $this->hasMany(LeavePolicy::class);
    }

    public function requests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
