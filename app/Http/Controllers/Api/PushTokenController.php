<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushTokenController extends Controller
{
    /**
     * Register a push notification token for the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'device_type' => 'nullable|string|in:ios,android',
        ]);

        $request->user()->pushTokens()->updateOrCreate(
            ['token' => $validated['token']],
            ['device_type' => $validated['device_type'] ?? null]
        );

        return response()->json(['message' => 'Push token registered']);
    }

    /**
     * Remove all push tokens for the authenticated user.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->pushTokens()->delete();

        return response()->json(['message' => 'Push tokens removed']);
    }
}
