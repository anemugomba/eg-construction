<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Get notification settings
     */
    public function notifications(): JsonResponse
    {
        return response()->json([
            'data' => [
                'reminder_intervals' => Setting::getValue('reminder_intervals', [14, 7, 3, 1]),
                'email_enabled' => Setting::getValue('notifications_email_enabled', true),
                'sms_enabled' => Setting::getValue('notifications_sms_enabled', false),
                'whatsapp_enabled' => Setting::getValue('notifications_whatsapp_enabled', false),
            ],
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateNotifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reminder_intervals' => 'sometimes|array',
            'reminder_intervals.*' => 'integer|min:1|max:90',
            'email_enabled' => 'sometimes|boolean',
            'sms_enabled' => 'sometimes|boolean',
            'whatsapp_enabled' => 'sometimes|boolean',
        ]);

        if (isset($validated['reminder_intervals'])) {
            // Sort in descending order and remove duplicates
            $intervals = array_unique($validated['reminder_intervals']);
            rsort($intervals);
            Setting::setValue('reminder_intervals', array_values($intervals), 'json', 'notifications');
        }

        if (isset($validated['email_enabled'])) {
            Setting::setValue('notifications_email_enabled', $validated['email_enabled'], 'boolean', 'notifications');
        }

        if (isset($validated['sms_enabled'])) {
            Setting::setValue('notifications_sms_enabled', $validated['sms_enabled'], 'boolean', 'notifications');
        }

        if (isset($validated['whatsapp_enabled'])) {
            Setting::setValue('notifications_whatsapp_enabled', $validated['whatsapp_enabled'], 'boolean', 'notifications');
        }

        return response()->json([
            'message' => 'Notification settings updated successfully',
            'data' => [
                'reminder_intervals' => Setting::getValue('reminder_intervals', [14, 7, 3, 1]),
                'email_enabled' => Setting::getValue('notifications_email_enabled', true),
                'sms_enabled' => Setting::getValue('notifications_sms_enabled', false),
                'whatsapp_enabled' => Setting::getValue('notifications_whatsapp_enabled', false),
            ],
        ]);
    }
}
