<?php

namespace App\Notifications;

use App\Models\Leave\LeaveRequest;
use App\Traits\HasTenantBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmitted extends Notification
{
    use Queueable, HasTenantBranding;

    public LeaveRequest $leaveRequest;
    public string $approvalQueueUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(LeaveRequest $leaveRequest)
    {
        $this->leaveRequest = $leaveRequest;

        // Build approval queue URL
        $frontendUrl = config('app.frontend_url');
        $tenant = $leaveRequest->tenant;
        $tenantSlug = $tenant ? $tenant->slug : 'default';
        $this->approvalQueueUrl = str_replace('://', "://{$tenantSlug}.", $frontendUrl) . '/leave/approvals';
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $tenantId = $this->leaveRequest->tenant_id;
        $branding = $this->getTenantBranding($tenantId);
        $companyName = $branding['name'] ?? config('app.name');

        $mailMessage = (new MailMessage)
            ->subject($companyName . ' - New Leave Request Submitted')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->leaveRequest->employee->full_name . ' has submitted a new leave request.')
            ->line('**Leave Type:** ' . $this->leaveRequest->leaveType->name)
            ->line('**Period:** ' . $this->leaveRequest->start_date->format('M d, Y') . ' to ' . $this->leaveRequest->end_date->format('M d, Y'))
            ->line('**Duration:** ' . $this->leaveRequest->duration_days . ' days')
            ->action('Review Request', $this->approvalQueueUrl)
            ->line('Please review and action this request as soon as possible.');

        return $this->applyBranding($mailMessage, $tenantId);
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'leave_request_submitted',
            'leave_request_id' => $this->leaveRequest->id,
            'employee_name' => $this->leaveRequest->employee->full_name,
            'leave_type' => $this->leaveRequest->leaveType->name,
            'duration' => $this->leaveRequest->duration_days,
            'url' => $this->approvalQueueUrl,
        ];
    }
}
