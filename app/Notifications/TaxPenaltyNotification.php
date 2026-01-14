<?php

namespace App\Notifications;

class TaxPenaltyNotification extends TaxNotification
{
    public function getNotificationType(): string
    {
        return 'tax_penalty';
    }

    public function getSubject(): string
    {
        return "CRITICAL: Penalty Incurred - {$this->vehicle->reference_name}";
    }

    public function getBodyText(): string
    {
        return "The vehicle tax for {$this->vehicle->reference_name} is now beyond the grace period. A PENALTY has been incurred. Immediate action is required to regularize the vehicle tax status.";
    }
}
