<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\TaxExpiryReminderNotification;
use App\Notifications\TaxExpiredNotification;
use App\Notifications\TaxPenaltyNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTaxReminders extends Command
{
    protected $signature = 'tax:send-reminders
                            {--dry-run : Show what would be sent without actually sending}
                            {--user= : Send only to specific user ID}
                            {--vehicle= : Process only specific vehicle ID}';

    protected $description = 'Send tax expiry reminder notifications to users';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $specificUser = $this->option('user');
        $specificVehicle = $this->option('vehicle');

        $this->info('Starting tax reminder processing...');

        if ($isDryRun) {
            $this->warn('[DRY RUN MODE] - No notifications will be sent');
        }

        // Check if email notifications are enabled globally
        if (!Setting::getValue('notifications_email_enabled', true)) {
            $this->warn('Email notifications are disabled globally. Exiting.');
            return 0;
        }

        $reminderIntervals = Setting::getValue('reminder_intervals', [14, 7, 3, 1]);
        $this->info('Reminder intervals: ' . implode(', ', $reminderIntervals) . ' days');

        // Get all users who want email notifications
        $usersQuery = User::where('notify_email', true);
        if ($specificUser) {
            $usersQuery->where('id', $specificUser);
        }
        $users = $usersQuery->get();

        if ($users->isEmpty()) {
            $this->warn('No users with email notifications enabled.');
            return 0;
        }

        $this->info("Found {$users->count()} user(s) with email notifications enabled");

        // Get vehicles needing notifications
        $vehiclesQuery = Vehicle::active()->with('currentTaxPeriod');
        if ($specificVehicle) {
            $vehiclesQuery->where('id', $specificVehicle);
        }
        $vehicles = $vehiclesQuery->get();

        $this->info("Found {$vehicles->count()} active vehicle(s)");

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($vehicles as $vehicle) {
            $taxPeriod = $vehicle->currentTaxPeriod;
            if (!$taxPeriod) {
                $this->line("  Skipping {$vehicle->reference_name}: No tax period");
                continue;
            }

            $daysRemaining = $taxPeriod->days_remaining;
            $taxStatus = $taxPeriod->tax_status;

            // Determine notification type and interval
            $notificationData = $this->determineNotification(
                $taxStatus,
                $daysRemaining,
                $reminderIntervals
            );

            if (!$notificationData) {
                continue;
            }

            $this->line("Processing {$vehicle->reference_name}: status={$taxStatus}, days={$daysRemaining}");

            foreach ($users as $user) {
                // Check if notification was already sent for this interval
                if ($this->wasAlreadySent($user, $vehicle, $notificationData['type'], $notificationData['interval'])) {
                    $skippedCount++;
                    $this->line("  Skipped {$user->email}: Already sent");
                    continue;
                }

                if ($isDryRun) {
                    $this->info("[DRY RUN] Would send {$notificationData['type']} to {$user->email} for {$vehicle->reference_name}");
                    continue;
                }

                // Create and send notification
                $notificationClass = $notificationData['class'];
                $notificationInstance = new $notificationClass($vehicle, $taxPeriod, $notificationData['interval']);

                // Create tracking record
                $record = $notificationInstance->createNotificationRecord($user);

                try {
                    $user->notify($notificationInstance);
                    $record->markAsSent();
                    $sentCount++;

                    $this->info("  Sent {$notificationData['type']} to {$user->email}");
                } catch (\Exception $e) {
                    $record->markAsFailed($e->getMessage());
                    Log::error("Failed to send notification", [
                        'user_id' => $user->id,
                        'vehicle_id' => $vehicle->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("  Failed to send to {$user->email}: {$e->getMessage()}");
                }
            }
        }

        $this->newLine();
        $this->info("Completed. Sent: {$sentCount}, Skipped (already sent): {$skippedCount}");

        return 0;
    }

    private function determineNotification(string $taxStatus, int $daysRemaining, array $intervals): ?array
    {
        // Penalty status
        if ($taxStatus === 'penalty') {
            return [
                'type' => 'tax_penalty',
                'class' => TaxPenaltyNotification::class,
                'interval' => null,
            ];
        }

        // Expired status (within grace period)
        if ($taxStatus === 'expired') {
            return [
                'type' => 'tax_expired',
                'class' => TaxExpiredNotification::class,
                'interval' => 0,
            ];
        }

        // Expiring soon - check if matches any reminder interval
        if ($taxStatus === 'expiring_soon' && $daysRemaining > 0) {
            foreach ($intervals as $interval) {
                if ($daysRemaining === $interval) {
                    return [
                        'type' => 'tax_expiry_reminder',
                        'class' => TaxExpiryReminderNotification::class,
                        'interval' => $interval,
                    ];
                }
            }
        }

        return null;
    }

    private function wasAlreadySent(User $user, Vehicle $vehicle, string $type, ?int $interval): bool
    {
        $query = Notification::where('user_id', $user->id)
            ->where('vehicle_id', $vehicle->id)
            ->where('type', $type)
            ->where('status', '!=', 'failed');

        if ($type === 'tax_expiry_reminder') {
            // For reminders, check specific interval
            $query->where('days_before_expiry', $interval);
        } else {
            // For expired/penalty, check if sent in the last 24 hours
            $query->where('created_at', '>=', now()->subDay());
        }

        return $query->exists();
    }
}
