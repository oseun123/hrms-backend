<?php

namespace App\Models\Preference;

use App\Models\Tenant;
use App\Models\User;
use App\Traits\Auditable;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Preference extends BaseModel
{
    use HasFactory, SoftDeletes, Auditable;

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::saved(function ($preference) {
            // When organization legal_name is updated, sync it to tenant.name
            if ($preference->category === 'organization' && $preference->key === 'legal_name' && !empty($preference->value)) {
                $tenant = Tenant::find($preference->tenant_id);
                if ($tenant && $tenant->name !== $preference->value) {
                    $tenant->update(['name' => $preference->value]);
                }
            }
        });
    }

    protected $fillable = [
        'tenant_id',
        'user_id',
        'category',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    /**
     * Accessor for the value attribute.
     * Automatically resolves URLs for logo_url and other file paths.
     */
    public function getValueAttribute($value)
    {
        // Decode the JSON value manually as we're overriding the cast behavior for the getter
        $decoded = json_decode($value, true);

        if ($this->key === 'logo_url' && is_string($decoded) && !empty($decoded)) {
            // If it's already a full URL or base64, return as is
            if (str_starts_with($decoded, 'http') || str_starts_with($decoded, 'data:')) {
                return $decoded;
            }

            // Resolve the URL using FileUploadService
            try {
                return app(\App\Services\FileUploadService::class)->getUrl($decoded);
            } catch (\Exception $e) {
                return $decoded;
            }
        }

        return $decoded;
    }

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId)->whereNull('user_id');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get a preference value by category and key
     * 
     * @param string $category
     * @param string $key
     * @param int|null $tenantId
     * @param int|null $userId
     * @param mixed $default
     * @return mixed
     */
    public static function getValue($category, $key, $tenantId = null, $userId = null, $default = null)
    {
        $query = static::where('category', $category)->where('key', $key);

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($tenantId) {
            $query->where('tenant_id', $tenantId)->whereNull('user_id');
        }

        $preference = $query->first();

        return $preference ? $preference->value : $default;
    }
}
