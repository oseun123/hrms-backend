<?php

namespace App\Notifications;

use App\Models\Hris\Employee;
use App\Models\Hris\ProfileChangeRequest;
use App\Traits\HasTenantBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProfileChangeRequestSubmitted extends Notification
{
    use Queueable, HasTenantBranding;

    public ProfileChangeRequest $request;
    public Employee $employee;
    public string $approvalQueueUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(ProfileChangeRequest $request, Employee $employee)
    {
        $this->request = $request;
        $this->employee = $employee;

        // Build approval queue URL
        $frontendUrl = config('app.frontend_url');
        $tenant = $request->tenant;
        $tenantSlug = $tenant ? $tenant->slug : 'default';
        $this->approvalQueueUrl = str_replace('://', "://{$tenantSlug}.", $frontendUrl) . '/hr/approvals';
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
        $tenantId = $this->request->tenant_id;
        $branding = $this->getTenantBranding($tenantId);
        $companyName = $branding['name'] ?? config('app.name');

        $sectionName = ucwords(str_replace('_', ' ', $this->request->section));

        $mailMessage = (new MailMessage)
            ->subject($companyName . ' - Profile Change Request')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->employee->full_name . ' has submitted a profile change request.')
            ->line('**Section:** ' . $sectionName)
            ->line('**Submitted:** ' . $this->request->submitted_at->diffForHumans())
            ->action('Review Request', $this->approvalQueueUrl)
            ->line('Please review and approve or decline this request.');

        return $this->applyBranding($mailMessage, $tenantId);
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'profile_change_request',
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->full_name,
            'section' => $this->request->section,
            'request_id' => $this->request->id,
            'url' => $this->approvalQueueUrl,
        ];
    }
}
