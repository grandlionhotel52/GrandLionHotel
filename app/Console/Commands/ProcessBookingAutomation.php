<?php

namespace App\Console\Commands;

use App\Mail\BookingCheckInReminderMail;
use App\Mail\BookingExpiredMail;
use App\Mail\PaymentDueReminderMail;
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
        $paymentExpired = $this->expireUnpaidConfirmedBookings();
        $paymentReminded = $this->sendPaymentDeadlineReminders();
        $noShows = $this->markNoShows();
        $reminded = $this->sendCheckInReminders();

        $this->info("Expired {$expired} pending; cancelled {$paymentExpired} unpaid; sent {$paymentReminded} payment reminders; marked {$noShows} no-shows; sent {$reminded} check-in reminders.");

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

    private function expireUnpaidConfirmedBookings(): int
    {
        $expired = 0;

        Booking::query()
            ->where('status', 'confirmed')
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<=', now())
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

                        if (! $locked
                            || $locked->status !== 'confirmed'
                            || is_null($locked->payment_due_at)
                            || $locked->payment_due_at->isFuture()
                            || in_array($locked->payment_status, ['paid', 'pending_verification', 'refund_pending'], true)) {
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

                    $message = 'Your confirmed booking was automatically cancelled because payment was not completed before the deadline.';
                    $booking->customer?->notify(new BookingAutomationNotification($booking, 'payment_deadline_expired', $message));
                    $this->queueMail($booking, new BookingExpiredMail($booking));
                    $expired++;
                }
            }, 'booking_id');

        return $expired;
    }

    private function sendPaymentDeadlineReminders(): int
    {
        $hours = max(1, (int) config('booking_automation.payment_reminder_hours', 6));
        $reminded = 0;

        Booking::query()
            ->where('status', 'confirmed')
            ->whereNull('payment_reminder_sent_at')
            ->whereBetween('payment_due_at', [now(), now()->addHours($hours)])
            ->whereDoesntHave('payment', static fn ($query) => $query->whereIn('status', ['paid', 'pending_verification', 'refund_pending']))
            ->with(['customer', 'room', 'guestDetail', 'payment'])
            ->each(function (Booking $booking) use (&$reminded): void {
                $booking->update(['payment_reminder_sent_at' => now()]);
                $message = 'Payment for booking #'.$booking->id.' is due '.$booking->payment_due_at->diffForHumans().'. Complete payment to keep the reservation.';
                $booking->customer?->notify(new BookingAutomationNotification($booking, 'payment_due_reminder', $message));
                $this->queueMail($booking, new PaymentDueReminderMail($booking));
                $reminded++;
            });

        return $reminded;
    }

    private function markNoShows(): int
    {
        $hours = max(24, (int) config('booking_automation.no_show_grace_hours', 30));
        $cutoff = now()->subHours($hours)->toDateString();
        $count = 0;

        Booking::query()
            ->where('status', 'confirmed')
            ->whereNull('actual_check_in_at')
            ->whereNull('no_show_at')
            ->whereDate('check_in', '<=', $cutoff)
            ->with(['customer', 'room', 'guestDetail', 'payment'])
            ->each(function (Booking $booking) use (&$count): void {
                $booking->update([
                    'status' => 'cancelled',
                    'no_show_at' => now(),
                    'cancellation_reason' => 'Automatically marked as no-show after the check-in grace period.',
                ]);
                $message = 'Booking #'.$booking->id.' was marked as a no-show because check-in was not recorded within the grace period.';
                $booking->customer?->notify(new BookingAutomationNotification($booking, 'booking_no_show', $message));
                $count++;
            });

        return $count;
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
