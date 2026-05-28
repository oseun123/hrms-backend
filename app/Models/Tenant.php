<?php

namespace App\Models;

use App\Models\Hris\Department;
use App\Models\Hris\Employee;
use App\Models\Hris\Grade;
use App\Models\Hris\Level;
use App\Models\Hris\Position;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Tenant extends Model
{
    use HasFactory, SoftDeletes, Notifiable;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'contact_email',
        'plan',
        'max_users',
        'trial_ends_at',
        'notes',
        'is_active',
        'settings',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['logo_url', 'theme_color'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * Accessor for logo_url
     */
    public function getLogoUrlAttribute()
    {
        $value = \App\Models\Preference\Preference::where('tenant_id', $this->id)
            ->where('category', 'organization')
            ->where('key', 'logo_url')
            ->whereNull('user_id')
            ->value('value');

        if (!$value) {
            return null;
        }

        // If it's a base64 string, return it as is
        if (str_starts_with($value, 'data:image')) {
            return $value;
        }

        // Otherwise, resolve the URL using FileUploadService
        return app(\App\Services\FileUploadService::class)->getUrl($value);
    }

    /**
     * Accessor for theme_color
     */
    public function getThemeColorAttribute()
    {
        $value = \App\Models\Preference\Preference::where('tenant_id', $this->id)
            ->where('category', 'display')
            ->where('key', 'theme_color')
            ->whereNull('user_id')
            ->value('value');

        return $value ?? 'geekblue';
    }

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    public function levels()
    {
        return $this->hasMany(Level::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Route notifications for the mail channel.
     *
     * @return string
     */
    public function routeNotificationForMail()
    {
        return $this->contact_email;
    }
}
