<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Room;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\Staff;
use App\Observers\OperationalAuditObserver;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
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
        Room::observe(OperationalAuditObserver::class);
        Admin::observe(OperationalAuditObserver::class);
        Staff::observe(OperationalAuditObserver::class);
        Customer::observe(OperationalAuditObserver::class);

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof Model) {
                app(AuditLogger::class)->recordAuthentication($event->user, 'logged_in', $event->guard);
            }
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user instanceof Model) {
                app(AuditLogger::class)->recordAuthentication($event->user, 'logged_out', $event->guard);
            }
        });

        if (config('app.force_https')) {
            URL::forceScheme('https');
        }
    }
}
