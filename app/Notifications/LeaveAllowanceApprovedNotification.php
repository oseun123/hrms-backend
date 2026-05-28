<?php

namespace App\Notifications;

use App\Models\Payroll\LeaveAllowanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

use App\Traits\HasTenantBranding;

class LeaveAllowanceApprovedNotification extends Notification
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
            ->subject($companyName . ' - Leave Allowance Approved')
            ->greeting('Hello ' . ($notifiable->name ?? 'Employee') . '!')
            ->line('Your leave allowance request has been approved.')
            ->line('**Amount:** ' . number_format($this->leaveAllowanceRequest->amount, 2))
            ->line('**Leave Year:** ' . $this->leaveAllowanceRequest->leave_year)
            ->line('The approved amount will be included in your next payroll payment.')
            ->action('View Payslips', config('app.frontend_url') . '/payslips')
            ->line('Thank you!');

        return $this->applyBranding($mailMessage, $tenantId);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'leave_allowance_approved',
            'title' => 'Leave Allowance Approved',
            'message' => "Your leave allowance request of " .
                number_format($this->leaveAllowanceRequest->amount, 2) .
                " for leave year {$this->leaveAllowanceRequest->leave_year} has been approved. " .
                "It will be included in your next payroll.",
            'leave_allowance_request_id' => $this->leaveAllowanceRequest->id,
            'amount' => $this->leaveAllowanceRequest->amount,
            'leave_year' => $this->leaveAllowanceRequest->leave_year,
            'action_url' => '/payslips',
        ];
    }
}
