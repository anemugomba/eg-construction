<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function () {
    // User Management
    Route::apiResource('users', UserController::class);
    Route::get('users/{user}/sites', [UserController::class, 'sites']);
    Route::post('users/{user}/sites', [UserController::class, 'assignSites']);
    Route::delete('users/{user}/sites/{site}', [UserController::class, 'removeSite']);
});
