<?php

namespace App\Notifications;

class TaxExpiredNotification extends TaxNotification
{
    public function getNotificationType(): string
    {
        return 'tax_expired';
    }

    public function getSubject(): string
    {
        return "URGENT: Vehicle Tax Expired - {$this->vehicle->reference_name}";
    }

    public function getBodyText(): string
    {
        $daysOverdue = abs($this->taxPeriod->days_remaining);
        $dayWord = $daysOverdue === 1 ? 'day' : 'days';
        return "The vehicle tax for {$this->vehicle->reference_name} has EXPIRED. It is now {$daysOverdue} {$dayWord} overdue. Please renew immediately to avoid penalties.";
    }
}
