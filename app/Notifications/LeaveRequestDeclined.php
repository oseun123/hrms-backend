<?php

namespace App\Notifications;

use App\Models\Leave\LeaveRequest;
use App\Traits\HasTenantBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestDeclined extends Notification
{
    use Queueable, HasTenantBranding;

    public LeaveRequest $leaveRequest;
    public string $dashboardUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(LeaveRequest $leaveRequest)
    {
        $this->leaveRequest = $leaveRequest;

        // Build dashboard URL
        $frontendUrl = config('app.frontend_url');
        $tenant = $leaveRequest->tenant;
        $tenantSlug = $tenant ? $tenant->slug : 'default';
        $this->dashboardUrl = str_replace('://', "://{$tenantSlug}.", $frontendUrl) . '/leave/dashboard';
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
            ->subject($companyName . ' - Leave Request Declined')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your leave request has been declined.')
            ->line('**Leave Type:** ' . $this->leaveRequest->leaveType->name)
            ->line('**Period:** ' . $this->leaveRequest->start_date->format('M d, Y') . ' to ' . $this->leaveRequest->end_date->format('M d, Y'))
            ->line('**Decline Reason:** ' . ($this->leaveRequest->decline_reason ?? 'No reason provided.'))
            ->action('View Dashboard', $this->dashboardUrl)
            ->line('Please contact HR if you have any questions.');

        return $this->applyBranding($mailMessage, $tenantId);
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'leave_request_declined',
            'leave_request_id' => $this->leaveRequest->id,
            'leave_type' => $this->leaveRequest->leaveType->name,
            'reason' => $this->leaveRequest->decline_reason,
            'url' => $this->dashboardUrl,
        ];
    }
}
