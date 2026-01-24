<?php

namespace App\Notifications;

use App\Traits\HasTenantBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification
{
    use Queueable, HasTenantBranding;

    public $token;

    public $email;

    public $tenant;

    /**
     * Create a new notification instance.
     */
    public function __construct($token, $email, $tenant = null)
    {
        $this->token = $token;
        $this->email = $email;
        $this->tenant = $tenant;
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
        $tenantId = $this->tenant ? $this->tenant->id : ($notifiable->tenant_id ?? null);
        $branding = $this->getTenantBranding($tenantId);
        $companyName = $branding['name'] ?? config('app.name');

        // Build tenant-aware reset URL
        $frontendUrl = config('app.frontend_url');
        $tenantSlug = $this->tenant ? $this->tenant->slug : 'default';

        // Format: http://tenant.localhost:3000/reset-password/TOKEN?email=EMAIL
        $resetUrl = str_replace('://', "://{$tenantSlug}.", $frontendUrl) .
            '/reset-password/' . $this->token .
            '?email=' . urlencode($this->email);

        $mailMessage = (new MailMessage)
            ->subject($companyName . ' - Password Reset Request')
            ->greeting("Hello {$notifiable->name}!")
            ->line('You requested a password reset for your account.')
            ->line('Click the button below to reset your password:')
            ->action('Reset Password', $resetUrl)
            ->line('This link will expire in 1 hour.')
            ->line('If you did not request this reset, please ignore this email.');

        return $this->applyBranding($mailMessage, $tenantId);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'token' => $this->token,
            'email' => $this->email,
        ];
    }
}
