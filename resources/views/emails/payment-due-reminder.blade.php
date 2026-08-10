<!DOCTYPE html>
<html><body style="font-family:Arial,sans-serif;color:#1f2937;background:#f8f5ef;padding:24px">
<div style="max-width:580px;margin:auto;background:#fff;padding:24px;border-radius:14px">
    <h1 style="font-size:21px">Payment deadline reminder</h1>
    <p>Hello {{ $booking->guestName() }},</p>
    <p>Payment for booking <strong>#{{ $booking->id }}</strong> is due on <strong>{{ $booking->payment_due_at?->format('M d, Y h:i A') }}</strong>.</p>
    <p>The reservation will be cancelled automatically if payment is not completed before this deadline.</p>
    <a href="{{ route('bookings.show', $booking) }}" style="display:inline-block;padding:10px 16px;background:#b89254;color:#fff;text-decoration:none;border-radius:10px">View booking</a>
</div></body></html>
