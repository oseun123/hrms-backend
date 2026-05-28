<?php

namespace App\Notifications;

use App\Models\DemoRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DemoRequestNotification extends Notification
{
    use Queueable;

    public function __construct(public DemoRequest $demo) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $adminUrl = config('app.frontend_url', 'http://localhost:3000') . '/super-admin';

        return (new MailMessage)
            ->subject('New Demo Request — ' . $this->demo->company)
            ->greeting('Hello,')
            ->line('A new demo request has been submitted via the FlowDeskHr website.')
            ->line('**Name:** ' . $this->demo->name)
            ->line('**Email:** ' . $this->demo->email)
            ->line('**Company:** ' . $this->demo->company)
            ->line('**Phone:** ' . ($this->demo->phone ?: 'Not provided'))
            ->line('**Company Size:** ' . ($this->demo->company_size ?: 'Not provided'))
            ->line('**Message:**')
            ->line($this->demo->message ?: '(No message provided)')
            ->action('View in Admin Portal', $adminUrl)
            ->line('Please follow up with this prospect at your earliest convenience.');
    }

    public function toArray($notifiable): array
    {
        return [
            'demo_request_id' => $this->demo->id,
            'company' => $this->demo->company,
            'email' => $this->demo->email,
        ];
    }
}
