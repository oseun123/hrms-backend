<?php

namespace App\Models\Preference;

use App\Models\Tenant;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileApprovalSetting extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'section',
        'requires_approval',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
    ];

    /**
     * Get the tenant that owns the approval setting
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if a specific section requires approval for a tenant
     */
    public static function requiresApproval(int $tenantId, string $section): bool
    {
        return static::where('tenant_id', $tenantId)
            ->where('section', $section)
            ->value('requires_approval') ?? false;
    }

    /**
     * Get all sections that require approval for a tenant
     */
    public static function getRequiredSections(int $tenantId): array
    {
        return static::where('tenant_id', $tenantId)
            ->where('requires_approval', true)
            ->pluck('section')
            ->toArray();
    }
}
