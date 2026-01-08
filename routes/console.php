<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tax reminder notifications - runs daily at 8am
Schedule::command('tax:send-reminders')->dailyAt('08:00');

// Exemption reminders - runs daily at 8am
Schedule::command('exemption:send-reminders')->dailyAt('08:00');

// Process expired exemptions - runs daily at midnight
Schedule::command('exemption:process-expired')->dailyAt('00:05');
