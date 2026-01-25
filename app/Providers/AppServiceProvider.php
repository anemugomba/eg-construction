<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Models\TaxPeriod;
use App\Models\Vehicle;
use App\Models\VehicleExemption;
use App\Observers\TaxPeriodObserver;
use App\Observers\VehicleExemptionObserver;
use App\Observers\VehicleObserver;
use App\Services\ExpoNotificationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Expo notification service as singleton
        $this->app->singleton(ExpoNotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use custom PersonalAccessToken model with UUIDs
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Register observers
        Vehicle::observe(VehicleObserver::class);
        TaxPeriod::observe(TaxPeriodObserver::class);
        VehicleExemption::observe(VehicleExemptionObserver::class);

        // Rate limit for email/whatsapp notifications: 10 per minute
        RateLimiter::for('notifications', function (object $job) {
            return Limit::perMinute(10);
        });

        // Rate limit for SMS: 1 per minute (SMS is expensive)
        RateLimiter::for('sms', function (object $job) {
            return Limit::perMinute(1);
        });
    }
}
