<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Payroll\MonthlyPayment;

class PayrollAuthorized extends Notification
{
    use Queueable;

    protected $payment;

    /**
     * Create a new notification instance.
     */
    public function __construct(MonthlyPayment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $monthName = date("F", mktime(0, 0, 0, $this->payment->batchPayment->month, 10));
        $year = $this->payment->batchPayment->year;

        return (new MailMessage)
            ->subject("Your Payslip for {$monthName} {$year} is Ready")
            ->greeting("Hello " . $notifiable->full_name . ",")
            ->line("Your payroll for {$monthName} {$year} has been processed and authorized.")
            ->line("Net Salary: " . number_format($this->payment->net_salary, 2))
            ->action('Download Payslip', url('/payroll/payslips/' . $this->payment->id))
            ->line('Thank you for your hard work!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $monthName = date("F", mktime(0, 0, 0, $this->payment->batchPayment->month, 10));

        return [
            'type' => 'payroll',
            'title' => 'Payslip Ready',
            'message' => "Your payslip for {$monthName} {$this->payment->batchPayment->year} is now available.",
            'payment_id' => $this->payment->id,
            'action_url' => '/payroll/payslips/' . $this->payment->id,
        ];
    }
}
