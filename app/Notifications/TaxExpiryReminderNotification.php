<?php

namespace App\Notifications;

class TaxExpiryReminderNotification extends TaxNotification
{
    public function getNotificationType(): string
    {
        return 'tax_expiry_reminder';
    }

    public function getSubject(): string
    {
        $days = abs($this->daysBeforeExpiry ?? 0);
        $dayWord = $days === 1 ? 'day' : 'days';
        return "Tax Expiry Reminder: {$this->vehicle->reference_name} - {$days} {$dayWord} remaining";
    }

    public function getBodyText(): string
    {
        $days = abs($this->daysBeforeExpiry ?? 0);
        $dayWord = $days === 1 ? 'day' : 'days';
        return "The vehicle tax for {$this->vehicle->reference_name} will expire in {$days} {$dayWord}. Please renew the tax to avoid penalties.";
    }
}
