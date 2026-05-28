<?php

namespace App\Notifications;

use App\Models\Requests\RequestSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestPendingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public RequestSubmission $submission) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $url = $frontendUrl . '/requests/approvals';
        $templateName = $this->submission->template->name;
        $requesterName = $this->submission->employee->full_name;

        return (new MailMessage)
            ->subject("Action Required: New {$templateName} Request Pending Approval")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new {$templateName} request has been submitted and is awaiting your approval.")
            ->line("**Reference Number:** " . $this->submission->reference_number)
            ->line("**Requester:** " . $requesterName)
            ->line("**Date Submitted:** " . $this->submission->submitted_at->format('M d, Y H:i'))
            ->action('Review Request', $url)
            ->line('Thank you for using FlowDeskHr!');
    }

    public function toArray($notifiable): array
    {
        return [
            'request_submission_id' => $this->submission->id,
            'reference_number' => $this->submission->reference_number,
            'template_name' => $this->submission->template->name,
            'requester_name' => $this->submission->employee->full_name,
            'type' => 'pending_approval',
        ];
    }
}
