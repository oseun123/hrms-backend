<?php

namespace App\Models\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tenant;
use App\Models\User;

class RequestWorkflow extends Model
{
    use SoftDeletes;

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
        return $this->hasMany(RequestWorkflowLevel::class)->orderBy('level');
    }

    public function templates()
    {
        return $this->hasMany(RequestTemplate::class);
    }
}
