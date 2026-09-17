<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

class BookingActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Booking $booking,
        private readonly Model $subject,
        private readonly string $event
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->getKey(),
            'booking_status' => $this->booking->status,
            'payment_status' => $this->booking->payment_status,
            'event' => $this->event,
            'subject' => class_basename($this->subject),
            'message' => $this->message(),
        ];
    }

    private function message(): string
    {
        $bookingNumber = '#'.$this->booking->getKey();

        return match (class_basename($this->subject)) {
            'Payment' => $this->paymentMessage($bookingNumber),
            default => $this->bookingMessage($bookingNumber),
        };
    }

    private function bookingMessage(string $bookingNumber): string
    {
        if ($this->event === 'created') {
            return "Booking {$bookingNumber} was submitted. Please wait for hotel confirmation.";
        }

        return match ($this->booking->status) {
            'confirmed' => "Booking {$bookingNumber} is confirmed. Please complete payment before the deadline shown in your booking.",
            'cancelled' => "Booking {$bookingNumber} was cancelled. Open it to see the cancellation details.",
            'completed' => "Booking {$bookingNumber} is complete. Thank you for staying with us.",
            default => "Booking {$bookingNumber} is being reviewed by the hotel.",
        };
    }

    private function paymentMessage(string $bookingNumber): string
    {
        $status = strtolower((string) $this->subject->getAttribute('status'));

        return match ($status) {
            'paid' => "Payment received for booking {$bookingNumber}. Your receipt is ready.",
            'pending_verification' => "Payment proof received for booking {$bookingNumber}. The hotel is checking it now.",
            'failed' => "Payment for booking {$bookingNumber} failed. Please try again or choose another payment method.",
            default => "Booking {$bookingNumber} still needs payment. Open the booking to continue.",
        };
    }

}
