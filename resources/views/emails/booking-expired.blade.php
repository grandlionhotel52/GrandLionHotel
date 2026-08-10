<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Expired</title>
</head>
<body style="margin:0;padding:0;background:#f8f5ef;font-family:Arial,sans-serif;color:#1f2937;">
    <table role="presentation" cellspacing="0" cellpadding="0" width="100%" style="padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" width="100%" style="max-width:580px;background:#ffffff;border:1px solid #e8dece;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 24px;background:#7f1d1d;color:#ffffff;">
                            <h1 style="margin:0;font-size:21px;">Reservation window expired</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 24px;">
                            <p style="margin:0 0 12px;">Hello {{ $booking->guestName() }},</p>
                            <p style="margin:0 0 16px;line-height:1.6;">
                                Booking <strong>#{{ $booking->id }}</strong> for {{ $booking->room->name ?? 'your selected room' }}
                                was automatically cancelled because it remained pending beyond the reservation window.
                            </p>
                            <p style="margin:0 0 18px;line-height:1.6;">The room has been released so it can be reserved again. You are welcome to create a new booking.</p>
                            <a href="{{ route('rooms.index') }}" style="display:inline-block;padding:10px 16px;background:#b89254;color:#ffffff;text-decoration:none;border-radius:10px;font-weight:700;">Browse rooms</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
