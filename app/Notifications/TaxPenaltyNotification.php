<?php

namespace App\Notifications;

class TaxPenaltyNotification extends TaxNotification
{
    protected function getNotificationType(): string
    {
        return 'tax_penalty';
    }

    protected function getSubject(): string
    {
        return "CRITICAL: Penalty Incurred - {$this->vehicle->reference_name}";
    }

    protected function getBodyText(): string
    {
        return "The vehicle tax for {$this->vehicle->reference_name} is now beyond the grace period. A PENALTY has been incurred. Immediate action is required to regularize the vehicle tax status.";
    }
}
