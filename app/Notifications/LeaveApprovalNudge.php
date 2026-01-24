<?php

namespace App\Notifications;

use App\Models\Leave\LeaveApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveApprovalNudge extends Notification
{
    use Queueable;

    protected $leaveApproval;

    public function __construct(LeaveApproval $leaveApproval)
    {
        $this->leaveApproval = $leaveApproval;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $request = $this->leaveApproval->leaveRequest;
        $employee = $request->employee;

        return (new MailMessage)
            ->subject('Reminder: Leave Approval Pending')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('This is a friendly reminder that a leave request from ' . $employee->full_name . ' is awaiting your approval.')
            ->line('Leave Type: ' . $request->leaveType->name)
            ->line('Duration: ' . $request->start_date . ' to ' . $request->end_date . ' (' . $request->duration_days . ' days)')
            ->action('View Request', url('/leave/approvals'))
            ->line('Thank you for your prompt attention.');
    }

    public function toArray($notifiable)
    {
        $request = $this->leaveApproval->leaveRequest;
        $employee = $request->employee;

        return [
            'type' => 'leave_nudge',
            'title' => 'Reminder: Leave Approval Pending',
            'message' => $employee->full_name . ' has sent a reminder for their leave request.',
            'leave_request_id' => $request->id,
            'action_url' => '/leave/approvals',
        ];
    }
}
