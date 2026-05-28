<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use App\Models\Hris\Employee;
use App\Models\Preference\Preference;
use App\Traits\TenantScoped;

class User extends BaseUser
{
    use HasApiTokens, HasFactory, Notifiable, TenantScoped;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['is_hr', 'all_permissions'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'password_changed_at',
        'tenant_id',
        'two_factor_code',
        'two_factor_expires_at',
        'two_factor_enabled',
        'last_login',
        'last_login_ip',
        'previous_login',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'previous_login' => 'datetime',
        ];
    }

    /**
     * Relationships
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function knownDevices()
    {
        return $this->hasMany(KnownDevice::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Check if the user has a specific permission
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // Admin role always has all permissions (mirrors frontend logic)
        if ($this->roles()->where('slug', 'admin')->exists()) {
            return true;
        }

        return $this->roles()->whereHas('permissions', function ($query) use ($permissionSlug) {
            $query->where('slug', $permissionSlug);
        })->exists();
    }

    /**
     * Get all permissions for the user
     */
    public function getAllPermissionsAttribute(): array
    {
        return Permission::whereHas('roles', function ($query) {
            $query->whereIn('roles.id', $this->roles->pluck('id'));
        })->pluck('slug')->toArray();
    }

    /**
     * Determine if the user is an HR administrator
     */
    public function getIsHrAttribute(): bool
    {
        // is_hr is now purely informational (e.g., for notification routing)
        // It does NOT grant any permissions.
        if (!$this->tenant_id) {
            return false;
        }

        $employee = $this->employee;
        if (!$employee) {
            return false;
        }

        return Preference::where('tenant_id', $this->tenant_id)
            ->where('category', 'hr_admins')
            ->where('key', (string) $employee->id)
            ->exists();
    }

    /**
     * Get all HR users for a tenant, optionally filtered by role
     */
    public static function hrUsers(int $tenantId, ?string $role = null)
    {
        $query = Preference::where('tenant_id', $tenantId)
            ->where('category', 'hr_admins');

        if ($role) {
            $query->where('value', 'LIKE', '%"role":"' . $role . '"%');
        }

        $hrEmployeeIds = $query->pluck('key')->toArray();

        if (empty($hrEmployeeIds)) {
            return collect();
        }

        return static::where('tenant_id', $tenantId)
            ->whereIn('id', function ($query) use ($hrEmployeeIds) {
                $query->select('user_id')
                    ->from('employees')
                    ->whereIn('id', $hrEmployeeIds);
            })->get();
    }

    /**
     * Get all Finance users for a tenant
     */
    public static function financeUsers(int $tenantId)
    {
        return static::hrUsers($tenantId, 'Finance Admin');
    }

    /**
     * Override createToken to include tenant_id
     */
    public function createToken(string $name, array $abilities = ['*'], ?int $tenantId = null)
    {
        $deviceName = ($name === 'auth-token')
            ? parse_user_agent(request()->userAgent())
            : $name;

        $token = $this->tokens()->create([
            'name' => $deviceName,
            'token' => hash('sha256', $plainTextToken = \Illuminate\Support\Str::random(40)),
            'abilities' => $abilities,
            'tenant_id' => $tenantId ?? $this->tenant_id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        /** @var \Laravel\Sanctum\PersonalAccessToken $token */
        return new \Laravel\Sanctum\NewAccessToken($token, $token->getKey() . '|' . $plainTextToken);
    }
}
