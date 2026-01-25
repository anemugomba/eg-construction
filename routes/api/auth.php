<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PushTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::post('/auth/logout', [AuthController::class, 'logout']);
Route::get('/auth/me', [AuthController::class, 'me']);

// Push notification token management
Route::post('/push-tokens', [PushTokenController::class, 'store']);
Route::delete('/push-tokens', [PushTokenController::class, 'destroy']);

// Legacy route
Route::get('/user', function (Request $request) {
    return $request->user();
});
