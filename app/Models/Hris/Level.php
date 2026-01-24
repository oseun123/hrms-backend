<?php

namespace App\Models\Hris;

use App\Models\User;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Level extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'rank',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rank' => 'integer',
    ];

    // Relationships
    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    public function employees()
    {
        return $this->hasManyThrough(
            EmployeeEmploymentDetail::class,
            Position::class,
            'level_id', // Foreign key on positions table
            'position_id', // Foreign key on employee_employment_details table
            'id', // Local key on levels table
            'id' // Local key on positions table
        );
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('rank');
    }
}
