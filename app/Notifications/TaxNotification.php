<?php

namespace App\Notifications;

use App\Models\Notification as NotificationModel;
use App\Models\TaxPeriod;
use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Middleware\RateLimited;

abstract class TaxNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Vehicle $vehicle;
    protected TaxPeriod $taxPeriod;
    protected ?int $daysBeforeExpiry;

    /**
     * Number of times to attempt the job if rate limited
     */
    public int $tries = 5;

    /**
     * Seconds to wait before retrying a rate-limited job
     */
    public int $backoff = 60;

    public function __construct(Vehicle $vehicle, TaxPeriod $taxPeriod, ?int $daysBeforeExpiry = null)
    {
        $this->vehicle = $vehicle;
        $this->taxPeriod = $taxPeriod;
        $this->daysBeforeExpiry = $daysBeforeExpiry;
    }

    /**
     * Get the middleware the notification job should pass through.
     */
    public function middleware(): array
    {
        return [new RateLimited('notifications')];
    }

    public function via($notifiable): array
    {
        $channels = [];

        if ($notifiable->notify_email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    abstract protected function getNotificationType(): string;

    abstract protected function getSubject(): string;

    abstract protected function getBodyText(): string;

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getSubject())
            ->greeting("Hello {$notifiable->name},")
            ->line($this->getBodyText())
            ->line("**Vehicle:** {$this->vehicle->reference_name}")
            ->line("**Registration:** {$this->vehicle->registration_number}")
            ->line("**Tax Expiry Date:** {$this->taxPeriod->end_date->format('d M Y')}")
            ->action('View Vehicle Details', config('app.frontend_url', 'http://localhost:3000') . "/vehicles/{$this->vehicle->id}")
            ->salutation('Regards, EG Construction Fleet Management');
    }

    public function createNotificationRecord($notifiable): NotificationModel
    {
        return NotificationModel::create([
            'user_id' => $notifiable->id,
            'vehicle_id' => $this->vehicle->id,
            'tax_period_id' => $this->taxPeriod->id,
            'type' => $this->getNotificationType(),
            'channel' => 'email',
            'subject' => $this->getSubject(),
            'body' => $this->getBodyText(),
            'status' => 'pending',
            'days_before_expiry' => $this->daysBeforeExpiry,
        ]);
    }

    public function getVehicle(): Vehicle
    {
        return $this->vehicle;
    }

    public function getTaxPeriod(): TaxPeriod
    {
        return $this->taxPeriod;
    }

    public function getDaysBeforeExpiry(): ?int
    {
        return $this->daysBeforeExpiry;
    }
}
