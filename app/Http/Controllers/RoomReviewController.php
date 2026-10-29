<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomReviewController extends Controller
{
    public function store(Request $request, Room $room)
    {
        $booking = $room->bookings()->where('customer_id', $request->user('customer')->id)
            ->where('status', 'completed')->orderByDesc('check_out')->orderByDesc('booking_id')->first();
        if (! $booking) {
            return back()->withErrors(['review' => 'You can review this room after completing a stay.']);
        }
        $validated = $this->validateReview($request);
        $booking->roomReview()->updateOrCreate([], [
            'rating' => (int) $validated['rating'], 'comment' => trim($validated['comment']),
        ]);

        return redirect()
            ->to(route('rooms.show', $room).'#guest-reviews')
            ->with('status', 'Thank you. Your room review has been saved.');
    }

    public function storeForBooking(Request $request, Booking $booking)
    {
        abort_unless((int) $request->user()->getKey() === (int) $booking->customer_id, 403);

        if ($booking->status !== 'completed') {
            return redirect()
                ->route('bookings.show', $booking)
                ->withErrors(['review' => 'You can review this room after your stay is completed.']);
        }

        $validated = $this->validateReview($request);

        $booking->roomReview()->updateOrCreate([], [
            'rating' => (int) $validated['rating'], 'comment' => trim($validated['comment']),
        ]);

        return redirect()
            ->to(route('bookings.show', $booking).'#guest-review')
            ->with('status', 'Thank you. Your room review has been saved.');
    }

    private function validateReview(Request $request): array
    {
        return $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:1500'],
        ], [
            'rating.between' => 'Choose a rating from 1 to 5 stars.',
            'comment.required' => 'Please share a comment about this room.',
        ]);
    }
}
