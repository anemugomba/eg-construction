<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\TaxPeriodController;
use App\Http\Controllers\Api\UserPreferencesController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\VehicleExemptionController;
use App\Http\Controllers\Api\VehicleTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tax & Vehicle Management Routes
|--------------------------------------------------------------------------
*/

// Vehicles
Route::apiResource('vehicles', VehicleController::class);
Route::apiResource('vehicle-types', VehicleTypeController::class);

// Tax Periods
Route::get('vehicles/{vehicle}/tax-periods', [TaxPeriodController::class, 'index']);
Route::post('vehicles/{vehicle}/tax-periods', [TaxPeriodController::class, 'store']);
Route::put('tax-periods/{taxPeriod}', [TaxPeriodController::class, 'update']);
Route::delete('tax-periods/{taxPeriod}', [TaxPeriodController::class, 'destroy']);

// Vehicle Exemptions
Route::get('vehicles/{vehicle}/exemptions', [VehicleExemptionController::class, 'index']);
Route::post('vehicles/{vehicle}/exemptions', [VehicleExemptionController::class, 'store']);
Route::get('vehicles/{vehicle}/exemptions/current', [VehicleExemptionController::class, 'current']);
Route::get('exemptions/{exemption}', [VehicleExemptionController::class, 'show']);
Route::put('exemptions/{exemption}', [VehicleExemptionController::class, 'update']);
Route::post('exemptions/{exemption}/end', [VehicleExemptionController::class, 'endExemption']);
Route::delete('exemptions/{exemption}', [VehicleExemptionController::class, 'destroy']);

// Tax Dashboard
Route::get('dashboard/summary', [DashboardController::class, 'summary']);
Route::get('dashboard/alerts', [DashboardController::class, 'alerts']);
Route::get('dashboard/activity', [DashboardController::class, 'activity']);

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
