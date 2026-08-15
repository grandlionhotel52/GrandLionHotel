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
            'RefundRequest' => $this->refundMessage($bookingNumber),
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
            'cancelled' => "Booking {$bookingNumber} was cancelled. Open it to see the cancellation or refund details.",
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
            'refund_pending' => "Refund for booking {$bookingNumber} is waiting for admin approval.",
            'refunded' => "Refund completed for booking {$bookingNumber}. Please check your original payment account.",
            'failed' => "Payment for booking {$bookingNumber} failed. Please try again or choose another payment method.",
            default => "Booking {$bookingNumber} still needs payment. Open the booking to continue.",
        };
    }

    private function refundMessage(string $bookingNumber): string
    {
        $status = strtolower((string) $this->subject->getAttribute('status'));

        return match ($status) {
            'pending' => "Refund requested for booking {$bookingNumber}. Please wait for admin review.",
            'approved' => "Refund approved for booking {$bookingNumber}. The money is being returned.",
            'processed' => "Refund completed for booking {$bookingNumber}. Please check your original payment account.",
            'rejected' => "Refund request for booking {$bookingNumber} was not approved. Open the booking for details.",
            default => "Refund status changed for booking {$bookingNumber}. Open the booking for details.",
        };
    }
}
