<?php

namespace App\Providers;

use App\Models\TaxPeriod;
use App\Models\Vehicle;
use App\Observers\TaxPeriodObserver;
use App\Observers\VehicleObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers
        Vehicle::observe(VehicleObserver::class);
        TaxPeriod::observe(TaxPeriodObserver::class);

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
