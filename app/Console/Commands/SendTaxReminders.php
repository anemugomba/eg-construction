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
                            {--vehicle= : Process only specific vehicle ID}
                            {--channel= : Send only via specific channel (email, whatsapp, sms, or all)}';

    protected $description = 'Send tax expiry reminder notifications to users via email and WhatsApp';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $specificUser = $this->option('user');
        $specificVehicle = $this->option('vehicle');
        $channelFilter = $this->option('channel') ?: 'all';

        $this->info('Starting tax reminder processing...');

        if ($isDryRun) {
            $this->warn('[DRY RUN MODE] - No notifications will be sent');
        }

        $reminderIntervals = Setting::getValue('reminder_intervals', [14, 7, 3, 1]);
        $this->info('Reminder intervals: ' . implode(', ', $reminderIntervals) . ' days');
        $this->info("Channel filter: {$channelFilter}");

        // Get all users who want any type of notification
        $usersQuery = User::where(function ($q) use ($channelFilter) {
            if ($channelFilter === 'all') {
                $q->where('notify_email', true)->orWhere('notify_whatsapp', true);
            } elseif ($channelFilter === 'email') {
                $q->where('notify_email', true);
            } elseif ($channelFilter === 'whatsapp') {
                $q->where('notify_whatsapp', true);
            }
        });

        if ($specificUser) {
            $usersQuery->where('id', $specificUser);
        }
        $users = $usersQuery->get();

        if ($users->isEmpty()) {
            $this->warn('No users with notifications enabled.');
            return 0;
        }

        $this->info("Found {$users->count()} user(s) with notifications enabled");

        // Get vehicles needing notifications (exclude exempted vehicles)
        $vehiclesQuery = Vehicle::active()->notExempted()->with('currentTaxPeriod');
        if ($specificVehicle) {
            $vehiclesQuery->where('id', $specificVehicle);
        }
        $vehicles = $vehiclesQuery->get();

        $this->info("Found {$vehicles->count()} active vehicle(s)");

        $stats = ['email' => 0, 'whatsapp' => 0, 'sms' => 0, 'skipped' => 0];

        foreach ($vehicles as $vehicle) {
            $taxPeriod = $vehicle->currentTaxPeriod;
            if (!$taxPeriod) {
                continue;
            }

            $daysRemaining = $taxPeriod->days_remaining;
            $taxStatus = $taxPeriod->tax_status;

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
                $channels = $this->getChannelsToSend($user, $vehicle, $notificationData, $channelFilter);

                if (empty($channels)) {
                    $stats['skipped']++;
                    continue;
                }

                if ($isDryRun) {
                    $channelList = implode(', ', $channels);
                    $this->info("  [DRY RUN] Would send {$notificationData['type']} via [{$channelList}] to {$user->name}");
                    continue;
                }

                // Send notification for each channel
                foreach ($channels as $channel) {
                    $this->sendNotification($user, $vehicle, $taxPeriod, $notificationData, $channel, $stats);
                }
            }
        }

        $this->newLine();
        $this->info("Completed. Email: {$stats['email']}, WhatsApp: {$stats['whatsapp']}, SMS: {$stats['sms']}, Skipped: {$stats['skipped']}");

        return 0;
    }

    private function getChannelsToSend(User $user, Vehicle $vehicle, array $notificationData, string $channelFilter): array
    {
        $channels = [];

        // Check email
        if (($channelFilter === 'all' || $channelFilter === 'email') && $user->notify_email) {
            if (!$this->wasAlreadySent($user, $vehicle, $notificationData['type'], $notificationData['interval'], 'email')) {
                $channels[] = 'email';
            }
        }

        // Check WhatsApp
        if (($channelFilter === 'all' || $channelFilter === 'whatsapp') && $user->notify_whatsapp && $user->phone) {
            if (!$this->wasAlreadySent($user, $vehicle, $notificationData['type'], $notificationData['interval'], 'whatsapp')) {
                $channels[] = 'whatsapp';
            }
        }

        // Check SMS
        if (($channelFilter === 'all' || $channelFilter === 'sms') && $user->notify_sms && $user->phone) {
            if (!$this->wasAlreadySent($user, $vehicle, $notificationData['type'], $notificationData['interval'], 'sms')) {
                $channels[] = 'sms';
            }
        }

        return $channels;
    }

    private function sendNotification(User $user, Vehicle $vehicle, $taxPeriod, array $notificationData, string $channel, array &$stats): void
    {
        $notificationClass = $notificationData['class'];
        $notificationInstance = new $notificationClass($vehicle, $taxPeriod, $notificationData['interval']);

        // Create tracking record for this channel
        $record = Notification::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'tax_period_id' => $taxPeriod->id,
            'type' => $notificationData['type'],
            'channel' => $channel,
            'subject' => $notificationInstance->getSubject(),
            'body' => $notificationInstance->getBodyText(),
            'status' => 'pending',
            'days_before_expiry' => $notificationData['interval'],
        ]);

        try {
            // Send via specific channel only
            if ($channel === 'email') {
                $user->notifyNow($notificationInstance->onlyVia('mail'));
            } elseif ($channel === 'whatsapp') {
                $user->notifyNow($notificationInstance->onlyVia(\App\Channels\WhatsAppChannel::class));
            } elseif ($channel === 'sms') {
                $user->notifyNow($notificationInstance->onlyVia(\App\Channels\AfricasTalkingSmsChannel::class));
            }

            $record->markAsSent();
            $stats[$channel]++;

            $this->info("  Sent {$notificationData['type']} via {$channel} to {$user->name}");
        } catch (\Exception $e) {
            $record->markAsFailed($e->getMessage());
            Log::error("Failed to send {$channel} notification", [
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
            $this->error("  Failed {$channel} to {$user->name}: {$e->getMessage()}");
        }
    }

    private function determineNotification(string $taxStatus, int $daysRemaining, array $intervals): ?array
    {
        if ($taxStatus === 'penalty') {
            return [
                'type' => 'tax_penalty',
                'class' => TaxPenaltyNotification::class,
                'interval' => null,
            ];
        }

        if ($taxStatus === 'expired') {
            return [
                'type' => 'tax_expired',
                'class' => TaxExpiredNotification::class,
                'interval' => 0,
            ];
        }

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

    private function wasAlreadySent(User $user, Vehicle $vehicle, string $type, ?int $interval, string $channel): bool
    {
        $query = Notification::where('user_id', $user->id)
            ->where('vehicle_id', $vehicle->id)
            ->where('type', $type)
            ->where('channel', $channel)
            ->where('status', '!=', 'failed');

        if ($type === 'tax_expiry_reminder') {
            $query->where('days_before_expiry', $interval);
        } else {
            $query->where('created_at', '>=', now()->subDay());
        }

        return $query->exists();
    }
}
