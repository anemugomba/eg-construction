<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserPreferencesController extends Controller
{
    /**
     * Get current user's preferences
     */
    public function show(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'notify_email' => $user->notify_email,
                'notify_sms' => $user->notify_sms,
                'notify_whatsapp' => $user->notify_whatsapp,
            ],
        ]);
    }

    /**
     * Update current user's preferences
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'notify_email' => 'sometimes|boolean',
            'notify_sms' => 'sometimes|boolean',
            'notify_whatsapp' => 'sometimes|boolean',
        ]);

        $user = Auth::user();
        $user->update($validated);

        return response()->json([
            'message' => 'Preferences updated successfully',
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'notify_email' => $user->notify_email,
                'notify_sms' => $user->notify_sms,
                'notify_whatsapp' => $user->notify_whatsapp,
            ],
        ]);
    }
}
