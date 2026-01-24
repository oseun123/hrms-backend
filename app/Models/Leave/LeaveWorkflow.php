<?php

namespace App\Models\Leave;

use App\Models\BaseModel;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveWorkflow extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'is_active',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function levels()
    {
        return $this->hasMany(LeaveWorkflowLevel::class)->orderBy('level');
    }

    public function policies()
    {
        return $this->hasMany(LeavePolicy::class);
    }
}
