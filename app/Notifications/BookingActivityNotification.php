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
        return match (class_basename($this->subject)) {
            'Payment' => 'Payment information for your booking was updated.',
            'RefundRequest' => 'Your booking refund request was updated.',
            default => 'Your booking status was updated.',
        };
    }
}
