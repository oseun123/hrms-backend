<?php

namespace App\Models\Requests;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class RequestWorkflowLevel extends Model
{
    protected $fillable = [
        'request_workflow_id',
        'level',
        'approver_type',
        'approver_id',
    ];

    public function workflow()
    {
        return $this->belongsTo(RequestWorkflow::class, 'request_workflow_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
