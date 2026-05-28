<?php

namespace App\Models\Requests;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class RequestApproval extends Model
{
    protected $fillable = [
        'request_submission_id',
        'approver_id',
        'level',
        'status',
        'comments',
        'notified_at',
        'actioned_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
        'actioned_at' => 'datetime',
    ];

    public function submission()
    {
        return $this->belongsTo(RequestSubmission::class, 'request_submission_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
