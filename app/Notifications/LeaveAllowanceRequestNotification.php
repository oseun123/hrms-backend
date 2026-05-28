<?php

namespace App\Notifications;

use App\Models\Payroll\LeaveAllowanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

use App\Traits\HasTenantBranding;

class LeaveAllowanceRequestNotification extends Notification
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

        $employee = $this->leaveAllowanceRequest->employee;
        $leaveRequest = $this->leaveAllowanceRequest->leaveRequest;

        $mailMessage = (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject($companyName . ' - New Leave Allowance Request')
            ->greeting('Hello Finance Admin!')
            ->line("{$employee->full_name} has submitted a new leave allowance request.")
            ->line('**Amount:** ' . number_format($this->leaveAllowanceRequest->amount, 2))
            ->line('**Leave Year:** ' . $this->leaveAllowanceRequest->leave_year)
            ->line('**Leave Period:** ' . $leaveRequest->start_date->format('M d, Y') . ' to ' . $leaveRequest->end_date->format('M d, Y'))
            ->action('View Requests', config('app.frontend_url') . '/payroll/leave-allowances')
            ->line('Please review and process this request.');

        return $this->applyBranding($mailMessage, $tenantId);
    }

    public function toDatabase(object $notifiable): array
    {
        $employee = $this->leaveAllowanceRequest->employee;
        $leaveRequest = $this->leaveAllowanceRequest->leaveRequest;

        return [
            'type' => 'leave_allowance_request',
            'title' => 'Leave Allowance Request',
            'message' => "{$employee->full_name} has requested their annual leave allowance of " .
                number_format($this->leaveAllowanceRequest->amount, 2) .
                " for leave year {$this->leaveAllowanceRequest->leave_year}.",
            'leave_allowance_request_id' => $this->leaveAllowanceRequest->id,
            'employee_id' => $employee->id,
            'employee_name' => $employee->full_name,
            'leave_request_id' => $leaveRequest->id,
            'leave_dates' => $leaveRequest->start_date->format('Y-m-d') . ' to ' . $leaveRequest->end_date->format('Y-m-d'),
            'amount' => $this->leaveAllowanceRequest->amount,
            'leave_year' => $this->leaveAllowanceRequest->leave_year,
            'action_url' => '/payroll/leave-allowances',
        ];
    }
}
