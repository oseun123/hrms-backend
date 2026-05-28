<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoRequest extends Model
{
    protected $fillable = [
        'name',
        'email',
        'company',
        'phone',
        'company_size',
        'message',
        'status',
    ];
}
