<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification
{
    use Queueable;

    public $token;
    public $email;

    /**
     * Create a new notification instance.
     */
    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
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
        $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $this->token . '&email=' . urlencode($this->email);

        return (new MailMessage)
            ->subject('Password Reset Request - HRMS')
            ->greeting("Hello {$notifiable->name}!")
            ->line('You requested a password reset for your account.')
            ->line('Click the button below to reset your password:')
            ->action('Reset Password', $resetUrl)
            ->line('Or use this reset token in the API:')
            ->line("**Token:** `{$this->token}`")
            ->line('This token will expire in 1 hour.')
            ->line('**API Endpoint:** `POST /api/auth/password/reset`')
            ->line('If you did not request this reset, please ignore this email.')
            ->salutation('Regards, HRMS Team');
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
