<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeEmployee extends Notification implements ShouldQueue
{
    use Queueable;

    protected $temporaryPassword;
    protected $employeeName;
    protected $employeeNumber;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $employeeName, string $employeeNumber, string $temporaryPassword)
    {
        $this->employeeName = $employeeName;
        $this->employeeNumber = $employeeNumber;
        $this->temporaryPassword = $temporaryPassword;
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
        // Get tenant slug for subdomain URL
        $tenantSlug = $notifiable->tenant->slug ?? 'app';
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        // Build tenant-aware login URL: http://{tenant-slug}.localhost:3000/login
        $loginUrl = str_replace('://', "://{$tenantSlug}.", $frontendUrl) . '/login';
        $forgotPasswordUrl = str_replace('://', "://{$tenantSlug}.", $frontendUrl) . '/forgot-password';

        return (new MailMessage)
            ->subject('Welcome to ' . config('app.name'))
            ->greeting('Hello ' . $this->employeeName . '!')
            ->line('Welcome to ' . config('app.name') . '! Your employee account has been created.')
            ->line('**Employee Number:** ' . $this->employeeNumber)
            ->line('**Email:** ' . $notifiable->email)
            ->line('To access your account, please set your password using the "Forgot Password" feature on the login page.')
            ->line('Simply click the link below, then use the "Forgot Password" link to receive a password reset email.')
            ->action('Go to Login Page', $loginUrl)
            ->line('Or visit: ' . $forgotPasswordUrl)
            ->line('If you have any questions, please contact your HR department.')
            ->line('Thank you for joining our team!');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray(object $notifiable): array
    {
        // Get tenant slug for subdomain URL
        $tenantSlug = $notifiable->tenant->slug ?? 'app';
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $loginUrl = str_replace('://', "://{$tenantSlug}.", $frontendUrl) . '/login';

        return [
            'title' => 'Welcome to ' . config('app.name'),
            'message' => 'Your employee account has been created. Employee Number: ' . $this->employeeNumber . '. Please use the Forgot Password feature to set your password.',
            'type' => 'welcome',
            'action_url' => $loginUrl,
            'action_text' => 'Go to Login Page',
            'employee_number' => $this->employeeNumber,
        ];
    }
}
