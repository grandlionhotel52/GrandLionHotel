<?php

namespace App\Http\Controllers;

use App\Mail\BookingPaidMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PayMongoWebhookController extends Controller
{
    public function __invoke(Request $request, PayMongoService $payMongo, PaymentService $payments): Response
    {
        $payload = $request->getContent();
        $signature = (string) ($request->header('Paymongo-Signature') ?? $request->header('X-Paymongo-Signature'));

        if (!$payMongo->hasValidWebhookSignature($payload, $signature)) {
            return response('Invalid signature.', 401);
        }

        $event = json_decode($payload, true);
        if (!is_array($event) || data_get($event, 'data.type') !== 'checkout_session.payment.paid') {
            return response('Received.', 200);
        }

        $session = data_get($event, 'data.data');
        $sessionId = trim((string) data_get($session, 'id'));
        $reference = trim((string) data_get($session, 'attributes.reference_number'));
        $paymentData = collect((array) data_get($session, 'attributes.payments', []))
            ->first(fn (array $item): bool => data_get($item, 'attributes.status') === 'paid');

        $payment = Payment::query()->where('provider_session_id', $sessionId)->first();
        if (!$payment || $reference !== 'BOOKING-'.$payment->booking_id || !$paymentData) {
            return response('Ignored.', 200);
        }

        $expectedAmount = (int) round((float) $payment->amount * 100);
        if ((int) data_get($paymentData, 'attributes.amount') !== $expectedAmount
            || strtoupper((string) data_get($paymentData, 'attributes.currency')) !== 'PHP') {
            report(new \RuntimeException('PayMongo webhook amount mismatch for session '.$sessionId));
            return response('Ignored.', 200);
        }

        $wasPaid = $payment->status === 'paid';
        $booking = Booking::query()->find($payment->booking_id);
        if (!$booking) {
            return response('Ignored.', 200);
        }

        $providerPaymentId = trim((string) data_get($paymentData, 'id'));
        $paidPayment = $payments->charge($booking, Payment::METHOD_CREDIT_DEBIT_CARD, [
            'source' => 'paymongo_checkout',
        ]);
        $paidPayment->forceFill(['provider_payment_id' => $providerPaymentId ?: null])->save();

        if (!$wasPaid) {
            $booking->loadMissing(['user', 'room', 'payment']);
            try {
                Mail::to($booking->user->email)->queue(new BookingPaidMail($booking));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return response('Received.', 200);
    }
}
