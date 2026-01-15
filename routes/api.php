<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes are organized into separate files for maintainability:
| - api/auth.php     - Authentication routes
| - api/tax.php      - Tax & vehicle management routes
| - api/fleet.php    - Fleet maintenance routes
| - api/admin.php    - Admin-only routes
| - api/webhooks.php - Webhook endpoints (no auth)
|
*/

// Health check endpoint (no auth)
Route::get('/gestalt', function () {
    $dbConnected = false;
    try {
        DB::connection()->getPdo();
        $dbConnected = true;
    } catch (\Exception $e) {
        $dbConnected = false;
    }

    return response()->json([
        'status' => $dbConnected ? 'ok' : 'degraded',
        'api' => 'running',
        'database' => $dbConnected ? 'connected' : 'disconnected',
        'timestamp' => now()->toIso8601String(),
    ], $dbConnected ? 200 : 503);
});

// Public auth route
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    require __DIR__.'/api/auth.php';
    require __DIR__.'/api/tax.php';
    require __DIR__.'/api/fleet.php';
    require __DIR__.'/api/admin.php';
});

// Webhook routes (no auth required)
require __DIR__.'/api/webhooks.php';
