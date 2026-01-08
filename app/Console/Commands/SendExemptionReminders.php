<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use App\Models\VehicleExemption;
use App\Notifications\ExemptionEndingNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendExemptionReminders extends Command
{
    protected $signature = 'exemption:send-reminders
                            {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send reminders for exemptions ending soon';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Starting exemption reminder processing...');

        if ($isDryRun) {
            $this->warn('[DRY RUN MODE] - No notifications will be sent');
        }

        // Use same intervals as tax reminders
        $reminderIntervals = Setting::getValue('reminder_intervals', [14, 7, 3, 1]);
        $this->info('Reminder intervals: ' . implode(', ', $reminderIntervals) . ' days');

        // Get users with email notifications enabled
        $users = User::where('notify_email', true)->get();

        if ($users->isEmpty()) {
            $this->warn('No users with email notifications enabled.');
            return 0;
        }

        $this->info("Found {$users->count()} user(s) with notifications enabled");

        // Get active exemptions
        $exemptions = VehicleExemption::active()
            ->with('vehicle')
            ->get();

        $this->info("Found {$exemptions->count()} active exemption(s)");

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($exemptions as $exemption) {
            $daysRemaining = $exemption->days_remaining;

            // Check if days remaining matches any interval
            if (!in_array($daysRemaining, $reminderIntervals)) {
                continue;
            }

            $vehicle = $exemption->vehicle;
            $this->line("Processing exemption for {$vehicle->reference_name}: {$daysRemaining} days remaining");

            foreach ($users as $user) {
                if ($isDryRun) {
                    $this->info("  [DRY RUN] Would send exemption reminder to {$user->name}");
                    continue;
                }

                try {
                    $user->notify(new ExemptionEndingNotification($vehicle, $exemption, $daysRemaining));
                    $sentCount++;
                    $this->info("  Sent exemption reminder to {$user->name}");
                } catch (\Exception $e) {
                    $skippedCount++;
                    Log::error("Failed to send exemption reminder", [
                        'user_id' => $user->id,
                        'exemption_id' => $exemption->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("  Failed to send to {$user->name}: {$e->getMessage()}");
                }
            }
        }

        $this->newLine();
        $this->info("Completed. Sent: {$sentCount}, Skipped: {$skippedCount}");

        return 0;
    }
}
