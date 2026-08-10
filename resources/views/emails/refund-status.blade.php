<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Update</title>
</head>
<body style="margin:0;padding:0;background:#f8f5ef;font-family:Arial,sans-serif;color:#1f2937;">
    @php($booking = $refundRequest->payment->booking)
    <table role="presentation" cellspacing="0" cellpadding="0" width="100%" style="padding:28px 12px;">
        <tr><td align="center">
            <table role="presentation" cellspacing="0" cellpadding="0" width="100%" style="max-width:580px;background:#fff;border:1px solid #e8dece;border-radius:14px;overflow:hidden;">
                <tr><td style="padding:22px 24px;background:#1f2937;color:#fff;">
                    <h1 style="margin:0;font-size:21px;">Refund {{ ucfirst($refundRequest->status) }}</h1>
                </td></tr>
                <tr><td style="padding:22px 24px;">
                    <p style="margin:0 0 12px;">Hello {{ $booking->guestName() }},</p>
                    @if($refundRequest->status === 'approved')
                        <p style="line-height:1.6;">Your refund request for booking <strong>#{{ $booking->id }}</strong> was approved and is awaiting processing.</p>
                    @elseif($refundRequest->status === 'processed')
                        <p style="line-height:1.6;">Your refund for booking <strong>#{{ $booking->id }}</strong> has been completed.</p>
                    @else
                        <p style="line-height:1.6;">Your refund request for booking <strong>#{{ $booking->id }}</strong> was not approved.</p>
                    @endif
                    @if($refundRequest->amount)
                        <p><strong>Amount:</strong> ₱{{ number_format((float) $refundRequest->amount, 2) }}</p>
                    @endif
                    @if($refundRequest->refund_method)
                        <p><strong>Method:</strong> {{ \App\Models\Payment::methodLabel($refundRequest->refund_method) }}</p>
                    @endif
                    @if($refundRequest->transaction_reference)
                        <p><strong>Reference:</strong> {{ $refundRequest->transaction_reference }}</p>
                    @endif
                    @if($refundRequest->rejection_reason)
                        <p><strong>Reason:</strong> {{ $refundRequest->rejection_reason }}</p>
                    @endif
                    <p style="margin:18px 0 0;color:#6b7280;">Contact The Grand Lion Hotel if you need assistance.</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
