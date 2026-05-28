<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Traits\HasTenantBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantWelcomeNotification extends Notification
{
    use Queueable, HasTenantBranding;

    public $tenant;
    public $adminUser;
    public $password;

    /**
     * Create a new notification instance.
     */
    public function __construct(Tenant $tenant, $adminUser, $password = null)
    {
        $this->tenant = $tenant;
        $this->adminUser = $adminUser;
        $this->password = $password;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $branding = $this->getTenantBranding($this->tenant->id);
        $companyName = $branding['name'] ?? config('app.name');

        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $tenantSlug = $this->tenant->slug;

        // Format: http://tenant.localhost:3000
        $loginUrl = str_replace('://', "://{$tenantSlug}.", $frontendUrl);

        $mailMessage = (new MailMessage)
            ->subject('Welcome to ' . $companyName)
            ->greeting('Hello ' . $this->adminUser->name . '!')
            ->line('Your organization, ' . $this->tenant->name . ', has been successfully set up on our platform.')
            ->line('You can now log in to your dashboard using the credentials below:')
            ->line('**Login URL:** ' . $loginUrl)
            ->line('**Email:** ' . $this->adminUser->email);

        if ($this->password) {
            $mailMessage->line('**Password:** ' . $this->password);
        } else {
            $mailMessage->line('Please reset your password on the login page to gain access to your account.');
        }

        $mailMessage->action('Go to Dashboard', $loginUrl)
            ->line('We are excited to have you on board!')
            ->line('If you have any questions, feel free to reply to this email.');

        return $this->applyBranding($mailMessage, $this->tenant->id);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'admin_email' => $this->adminUser->email,
        ];
    }
}
