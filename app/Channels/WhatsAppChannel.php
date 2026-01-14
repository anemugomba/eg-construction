<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    /**
     * Send the given notification via WhatsApp.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Check if user has WhatsApp notifications enabled and has a phone number
        if (!$notifiable->notify_whatsapp || empty($notifiable->phone)) {
            return;
        }

        // Get the WhatsApp message from the notification
        if (!method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);

        if (empty($message)) {
            return;
        }

        $this->sendWhatsAppMessage(
            $this->formatPhoneNumber($notifiable->phone),
            $message
        );
    }

    /**
     * Send WhatsApp message via Meta Cloud API.
     */
    protected function sendWhatsAppMessage(string $to, string $message): void
    {
        $accessToken = config('services.whatsapp.access_token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        if (empty($accessToken) || empty($phoneNumberId)) {
            Log::error('Meta WhatsApp configuration missing');
            return;
        }

        try {
            $response = Http::withToken($accessToken)
                ->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => true,
                        'body' => $message,
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Meta WhatsApp API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'to' => $to,
                ]);
            } else {
                Log::info('WhatsApp message sent successfully', [
                    'to' => $to,
                    'message_id' => $response->json('messages.0.id'),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp message', [
                'error' => $e->getMessage(),
                'to' => $to,
            ]);
            throw $e;
        }
    }

    /**
     * Format phone number for WhatsApp (must be in E.164 format without +).
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 0, assume Zimbabwe number and add country code
        if (str_starts_with($phone, '0')) {
            $phone = '263' . substr($phone, 1);
        }

        return $phone;
    }
}
