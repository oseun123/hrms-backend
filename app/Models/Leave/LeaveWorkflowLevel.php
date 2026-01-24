<?php

namespace App\Models\Leave;

use App\Models\BaseModel;
use App\Models\Hris\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveWorkflowLevel extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'leave_workflow_id',
        'level',
        'approver_type',
        'approver_id',
    ];

    public function workflow()
    {
        return $this->belongsTo(LeaveWorkflow::class, 'leave_workflow_id');
    }

    public function specificApprover()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }
}
