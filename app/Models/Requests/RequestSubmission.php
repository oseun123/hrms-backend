<?php

namespace App\Models\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tenant;
use App\Models\Hris\Employee;

class RequestSubmission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'template_id',
        'employee_id',
        'reference_number',
        'form_data',
        'status',
        'current_level',
        'submitted_at',
        'completed_at',
    ];

    protected $casts = [
        'form_data' => 'json',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function template()
    {
        return $this->belongsTo(RequestTemplate::class, 'template_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvals()
    {
        return $this->hasMany(RequestApproval::class, 'request_submission_id');
    }

    public function currentLevelApproval()
    {
        return $this->hasOne(RequestApproval::class, 'request_submission_id')->where('level', $this->current_level);
    }
}
