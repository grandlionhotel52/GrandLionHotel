<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Observers\OperationalAuditObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
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
        Paginator::useBootstrapFive();

        Booking::observe(OperationalAuditObserver::class);
        Payment::observe(OperationalAuditObserver::class);
        RefundRequest::observe(OperationalAuditObserver::class);

        if (config('app.force_https')) {
            URL::forceScheme('https');
        }
    }
}
