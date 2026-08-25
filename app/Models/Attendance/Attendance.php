<?php

namespace App\Models\Attendance;

use App\Models\User;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'date',
        'check_in',
        'check_out',
        'spent_time',
        'overtime',
        'status',
        'is_absent',
        'is_leave',
        'is_late',
        'shift_type',
        'ip_address',
        'latitude',
        'longitude',
        'photo_proof_path'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
