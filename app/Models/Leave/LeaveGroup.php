<?php

namespace App\Models\Leave;

use App\Models\BaseModel;
use App\Models\Hris\EmployeeEmploymentDetail;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveGroup extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function policies()
    {
        return $this->hasMany(LeavePolicy::class);
    }

    public function employmentDetails()
    {
        return $this->hasMany(EmployeeEmploymentDetail::class, 'leave_group_id');
    }
}
