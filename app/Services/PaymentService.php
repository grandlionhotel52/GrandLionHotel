<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function charge(Booking $booking, string $method = 'cash', array $meta = []): Payment
    {
        return DB::transaction(function () use ($booking, $method, $meta): Payment {
            $lockedBooking = Booking::query()
                ->whereKey($booking->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $bill = $lockedBooking->bill()->firstOrCreate([], ['total_amount' => $lockedBooking->total_price]);
            $bill->refresh();
            $providerId = trim((string) ($meta['provider_payment_id'] ?? ''));
            if ($providerId !== '') {
                $existing = Payment::query()->where('provider_payment_id', $providerId)->where('status', 'paid')->first();
                if ($existing) {
                    if ((int) $existing->booking_id !== (int) $lockedBooking->id) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['payment' => 'This provider payment belongs to another booking.']);
                    }
                    return $existing;
                }
            }
            $outstandingAmount = $bill->outstandingAmount();
            if ($outstandingAmount <= 0.009) {
                $paid = $lockedBooking->payments()->where('status', 'paid')->latest('payment_id')->firstOrFail();
                if ($providerId !== '' && $paid->provider_payment_id !== $providerId) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['payment' => 'This bill is already settled. An additional provider payment needs reconciliation.']);
                }
                $paid->ensureTransactionReference((int) $lockedBooking->id);
                return $paid->fresh();
            }
            $requestedAmount = $meta['amount'] ?? $outstandingAmount;
            $chargeAmount = is_numeric($requestedAmount)
                ? round(min($outstandingAmount, (float) $requestedAmount), 2)
                : $outstandingAmount;
            if ($chargeAmount <= 0) {
                throw \Illuminate\Validation\ValidationException::withMessages(['payment' => 'Payment must be greater than zero.']);
            }
            $billUpdates = array_intersect_key($meta, array_flip(['original_amount', 'discount_rate', 'discount_amount']));
            if ($billUpdates !== []) {
                $bill->update($billUpdates);
            }
            $pending = $lockedBooking->payments()->whereIn('status', ['unpaid', 'pending_verification']);
            if (! empty($meta['provider_session_id'])) {
                $pending->where('provider_session_id', $meta['provider_session_id']);
            }
            $payment = $pending->latest('payment_id')->lockForUpdate()->first();
            $payload = [
                'amount' => $chargeAmount,
                'method' => $method,
                'status' => 'paid',
                'paid_at' => now(),
                'source' => $meta['source'] ?? (Payment::isOnlineMethod($method) ? 'online' : 'manual'),
                'transaction_reference' => $this->generateUniqueTransactionReference((int) $lockedBooking->id),
            ];
            foreach (['provider_payment_id', 'provider_session_id', 'qr_reference'] as $field) {
                if (isset($meta[$field])) {
                    $payload[$field] = $meta[$field];
                }
            }
            if ($payment) {
                $payment->update($payload);
            } else {
                $payment = $lockedBooking->createPayment($payload);
            }
            if ($bill->outstandingAmount() <= 0.009) {
                $lockedBooking->payments()->where('payment_id', '!=', $payment->id)
                    ->whereIn('status', ['unpaid', 'pending_verification'])->update(['status' => 'cancelled']);
            }

            $lockedBooking->update([
                'payment_due_at' => null,
            ]);

            return $payment->fresh();
        }, 3);
    }

    private function generateUniqueTransactionReference(int $bookingId, ?int $exceptPaymentId = null): string
    {
        do {
            $candidate = Payment::generateTransactionReference($bookingId);

            $query = Payment::query()->where('transaction_reference', $candidate);
            if (!is_null($exceptPaymentId)) {
                $query->where('payment_id', '!=', $exceptPaymentId);
            }
        } while ($query->exists());

        return $candidate;
    }
}
