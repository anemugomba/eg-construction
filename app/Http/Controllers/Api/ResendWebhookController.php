<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ResendWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload = $request->all();
        $eventType = $payload['type'] ?? null;
        $data = $payload['data'] ?? [];

        Log::info('Resend webhook received', ['type' => $eventType, 'data' => $data]);

        $emailId = $data['email_id'] ?? null;

        if (!$emailId) {
            return response('OK', 200);
        }

        // Find notification by Resend ID
        $notification = Notification::where('resend_id', $emailId)->first();

        if (!$notification) {
            Log::info('Notification not found for Resend ID', ['email_id' => $emailId]);
            return response('OK', 200);
        }

        switch ($eventType) {
            case 'email.sent':
                $notification->update(['status' => 'sent', 'sent_at' => now()]);
                break;

            case 'email.delivered':
                $notification->markAsDelivered();
                break;

            case 'email.bounced':
            case 'email.complained':
                $notification->markAsFailed("Email {$eventType}");
                break;
        }

        return response('OK', 200);
    }
}
