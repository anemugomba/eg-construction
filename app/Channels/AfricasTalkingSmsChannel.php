<?php

namespace App\Channels;

use AfricasTalking\SDK\AfricasTalking;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AfricasTalkingSmsChannel
{
    protected AfricasTalking $client;

    public function __construct()
    {
        $username = config('services.africastalking.username');
        $apiKey = config('services.africastalking.api_key');

        $this->client = new AfricasTalking($username, $apiKey);
    }

    /**
     * Send the given notification via SMS.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Check if user has SMS notifications enabled and has a phone number
        if (!$notifiable->notify_sms || empty($notifiable->phone)) {
            return;
        }

        // Get the SMS message from the notification
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);

        if (empty($message)) {
            return;
        }

        // Apply rate limiting: 1 SMS per minute
        $executed = RateLimiter::attempt(
            'sms-notifications',
            1, // 1 attempt
            function () use ($notifiable, $message) {
                $this->sendSms(
                    $this->formatPhoneNumber($notifiable->phone),
                    $message
                );
            },
            60 // per 60 seconds
        );

        if (!$executed) {
            Log::warning('SMS rate limited, will retry later', [
                'to' => $notifiable->phone,
            ]);
            // Re-throw to trigger job retry
            throw new \Exception('SMS rate limited, retrying...');
        }
    }

    /**
     * Send SMS via Africa's Talking API.
     */
    protected function sendSms(string $to, string $message): void
    {
        $sms = $this->client->sms();

        $options = [
            'to' => [$to],
            'message' => $message,
        ];

        // Add sender ID if configured
        $senderId = config('services.africastalking.sender_id');
        if (!empty($senderId)) {
            $options['from'] = $senderId;
        }

        try {
            $result = $sms->send($options);

            if ($result['status'] === 'success') {
                $recipient = $result['data']->SMSMessageData->Recipients[0] ?? null;

                if ($recipient && $recipient->status === 'Success') {
                    Log::info('SMS sent successfully via Africa\'s Talking', [
                        'to' => $to,
                        'messageId' => $recipient->messageId ?? null,
                        'cost' => $recipient->cost ?? null,
                    ]);
                } else {
                    Log::warning('SMS sent but recipient status not success', [
                        'to' => $to,
                        'status' => $recipient->status ?? 'unknown',
                    ]);
                }
            } else {
                Log::error('Africa\'s Talking SMS API error', [
                    'to' => $to,
                    'status' => $result['status'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send SMS via Africa\'s Talking', [
                'error' => $e->getMessage(),
                'to' => $to,
            ]);
            throw $e;
        }
    }

    /**
     * Format phone number for Africa's Talking (E.164 format with +).
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove any non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // If starts with 0, assume Zimbabwe number and add country code
        if (str_starts_with($phone, '0')) {
            $phone = '+263' . substr($phone, 1);
        }

        // Ensure it starts with +
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }
}
