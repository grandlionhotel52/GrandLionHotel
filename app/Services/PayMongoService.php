<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayMongoService
{
    public function createCardCheckout(Booking $booking): array
    {
        $secretKey = trim((string) config('services.paymongo.secret_key'));

        if ($secretKey === '') {
            throw new RuntimeException('PayMongo is not configured. Add the PayMongo secret key first.');
        }

        $booking->loadMissing(['room', 'user']);
        $amount = (int) round((float) $booking->total_price * 100);

        if ($amount < 100) {
            throw new RuntimeException('The booking amount is too small for PayMongo checkout.');
        }

        $response = $this->client($secretKey)->post('/v2/checkout_sessions', [
            'data' => [
                'attributes' => [
                    'line_items' => [[
                        'name' => 'Hotel booking #'.$booking->getKey(),
                        'description' => (string) ($booking->room?->name ?? config('app.name')),
                        'amount' => $amount,
                        'currency' => 'PHP',
                        'quantity' => 1,
                    ]],
                    'payment_method_types' => ['card'],
                    'success_url' => route('payments.paymongo.return', $booking),
                    'cancel_url' => route('payments.checkout', $booking),
                    'reference_number' => $this->referenceFor($booking),
                    'send_email_receipt' => true,
                    'metadata' => [
                        'booking_id' => (string) $booking->getKey(),
                    ],
                ],
            ],
        ]);

        if ($response->failed()) {
            report(new RuntimeException('PayMongo checkout creation failed with HTTP '.$response->status()));
            throw new RuntimeException('PayMongo could not start the card checkout. Please try again.');
        }

        $sessionId = trim((string) $response->json('data.id'));
        $checkoutUrl = trim((string) $response->json('data.attributes.checkout_url'));

        if ($sessionId === '' || !str_starts_with($checkoutUrl, 'https://checkout.paymongo.com/')) {
            throw new RuntimeException('PayMongo returned an invalid checkout session. Please try again.');
        }

        return ['id' => $sessionId, 'checkout_url' => $checkoutUrl];
    }

    public function referenceFor(Booking $booking): string
    {
        return 'BOOKING-'.$booking->getKey();
    }

    public function hasValidWebhookSignature(string $payload, string $header): bool
    {
        $secret = trim((string) config('services.paymongo.webhook_secret'));
        if ($secret === '' || $header === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            $parts[$key] = $value;
        }

        $timestamp = $parts['t'] ?? '';
        if (!ctype_digit($timestamp) || abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $signatureKey = str_starts_with((string) config('services.paymongo.secret_key'), 'sk_live_') ? 'li' : 'te';
        $provided = $parts[$signatureKey] ?? '';
        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return $provided !== '' && hash_equals($expected, $provided);
    }

    private function client(string $secretKey): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.paymongo.base_url'), '/'))
            ->withBasicAuth($secretKey, '')
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->retry(2, 250, throw: false);
    }
}
