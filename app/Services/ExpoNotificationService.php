<?php

namespace App\Services;

use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoNotificationService
{
    private string $expoApiUrl = 'https://exp.host/--/api/v2/push/send';

    /**
     * Send push notification to a specific user.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        // Check if user has push notifications enabled
        if (!$user->notify_push) {
            return;
        }

        $tokens = $user->pushTokens()->pluck('token')->toArray();
        if (empty($tokens)) {
            return;
        }

        $this->send($tokens, $title, $body, $data);
    }

    /**
     * Send push notification to multiple tokens.
     */
    public function send(array $tokens, string $title, string $body, array $data = []): void
    {
        if (empty($tokens)) {
            return;
        }

        $messages = array_map(fn($token) => [
            'to' => $token,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'sound' => 'default',
        ], $tokens);

        try {
            $response = Http::post($this->expoApiUrl, $messages);

            if (!$response->successful()) {
                Log::error('Expo push notification failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Expo push notification exception', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify all users who can approve items at a specific site.
     */
    public function notifyApprovers(Site $site, string $title, string $body, array $data = [], ?string $excludeUserId = null): void
    {
        $query = User::whereIn('role', [User::ROLE_ADMINISTRATOR, User::ROLE_SENIOR_DPF])
            ->where(function ($query) use ($site) {
                $query->whereHas('sites', fn($q) => $q->where('sites.id', $site->id))
                      ->orWhere('role', User::ROLE_ADMINISTRATOR);
            });

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        foreach ($query->get() as $approver) {
            $this->sendToUser($approver, $title, $body, $data);
        }
    }

    /**
     * Notify site staff (Site DPF and Senior DPF) at a specific site.
     */
    public function notifySiteStaff(Site $site, string $title, string $body, array $data = [], ?string $excludeUserId = null): void
    {
        $query = User::whereIn('role', [User::ROLE_SITE_DPF, User::ROLE_SENIOR_DPF])
            ->where(function ($query) use ($site) {
                $query->whereHas('sites', fn($q) => $q->where('sites.id', $site->id))
                      ->orWhere('role', User::ROLE_ADMINISTRATOR);
            });

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        foreach ($query->get() as $user) {
            $this->sendToUser($user, $title, $body, $data);
        }
    }
}
