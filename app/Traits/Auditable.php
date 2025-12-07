<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot the auditable trait
     */
    protected static function bootAuditable()
    {
        static::created(function ($model) {
            $model->auditEvent('created');
        });

        static::updated(function ($model) {
            $model->auditEvent('updated');
        });

        static::deleted(function ($model) {
            $model->auditEvent('deleted');
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->auditEvent('restored');
            });
        }
    }

    /**
     * Create an audit log entry
     */
    protected function auditEvent(string $event)
    {
        $oldValues = null;
        $newValues = null;

        if ($event === 'updated') {
            $oldValues = $this->getOriginal();
            $newValues = $this->getAttributes();

            // Remove unchanged values
            $changes = [];
            foreach ($newValues as $key => $value) {
                if (isset($oldValues[$key]) && $oldValues[$key] != $value) {
                    $changes[$key] = [
                        'old' => $oldValues[$key],
                        'new' => $value,
                    ];
                }
            }

            if (empty($changes)) {
                return; // No actual changes
            }

            $oldValues = array_column($changes, 'old', null);
            $newValues = array_column($changes, 'new', null);
        } elseif ($event === 'created') {
            $newValues = $this->getAttributes();
        } elseif ($event === 'deleted') {
            $oldValues = $this->getAttributes();
        } elseif ($event === 'restored') {
            $newValues = $this->getAttributes();
        }

        // Get tenant_id from model or default to 1
        $tenantId = $this->tenant_id ?? 1;

        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => Auth::id(),
            'auditable_type' => get_class($this),
            'auditable_id' => $this->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get all audit logs for this model
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
