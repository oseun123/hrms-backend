<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuperAdminActivityLog extends Model
{
    protected $table = 'super_admin_activity_logs';

    protected $fillable = [
        'super_admin_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function superAdmin()
    {
        return $this->belongsTo(SuperAdmin::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }
}
