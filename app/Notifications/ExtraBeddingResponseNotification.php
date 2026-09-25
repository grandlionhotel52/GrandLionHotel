<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\BookingExtraBeddingRequest;
use Illuminate\Notifications\Notification;

class ExtraBeddingResponseNotification extends Notification
{
    public function __construct(
        private readonly Booking $booking,
        private readonly BookingExtraBeddingRequest $beddingRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $status = ucfirst(str_replace('_', ' ', $this->beddingRequest->status));

        return [
            'booking_id' => $this->booking->getKey(),
            'booking_status' => $this->booking->status,
            'payment_status' => $this->booking->payment_status,
            'event' => 'extra_bedding_response',
            'subject' => 'ExtraBeddingRequest',
            'message' => "Your extra bedding request was updated: {$status}.",
        ];
    }
}
