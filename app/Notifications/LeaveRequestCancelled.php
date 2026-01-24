<?php

namespace App\Notifications;

use App\Models\Leave\LeaveRequest;
use App\Traits\HasTenantBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestCancelled extends Notification
{
    use Queueable, HasTenantBranding;

    public LeaveRequest $leaveRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(LeaveRequest $leaveRequest)
    {
        $this->leaveRequest = $leaveRequest;
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
            ->subject($companyName . ' - Leave Request Cancelled')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->leaveRequest->employee->full_name . ' has cancelled their leave request.')
            ->line('**Leave Type:** ' . $this->leaveRequest->leaveType->name)
            ->line('**Period:** ' . $this->leaveRequest->start_date->format('M d, Y') . ' to ' . $this->leaveRequest->end_date->format('M d, Y'))
            ->line('**Duration:** ' . $this->leaveRequest->duration_days . ' days')
            ->line('This request has been removed from your approval queue.');

        return $this->applyBranding($mailMessage, $tenantId);
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'leave_request_cancelled',
            'leave_request_id' => $this->leaveRequest->id,
            'employee_name' => $this->leaveRequest->employee->full_name,
            'leave_type' => $this->leaveRequest->leaveType->name,
            'duration' => $this->leaveRequest->duration_days,
            'message' => $this->leaveRequest->employee->full_name . ' cancelled their leave request.',
        ];
    }
}
