<?php

namespace App\Notifications;

use App\Models\Vehicle;
use App\Models\VehicleExemption;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExemptionEndingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Vehicle $vehicle;
    protected VehicleExemption $exemption;
    protected int $daysRemaining;

    public function __construct(Vehicle $vehicle, VehicleExemption $exemption, int $daysRemaining)
    {
        $this->vehicle = $vehicle;
        $this->exemption = $exemption;
        $this->daysRemaining = $daysRemaining;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->getSubject();
        $endDate = $this->exemption->end_date->format('d M Y');

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name},")
            ->line("This is a reminder that the exemption for **{$this->vehicle->reference_name}** is ending soon.")
            ->line("**Exemption End Date:** {$endDate}")
            ->line("**Days Remaining:** {$this->daysRemaining} day(s)")
            ->line('Once the exemption ends, the vehicle will need to have valid road tax or be placed on a new exemption.')
            ->action('View Vehicle', config('app.frontend_url', 'http://localhost:3000') . '/vehicles/' . $this->vehicle->id)
            ->line('Please take action before the exemption expires to avoid any compliance issues.');
    }

    public function getSubject(): string
    {
        return "Exemption Ending Soon: {$this->vehicle->reference_name} - {$this->daysRemaining} days remaining";
    }

    public function getBodyText(): string
    {
        $endDate = $this->exemption->end_date->format('d M Y');
        return "The exemption for {$this->vehicle->reference_name} ends on {$endDate} ({$this->daysRemaining} days remaining). Please renew the tax or extend the exemption.";
    }

    public function toArray(object $notifiable): array
    {
        return [
            'vehicle_id' => $this->vehicle->id,
            'vehicle_name' => $this->vehicle->reference_name,
            'exemption_id' => $this->exemption->id,
            'days_remaining' => $this->daysRemaining,
            'end_date' => $this->exemption->end_date->toDateString(),
        ];
    }
}
