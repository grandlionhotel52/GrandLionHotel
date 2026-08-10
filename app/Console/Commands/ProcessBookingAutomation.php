<?php

namespace App\Console\Commands;

use App\Mail\BookingCheckInReminderMail;
use App\Mail\BookingExpiredMail;
use App\Models\Booking;
use App\Notifications\BookingAutomationNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ProcessBookingAutomation extends Command
{
    protected $signature = 'bookings:process-automation';

    protected $description = 'Expire stale pending bookings and send upcoming check-in reminders';

    public function handle(): int
    {
        $expired = $this->expirePendingBookings();
        $reminded = $this->sendCheckInReminders();

        $this->info("Expired {$expired} pending booking(s); sent {$reminded} check-in reminder(s).");

        return self::SUCCESS;
    }

    private function expirePendingBookings(): int
    {
        $hours = max(1, (int) config('booking_automation.pending_expiration_hours', 24));
        $cutoff = now()->subHours($hours);
        $expired = 0;

        Booking::query()
            ->where('status', 'pending')
            ->whereNull('expired_at')
            ->where('created_at', '<=', $cutoff)
            ->whereDoesntHave('payment', static function ($query): void {
                $query->whereIn('status', ['paid', 'pending_verification', 'refund_pending']);
            })
            ->select('booking_id')
            ->chunkById(100, function ($bookings) use (&$expired): void {
                foreach ($bookings as $candidate) {
                    $booking = DB::transaction(function () use ($candidate): ?Booking {
                        $locked = Booking::query()
                            ->with(['customer', 'room', 'guestDetail', 'payment'])
                            ->lockForUpdate()
                            ->find($candidate->getKey());

                        if (! $locked || $locked->status !== 'pending' || $locked->expired_at) {
                            return null;
                        }

                        if (in_array($locked->payment_status, ['paid', 'pending_verification', 'refund_pending'], true)) {
                            return null;
                        }

                        $locked->update([
                            'status' => 'cancelled',
                            'expired_at' => now(),
                        ]);

                        return $locked->fresh(['customer', 'room', 'guestDetail', 'payment']);
                    }, 3);

                    if (! $booking) {
                        continue;
                    }

                    $message = 'Your pending booking expired because it was not confirmed within the reservation window.';
                    $booking->customer?->notify(new BookingAutomationNotification($booking, 'booking_expired', $message));
                    $this->queueMail($booking, new BookingExpiredMail($booking));
                    $expired++;
                }
            }, 'booking_id');

        return $expired;
    }

    private function sendCheckInReminders(): int
    {
        $days = max(0, (int) config('booking_automation.check_in_reminder_days', 1));
        $reminderDate = today()->addDays($days)->toDateString();
        $reminded = 0;

        Booking::query()
            ->where('status', 'confirmed')
            ->whereDate('check_in', $reminderDate)
            ->whereNull('check_in_reminder_sent_at')
            ->select('booking_id')
            ->chunkById(100, function ($bookings) use (&$reminded): void {
                foreach ($bookings as $candidate) {
                    $booking = DB::transaction(function () use ($candidate): ?Booking {
                        $locked = Booking::query()
                            ->with(['customer', 'room', 'guestDetail', 'payment'])
                            ->lockForUpdate()
                            ->find($candidate->getKey());

                        if (! $locked || $locked->status !== 'confirmed' || $locked->check_in_reminder_sent_at) {
                            return null;
                        }

                        $locked->update(['check_in_reminder_sent_at' => now()]);

                        return $locked->fresh(['customer', 'room', 'guestDetail', 'payment']);
                    }, 3);

                    if (! $booking) {
                        continue;
                    }

                    $message = 'Reminder: your stay at The Grand Lion Hotel begins on '.$booking->check_in->format('M d, Y').'.';
                    $booking->customer?->notify(new BookingAutomationNotification($booking, 'check_in_reminder', $message));
                    $this->queueMail($booking, new BookingCheckInReminderMail($booking));
                    $reminded++;
                }
            }, 'booking_id');

        return $reminded;
    }

    private function queueMail(Booking $booking, object $mailable): void
    {
        $email = trim($booking->guestEmail());
        if ($email === '' || $email === '-') {
            return;
        }

        try {
            Mail::to($email)->queue($mailable);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
