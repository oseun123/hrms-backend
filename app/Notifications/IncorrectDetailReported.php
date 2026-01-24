<?php

namespace App\Notifications;

use App\Models\Hris\Employee;
use App\Models\Hris\IncorrectDetailReport;
use App\Traits\HasTenantBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncorrectDetailReported extends Notification
{
    use Queueable, HasTenantBranding;

    public IncorrectDetailReport $report;
    public Employee $employee;
    public string $reportsUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(IncorrectDetailReport $report, Employee $employee)
    {
        $this->report = $report;
        $this->employee = $employee;

        // Build reports URL
        $frontendUrl = config('app.frontend_url');
        $tenant = $report->tenant;
        $tenantSlug = $tenant ? $tenant->slug : 'default';
        $this->reportsUrl = str_replace('://', "://{$tenantSlug}.", $frontendUrl) . '/hr/incorrect-details';
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
        $tenantId = $this->report->tenant_id;
        $branding = $this->getTenantBranding($tenantId);
        $companyName = $branding['name'] ?? config('app.name');

        $sectionName = ucfirst($this->report->section);
        $fieldName = ucwords(str_replace('_', ' ', $this->report->field_name));

        $mailMessage = (new MailMessage)
            ->subject($companyName . ' - Incorrect Detail Reported')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->employee->full_name . ' has reported an incorrect detail in their profile.')
            ->line('**Section:** ' . $sectionName)
            ->line('**Field:** ' . $fieldName)
            ->line('**Current Value:** ' . ($this->report->current_value ?: 'N/A'))
            ->line('**Reported Correct Value:** ' . ($this->report->reported_correct_value ?: 'N/A'))
            ->line('**Description:** ' . $this->report->description)
            ->action('View Reports', $this->reportsUrl)
            ->line('Please review and update the employee\'s record as needed.');

        return $this->applyBranding($mailMessage, $tenantId);
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'incorrect_detail_reported',
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->full_name,
            'section' => $this->report->section,
            'field_name' => $this->report->field_name,
            'report_id' => $this->report->id,
            'url' => $this->reportsUrl,
        ];
    }
}
