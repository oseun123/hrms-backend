<?php

namespace App\Notifications;

use App\Traits\HasTenantBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification implements ShouldQueue
{
    use Queueable, HasTenantBranding;

    protected $code;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $code)
    {
        $this->code = $code;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $tenantId = $notifiable->tenant_id;
        $branding = $this->getTenantBranding($tenantId);
        $companyName = $branding['name'] ?? config('app.name');

        $mailMessage = (new MailMessage)
            ->subject($companyName . ' - Verification Code')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your security verification code is:')
            ->line('**' . $this->code . '**')
            ->line('This code will expire in 10 minutes.')
            ->line('If you did not request this code, please secure your account.');

        return $this->applyBranding($mailMessage, $tenantId);
    }
}
