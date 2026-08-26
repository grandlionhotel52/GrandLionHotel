<?php

namespace Tests\Feature;

use App\Mail\BookingCheckInReminderMail;
use App\Mail\BookingExpiredMail;
use App\Mail\PaymentDueReminderMail;
use App\Models\Booking;
use App\Models\Customer;
use App\Notifications\BookingAutomationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_deadline_uses_two_pm_on_the_arrival_date_instead_of_midnight(): void
    {
        $this->travelTo('2026-08-26 09:00:00');
        config()->set('booking_automation.payment_due_hours', 72);

        $booking = Booking::factory()->create([
            'status' => 'pending',
            'check_in' => '2026-08-28',
            'check_out' => '2026-08-29',
            'payment_due_at' => null,
        ]);

        $booking->update(['status' => 'confirmed']);

        $this->assertSame('2026-08-28 14:00:00', $booking->fresh()->payment_due_at->format('Y-m-d H:i:s'));
    }

    public function test_stale_unpaid_pending_booking_expires_and_customer_is_notified(): void
    {
        Mail::fake();
        Notification::fake();
        config()->set('booking_automation.pending_expiration_hours', 24);

        $booking = Booking::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subHours(25),
        ]);
        $customer = $booking->customer;

        $this->artisan('bookings:process-automation')->assertSuccessful();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertNotNull($booking->expired_at);
        Mail::assertQueued(BookingExpiredMail::class, fn (BookingExpiredMail $mail): bool => $mail->booking->is($booking));
        Notification::assertSentTo(
            $customer,
            BookingAutomationNotification::class,
            fn (BookingAutomationNotification $notification): bool => data_get($notification->toArray($customer), 'event') === 'booking_expired'
        );
    }

    public function test_recent_or_paid_pending_bookings_do_not_expire(): void
    {
        Mail::fake();
        Notification::fake();

        $recent = Booking::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subHours(2),
        ]);
        $paid = Booking::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subHours(30),
        ]);
        $paid->payment()->update(['status' => 'paid', 'paid_at' => now()]);

        $this->artisan('bookings:process-automation')->assertSuccessful();

        $this->assertSame('pending', $recent->fresh()->status);
        $this->assertSame('pending', $paid->fresh()->status);
        Mail::assertNothingQueued();
    }

    public function test_confirmed_booking_receives_only_one_check_in_reminder(): void
    {
        Mail::fake();
        Notification::fake();
        config()->set('booking_automation.check_in_reminder_days', 1);

        $booking = Booking::factory()->create([
            'status' => 'confirmed',
            'check_in' => today()->addDay(),
            'check_out' => today()->addDays(3),
        ]);
        $customer = Customer::query()->findOrFail($booking->customer_id);

        $this->artisan('bookings:process-automation')->assertSuccessful();
        $this->artisan('bookings:process-automation')->assertSuccessful();

        $this->assertNotNull($booking->fresh()->check_in_reminder_sent_at);
        Mail::assertQueued(BookingCheckInReminderMail::class, 1);
        Notification::assertSentTo(
            $customer,
            BookingAutomationNotification::class,
            fn (BookingAutomationNotification $notification): bool => data_get($notification->toArray($customer), 'event') === 'check_in_reminder'
        );
    }

    public function test_overdue_unpaid_confirmed_booking_is_cancelled_but_paid_booking_is_preserved(): void
    {
        Mail::fake();
        Notification::fake();

        $overdue = Booking::factory()->create([
            'status' => 'confirmed',
            'payment_due_at' => now()->subMinute(),
        ]);
        $overdue->payment()->update(['status' => 'unpaid', 'paid_at' => null]);

        $paid = Booking::factory()->create([
            'status' => 'confirmed',
            'payment_due_at' => now()->subMinute(),
        ]);
        $paid->payment()->update(['status' => 'paid', 'paid_at' => now()]);

        $this->artisan('bookings:process-automation')->assertSuccessful();

        $this->assertSame('cancelled', $overdue->fresh()->status);
        $this->assertNotNull($overdue->fresh()->expired_at);
        $this->assertSame('confirmed', $paid->fresh()->status);
        Mail::assertQueued(BookingExpiredMail::class, fn (BookingExpiredMail $mail): bool => $mail->booking->is($overdue));
    }

    public function test_upcoming_payment_deadline_sends_only_one_reminder(): void
    {
        Mail::fake();
        Notification::fake();
        config()->set('booking_automation.payment_reminder_hours', 6);

        $booking = Booking::factory()->create([
            'status' => 'confirmed',
            'payment_due_at' => now()->addHours(3),
            'payment_reminder_sent_at' => null,
        ]);
        $booking->payment()->update(['status' => 'unpaid', 'paid_at' => null]);

        $this->artisan('bookings:process-automation')->assertSuccessful();
        $this->artisan('bookings:process-automation')->assertSuccessful();

        $this->assertNotNull($booking->fresh()->payment_reminder_sent_at);
        Mail::assertQueued(PaymentDueReminderMail::class, 1);
    }

    public function test_missed_check_in_is_marked_as_no_show_after_grace_period(): void
    {
        Mail::fake();
        Notification::fake();
        config()->set('booking_automation.no_show_grace_hours', 24);

        $booking = Booking::factory()->create([
            'status' => 'confirmed',
            'check_in' => today()->subDays(2),
            'check_out' => today()->addDay(),
            'actual_check_in_at' => null,
            'no_show_at' => null,
        ]);
        $booking->payment()->update(['status' => 'paid', 'paid_at' => now()->subDays(3)]);

        $this->artisan('bookings:process-automation')->assertSuccessful();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertNotNull($booking->no_show_at);
        $this->assertStringContainsString('no-show', $booking->cancellation_reason);
    }
}
