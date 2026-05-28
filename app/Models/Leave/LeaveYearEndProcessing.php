<?php

namespace App\Models\Leave;

use App\Models\BaseModel;
use App\Models\User;
use App\Models\Tenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveYearEndProcessing extends BaseModel
{
    use HasFactory, Auditable;

    protected $table = 'leave_year_end_processing';

    protected $fillable = [
        'tenant_id',
        'from_year',
        'to_year',
        'processed_at',
        'processed_by',
        'employees_processed',
        'summary',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'summary' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
