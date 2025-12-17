<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ResendWebhookController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\TaxPeriodController;
use App\Http\Controllers\Api\UserPreferencesController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\VehicleTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'timestamp' => now()->toIso8601String(),
]));

// Auth routes
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Legacy route
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Vehicles
    Route::apiResource('vehicles', VehicleController::class);
    Route::apiResource('vehicle-types', VehicleTypeController::class);

    // Tax Periods
    Route::get('vehicles/{vehicle}/tax-periods', [TaxPeriodController::class, 'index']);
    Route::post('vehicles/{vehicle}/tax-periods', [TaxPeriodController::class, 'store']);
    Route::put('tax-periods/{taxPeriod}', [TaxPeriodController::class, 'update']);
    Route::delete('tax-periods/{taxPeriod}', [TaxPeriodController::class, 'destroy']);

    // Dashboard
    Route::get('dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('dashboard/alerts', [DashboardController::class, 'alerts']);

    // User Preferences
    Route::get('user/preferences', [UserPreferencesController::class, 'show']);
    Route::put('user/preferences', [UserPreferencesController::class, 'update']);

    // Global Settings
    Route::get('settings/notifications', [SettingsController::class, 'notifications']);
    Route::put('settings/notifications', [SettingsController::class, 'updateNotifications']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/summary', [NotificationController::class, 'summary']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});

// Webhooks (no auth required)
Route::post('webhooks/resend', [ResendWebhookController::class, 'handle']);
