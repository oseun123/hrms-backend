<?php

if (! function_exists('tenant_id')) {
    /**
     * Get the current tenant ID from the authenticated user's token or user record
     */
    function tenant_id(): ?int
    {
        if (! auth()->check()) {
            return null;
        }

        $token = auth()->user()->currentAccessToken();

        return $token ? $token->tenant_id : auth()->user()->tenant_id;
    }
}

if (! function_exists('tenant')) {
    /**
     * Get the current tenant model
     */
    function tenant(): ?\App\Models\Tenant
    {
        $tenantId = tenant_id();

        return $tenantId ? \App\Models\Tenant::find($tenantId) : null;
    }
}
if (! function_exists('parse_user_agent')) {
    /**
     * Parse User-Agent string to get descriptive browser and OS
     */
    function parse_user_agent(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown Device';
        }

        $os = 'Unknown OS';
        $browser = 'Unknown Browser';

        // OS Detection
        if (preg_match('/windows|win32/i', $userAgent)) $os = 'Windows';
        elseif (preg_match('/macintosh|mac os x/i', $userAgent)) $os = 'macOS';
        elseif (preg_match('/linux/i', $userAgent)) $os = 'Linux';
        elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) $os = 'iOS';
        elseif (preg_match('/android/i', $userAgent)) $os = 'Android';

        // Browser Detection
        if (preg_match('/msie|trident/i', $userAgent)) $browser = 'Internet Explorer';
        elseif (preg_match('/edge|edg/i', $userAgent)) $browser = 'Edge';
        elseif (preg_match('/firefox/i', $userAgent)) $browser = 'Firefox';
        elseif (preg_match('/chrome/i', $userAgent)) $browser = 'Chrome';
        elseif (preg_match('/safari/i', $userAgent)) $browser = 'Safari';
        elseif (preg_match('/opera|opr/i', $userAgent)) $browser = 'Opera';

        return "{$browser} on {$os}";
    }
}
