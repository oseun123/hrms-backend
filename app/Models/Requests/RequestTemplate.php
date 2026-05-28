<?php

namespace App\Models\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tenant;
use App\Models\User;

class RequestTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'category',
        'template_key',
        'icon',
        'fields',
        'is_active',
        'request_workflow_id',
        'created_by',
    ];

    protected $casts = [
        'fields' => 'json',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function workflow()
    {
        return $this->belongsTo(RequestWorkflow::class, 'request_workflow_id');
    }

    public function submissions()
    {
        return $this->hasMany(RequestSubmission::class, 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
