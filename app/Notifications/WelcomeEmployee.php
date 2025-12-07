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
        $loginUrl = config('app.url') . '/login';

        return (new MailMessage)
            ->subject('Welcome to ' . config('app.name'))
            ->greeting('Hello ' . $this->employeeName . '!')
            ->line('Welcome to ' . config('app.name') . '! Your employee account has been created.')
            ->line('**Employee Number:** ' . $this->employeeNumber)
            ->line('**Email:** ' . $notifiable->email)
            ->line('**Temporary Password:** ' . $this->temporaryPassword)
            ->line('Please use these credentials to log in to the system. You will be required to change your password on first login.')
            ->action('Login to Portal', $loginUrl)
            ->line('If you have any questions, please contact your HR department.')
            ->line('Thank you for joining our team!');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Welcome to ' . config('app.name'),
            'message' => 'Your employee account has been created. Employee Number: ' . $this->employeeNumber,
            'type' => 'welcome',
            'action_url' => config('app.url') . '/login',
            'action_text' => 'Login to Portal',
            'employee_number' => $this->employeeNumber,
            'has_temporary_password' => true,
        ];
    }
}
