<?php

namespace App\Notifications;

use App\Traits\HasTenantBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDeviceLoginNotification extends Notification implements ShouldQueue
{
    use Queueable, HasTenantBranding;

    protected $device;
    protected $ipAddress;
    protected $loginTime;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $device, string $ipAddress, $loginTime = null)
    {
        $this->device = $device;
        $this->ipAddress = $ipAddress;
        $this->loginTime = $loginTime ?? now();
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $tenantId = $notifiable->tenant_id;
        $branding = $this->getTenantBranding($tenantId);
        $companyName = $branding['name'] ?? config('app.name');

        // URL to security settings / sessions page
        // Standard session/security page: /profile?tab=security (assuming based on standard app layouts)
        $tenantSlug = $notifiable->tenant->slug ?? 'app';
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $securityUrl = str_replace('://', "://{$tenantSlug}.", $frontendUrl) . '/preferences';
        $changePasswordUrl = str_replace('://', "://{$tenantSlug}.", $frontendUrl) . '/preferences?tab=password';

        $mailMessage = (new MailMessage)
            ->subject('New Device Login Alert - ' . $companyName)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your account was just logged into from a new device.')
            ->line('**Device:** ' . $this->device)
            ->line('**IP Address:** ' . $this->ipAddress)
            ->line('**Date & Time:** ' . $this->loginTime->format('F j, Y, g:i a'))
            ->line('If this was you, you can safely ignore this email.')
            ->line('**If this was NOT you**, please take the following actions immediately:')
            ->line('1. Change your password to secure your account.')
            ->line('2. Review your active sessions and revoke any unrecognized devices.')
            ->action('View Active Sessions', $securityUrl)
            ->line('For added security, we recommend enabling Two-Factor Authentication (2FA) if you haven\'t already.');

        return $this->applyBranding($mailMessage, $tenantId);
    }

    /**
     * Get the array representation of the notification (database).
     */
    public function toArray(object $notifiable): array
    {
        $tenantSlug = $notifiable->tenant->slug ?? 'app';
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $securityUrl = str_replace('://', "://{$tenantSlug}.", $frontendUrl) . '/preferences';

        return [
            'title' => 'New Device Login Detected',
            'message' => "A new login was detected on your account from {$this->device} (IP: {$this->ipAddress}). If this wasn't you, please change your password immediately.",
            'type' => 'security_alert',
            'device' => $this->device,
            'ip_address' => $this->ipAddress,
            'action_url' => $securityUrl,
            'action_text' => 'Review Sessions',
        ];
    }
}
