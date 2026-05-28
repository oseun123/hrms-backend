<?php

namespace App\Notifications;

use App\Models\Requests\RequestSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RequestSubmission $submission,
        public string $status,
        public ?string $comments = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $url = $frontendUrl . '/requests/submissions/' . $this->submission->id;
        $templateName = $this->submission->template->name;
        $statusLabel = ucfirst($this->status);

        $mail = (new MailMessage)
            ->subject("Update on your {$templateName} Request ({$statusLabel})")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your request for {$templateName} has been {$this->status}.")
            ->line("**Reference Number:** " . $this->submission->reference_number);

        if ($this->comments) {
            $mail->line("**Approver Comments:** \"{$this->comments}\"");
        }

        return $mail
            ->action('View Request Details', $url)
            ->line('Thank you for using FlowDeskHr!');
    }

    public function toArray($notifiable): array
    {
        return [
            'request_submission_id' => $this->submission->id,
            'reference_number' => $this->submission->reference_number,
            'status' => $this->status,
            'comments' => $this->comments,
            'type' => 'request_status_updated',
        ];
    }
}
