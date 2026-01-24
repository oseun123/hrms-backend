<?php

namespace App\Traits;

use App\Models\Preference\Preference;
use Illuminate\Notifications\Messages\MailMessage;

trait HasTenantBranding
{
    /**
     * Get branding information for a tenant
     *
     * @param int $tenantId
     * @return array
     */
    protected function getTenantBranding(int $tenantId): array
    {
        return [
            'name' => Preference::getValue('organization', 'legal_name', $tenantId),
            'hr_email' => Preference::getValue('organization', 'hr_email', $tenantId),
            'support_email' => Preference::getValue('organization', 'support_email', $tenantId),
            'logo_url' => \App\Models\Tenant::find($tenantId)->logo_url,
        ];
    }

    /**
     * Apply branding to a MailMessage
     *
     * @param MailMessage $mailMessage
     * @param int $tenantId
     * @return MailMessage
     */
    protected function applyBranding(MailMessage $mailMessage, int $tenantId): MailMessage
    {
        $branding = $this->getTenantBranding($tenantId);
        $appName = config('app.name');
        $companyName = $branding['name'] ?? $appName;

        // Set From address if HR email is available
        if ($branding['hr_email']) {
            $mailMessage->from($branding['hr_email'], $companyName);
        } elseif ($branding['name']) {
            $mailMessage->from(config('mail.from.address'), $companyName);
        }

        // Set Salutation/Regards
        $mailMessage->salutation('Regards, ' . ($branding['name'] ? $branding['name'] : $appName . ' Team'));

        // Build header URL (subdomain aware)
        $frontendUrl = config('app.frontend_url');
        $tenant = \App\Models\Tenant::find($tenantId);
        $tenantSlug = $tenant ? $tenant->slug : 'default';
        $headerUrl = str_replace('://', "://{$tenantSlug}.", $frontendUrl);

        // Share variables globally for this request so they're available in all mail views
        \Illuminate\Support\Facades\View::share([
            'companyName' => $companyName,
            'companyLogo' => $branding['logo_url'],
            'headerUrl' => $headerUrl,
        ]);

        return $mailMessage;
    }
}
