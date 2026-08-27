<?php

namespace App\Http\Controllers;

use App\Mail\BookingPaidMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PayMongoCheckoutSession;
use App\Models\RefundRequest;
use App\Services\PaymentService;
use App\Services\PayMongoService;
use App\Services\RefundRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PayMongoWebhookController extends Controller
{
    public function __invoke(Request $request, PayMongoService $payMongo, PaymentService $payments, RefundRequestService $refunds): Response
    {
        $payload = $request->getContent();
        $signature = (string) ($request->header('Paymongo-Signature') ?? $request->header('X-Paymongo-Signature'));

        if (!$payMongo->hasValidWebhookSignature($payload, $signature)) {
            return response('Invalid signature.', 401);
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            return response('Received.', 200);
        }

        // PayMongo wraps live and test webhook details under data.attributes.
        // Keep the legacy fallback so previously recorded fixtures remain compatible.
        $eventType = (string) data_get($event, 'data.attributes.type', data_get($event, 'data.type'));
        if (in_array($eventType, ['payment.refunded', 'payment.refund.update', 'payment.refund.updated'], true)) {
            $resource = data_get($event, 'data.attributes.data', data_get($event, 'data.data'));
            $resourceId = trim((string) data_get($resource, 'id'));
            $status = strtolower(trim((string) data_get($resource, 'attributes.status')));

            if (in_array($eventType, ['payment.refund.update', 'payment.refund.updated'], true)) {
                $refund = RefundRequest::query()->where('provider_refund_id', $resourceId)->first();
                if ($refund && in_array($status, ['pending', 'processing', 'succeeded', 'failed'], true)) {
                    $refund->update([
                        'provider_refund_status' => $status,
                        'status' => $status === 'succeeded' ? RefundRequest::STATUS_PROCESSED : $refund->status,
                        'processed_at' => $status === 'succeeded' ? now() : $refund->processed_at,
                    ]);
                    if ($status === 'succeeded') {
                        $refund->payment()->update(['status' => 'refunded']);
                    }
                }
            } else {
                $payment = Payment::query()->where('provider_payment_id', $resourceId)->first();
                if ($payment) {
                    $payment->update(['status' => 'refunded']);
                    $payment->refundRequests()
                        ->where('status', RefundRequest::STATUS_APPROVED)
                        ->update(['status' => RefundRequest::STATUS_PROCESSED, 'provider_refund_status' => 'succeeded', 'processed_at' => now()]);
                }
            }

            return response('Received.', 200);
        }

        if ($eventType !== 'checkout_session.payment.paid') {
            return response('Received.', 200);
        }

        $session = data_get($event, 'data.attributes.data', data_get($event, 'data.data'));
        $sessionId = trim((string) data_get($session, 'id'));
        $reference = trim((string) data_get($session, 'attributes.reference_number'));
        $paymentData = collect((array) data_get($session, 'attributes.payments', []))
            ->first(fn (array $item): bool => data_get($item, 'attributes.status') === 'paid');

        $checkoutSession = PayMongoCheckoutSession::query()->where('provider_session_id', $sessionId)->first();
        $payment = $checkoutSession
            ? Payment::query()->where('booking_id', $checkoutSession->booking_id)->first()
            : Payment::query()->where('provider_session_id', $sessionId)->first();
        if (!$payment || $reference !== 'BOOKING-'.$payment->booking_id || !$paymentData) {
            return response('Ignored.', 200);
        }

        if ($sessionId === ''
            || !str_starts_with($sessionId, 'cs_')
            || trim((string) $payment->provider_session_id) !== $sessionId
            || !in_array($payment->source, ['paymongo_checkout_pending', 'paymongo_checkout'], true)) {
            report(new \RuntimeException('PayMongo webhook session mismatch for booking '.$payment->booking_id));

            return response('Ignored.', 200);
        }

        $expectedAmount = (int) round((float) $payment->amount * 100);
        $providerAmount = (int) data_get($paymentData, 'attributes.amount');
        if ($providerAmount !== $expectedAmount
            || ($checkoutSession && (int) $checkoutSession->amount_centavos !== $providerAmount)
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
        if (!str_starts_with($providerPaymentId, 'pay_')) {
            report(new \RuntimeException('PayMongo webhook returned an invalid payment ID for session '.$sessionId));

            return response('Ignored.', 200);
        }
        $providerMethod = strtolower(trim((string) data_get($paymentData, 'attributes.source.type')));
        $paymentMethod = match ($providerMethod) {
            Payment::METHOD_GCASH => Payment::METHOD_GCASH,
            Payment::METHOD_PAYMAYA => Payment::METHOD_PAYMAYA,
            Payment::METHOD_QRPH => Payment::METHOD_QRPH,
            default => Payment::METHOD_CREDIT_DEBIT_CARD,
        };
        $paidPayment = $payments->charge($booking, $paymentMethod, [
            'source' => 'paymongo_checkout',
        ]);
        $paidPayment->forceFill([
            'provider_payment_id' => $providerPaymentId ?: null,
            'source' => 'paymongo_checkout',
            'verified_at' => $paidPayment->verified_at ?? now(),
        ])->save();
        $checkoutSession?->update(['status' => 'paid']);

        $booking->refresh();
        if ($booking->status === 'cancelled') {
            $paidPayment->update(['status' => 'refund_pending']);
            $refunds->createPendingForCancellation($booking, $paidPayment, [
                'reason' => 'Payment completed after the reservation was cancelled.',
                'notes' => 'Automatically created because PayMongo confirmed a late payment for a cancelled booking.',
            ]);
        }

        if (!$wasPaid && $booking->status !== 'cancelled') {
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
