<?php

namespace App\Notifications;

use App\Models\Payroll\LeaveAllowanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

use App\Traits\HasTenantBranding;

class LeaveAllowanceDeclinedNotification extends Notification
{
    use Queueable, HasTenantBranding;

    protected LeaveAllowanceRequest $leaveAllowanceRequest;

    public function __construct(LeaveAllowanceRequest $leaveAllowanceRequest)
    {
        $this->leaveAllowanceRequest = $leaveAllowanceRequest;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        $tenantId = $this->leaveAllowanceRequest->tenant_id;
        $branding = $this->getTenantBranding($tenantId);
        $companyName = $branding['name'] ?? config('app.name');

        $mailMessage = (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject($companyName . ' - Leave Allowance Request Declined')
            ->greeting('Hello ' . ($notifiable->name ?? 'Employee') . '!')
            ->line("Your leave allowance request for leave year {$this->leaveAllowanceRequest->leave_year} has been declined.")
            ->line('**Reason:** ' . ($this->leaveAllowanceRequest->decline_reason ?? 'No reason provided.'))
            ->action('View My Requests', config('app.frontend_url') . '/leave/my-requests')
            ->line('Please contact HR/Finance if you have any questions.');

        return $this->applyBranding($mailMessage, $tenantId);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'leave_allowance_declined',
            'title' => 'Leave Allowance Declined',
            'message' => "Your leave allowance request for leave year {$this->leaveAllowanceRequest->leave_year} has been declined. " .
                "Reason: " . ($this->leaveAllowanceRequest->decline_reason ?? 'No reason provided.'),
            'leave_allowance_request_id' => $this->leaveAllowanceRequest->id,
            'amount' => $this->leaveAllowanceRequest->amount,
            'leave_year' => $this->leaveAllowanceRequest->leave_year,
            'decline_reason' => $this->leaveAllowanceRequest->decline_reason,
            'action_url' => '/leave/my-requests',
        ];
    }
}
