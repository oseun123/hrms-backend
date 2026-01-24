<?php

namespace App\Notifications;

use App\Models\Hris\ProfileChangeRequest;
use App\Models\User;
use App\Traits\HasTenantBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProfileChangeRequestDeclined extends Notification
{
    use Queueable, HasTenantBranding;

    public ProfileChangeRequest $request;
    public User $reviewer;

    /**
     * Create a new notification instance.
     */
    public function __construct(ProfileChangeRequest $request, User $reviewer)
    {
        $this->request = $request;
        $this->reviewer = $reviewer;
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
        $supportEmail = $branding['support_email'] ?? $branding['hr_email'] ?? config('mail.from.address');

        $sectionName = ucwords(str_replace('_', ' ', $this->request->section));

        $mailMessage = (new MailMessage)
            ->subject($companyName . ' - Profile Update Request Declined')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your profile change request has been **declined**.')
            ->line('**Section:** ' . $sectionName)
            ->line('**Reviewed by:** ' . $this->reviewer->name)
            ->line('**Reviewed on:** ' . $this->request->reviewed_at->format('F j, Y \a\t g:i A'))
            ->line('**Reason:** ' . $this->request->decline_reason)
            ->line('If you have questions about this decision, please contact HR at ' . $supportEmail . '.');

        return $this->applyBranding($mailMessage, $tenantId);
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'profile_change_declined',
            'section' => $this->request->section,
            'request_id' => $this->request->id,
            'reviewer_name' => $this->reviewer->name,
            'reviewed_at' => $this->request->reviewed_at,
            'decline_reason' => $this->request->decline_reason,
        ];
    }
}
