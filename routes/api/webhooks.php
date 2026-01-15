<?php

use App\Http\Controllers\Api\ResendWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

Route::post('webhooks/resend', [ResendWebhookController::class, 'handle']);
