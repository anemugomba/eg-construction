<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Notification reminder intervals (days before expiry)
        Setting::setValue(
            'reminder_intervals',
            [14, 7, 3, 1],
            'json',
            'notifications',
            'Days before expiry to send reminder notifications'
        );

        // Enable/disable notification channels globally
        Setting::setValue(
            'notifications_email_enabled',
            true,
            'boolean',
            'notifications',
            'Enable email notifications globally'
        );

        Setting::setValue(
            'notifications_sms_enabled',
            false,
            'boolean',
            'notifications',
            'Enable SMS notifications globally'
        );

        Setting::setValue(
            'notifications_whatsapp_enabled',
            false,
            'boolean',
            'notifications',
            'Enable WhatsApp notifications globally'
        );
    }
}
