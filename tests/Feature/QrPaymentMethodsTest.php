<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Room;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QrPaymentMethodsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_supported_online_payment_methods(): void
    {
        $user = Customer::factory()->create();
        Storage::fake('public');

        foreach (['instapay'] as $method) {
            $room = Room::factory()->create(['is_available' => true]);
            $booking = Booking::factory()->create([
                'customer_id' => $user->id,
                'room_id' => $room->id,
                'status' => 'confirmed',
            ]);
            $booking->payment()->update(['status' => 'unpaid', 'method' => 'pending', 'paid_at' => null]);

            $customerReference = 'TEST-'.strtoupper($method).'-123456';

            $response = $this->actingAs($user)->post(route('payments.process', $booking), [
                'method' => $method,
                'customer_reference' => $customerReference,
                'payment_proof' => UploadedFile::fake()->create('proof.jpg', 10, 'image/jpeg'),
                'terms_accepted' => '1',
            ]);

            $response->assertRedirect(route('bookings.show', $booking));

            $booking->refresh()->load('payment');
            $this->assertSame('pending_verification', $booking->payment_status);
            $this->assertNotNull($booking->payment);
            $this->assertSame($method, $booking->payment->method);
            $this->assertSame('online_submitted', $booking->payment->source);
            $this->assertSame($customerReference, $booking->payment->customer_reference);
            Storage::disk('public')->assertExists($booking->payment->payment_proof_path);
        }
    }

    public function test_customer_is_redirected_to_paymongo_for_card_payment(): void
    {
        $user = Customer::factory()->create();
        $room = Room::factory()->create(['is_available' => true]);
        $booking = Booking::factory()->create([
            'customer_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'confirmed',
        ]);
        $booking->payment()->update(['status' => 'unpaid', 'method' => 'pending', 'paid_at' => null]);

        config(['services.paymongo.secret_key' => 'sk_test_example']);
        Http::fake([
            'api.paymongo.com/v2/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/test-session'],
                ],
            ]),
        ]);

        $response = $this->actingAs($user)->post(route('payments.process', $booking), [
            'method' => 'credit_debit_card',
        ]);

        $response->assertRedirect('https://checkout.paymongo.com/test-session');

        $booking->refresh()->load('payment');
        $this->assertSame('unpaid', $booking->payment_status);
        $this->assertSame('credit_debit_card', $booking->payment->method);
        $this->assertSame('paymongo_checkout_pending', $booking->payment->source);
        $this->assertSame('cs_test_123', $booking->payment->provider_session_id);
        $this->assertNull($booking->payment->payment_proof_path);

        Http::assertSent(fn ($request): bool =>
            $request->url() === 'https://api.paymongo.com/v2/checkout_sessions'
            && $request['data']['attributes']['payment_method_types'] === ['card', 'gcash', 'qrph']
            && $request['data']['attributes']['reference_number'] === 'BOOKING-'.$booking->id
        );
    }

    public function test_repeated_checkout_click_reuses_the_active_paymongo_session(): void
    {
        $user = Customer::factory()->create();
        $booking = Booking::factory()->create(['customer_id' => $user->id, 'status' => 'confirmed']);
        $booking->payment()->update(['status' => 'unpaid', 'method' => 'pending', 'paid_at' => null]);

        config(['services.paymongo.secret_key' => 'sk_test_example']);
        Http::fake([
            'api.paymongo.com/v2/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_reused',
                    'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/reused'],
                ],
            ]),
        ]);

        $this->actingAs($user)->post(route('payments.process', $booking), [
            'method' => 'credit_debit_card',
        ])->assertRedirect('https://checkout.paymongo.com/reused');
        $this->actingAs($user)->post(route('payments.process', $booking), [
            'method' => 'credit_debit_card',
        ])->assertRedirect('https://checkout.paymongo.com/reused');

        Http::assertSentCount(1);
        $this->assertDatabaseCount('paymongo_checkout_sessions', 1);
    }

    public function test_signed_paymongo_webhook_marks_card_payment_as_paid(): void
    {
        $booking = Booking::factory()->create(['status' => 'confirmed']);
        $booking->payment()->update([
            'amount' => 2500,
            'status' => 'unpaid',
            'method' => 'credit_debit_card',
            'source' => 'paymongo_checkout_pending',
            'provider_session_id' => 'cs_test_paid',
            'paid_at' => null,
        ]);
        config([
            'services.paymongo.secret_key' => 'sk_test_example',
            'services.paymongo.webhook_secret' => 'whsec_test_example',
        ]);

        $payload = json_encode([
            'data' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => 'cs_test_paid',
                    'attributes' => [
                        'reference_number' => 'BOOKING-'.$booking->id,
                        'payments' => [[
                            'id' => 'pay_test_paid',
                            'attributes' => ['status' => 'paid', 'amount' => 250000, 'currency' => 'PHP'],
                        ]],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test_example');

        $this->call('POST', route('webhooks.paymongo'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PAYMONGO_SIGNATURE' => "t={$timestamp},te={$signature},li=",
        ], $payload)->assertOk();

        $booking->refresh()->load('payment');
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('paymongo_checkout', $booking->payment->source);
        $this->assertSame('pay_test_paid', $booking->payment->provider_payment_id);
        $this->assertNotNull($booking->payment->verified_at);
    }

    public function test_paymongo_webhook_rejects_an_invalid_signature(): void
    {
        config(['services.paymongo.webhook_secret' => 'whsec_test_example']);

        $this->withHeader('Paymongo-Signature', 't='.time().',te=invalid,li=')
            ->postJson(route('webhooks.paymongo'), ['data' => []])
            ->assertUnauthorized();
    }

    public function test_paymongo_return_shows_a_clear_confirmation_page_instead_of_payment_form(): void
    {
        $customer = Customer::factory()->create();
        $booking = Booking::factory()->create(['customer_id' => $customer->id, 'status' => 'confirmed']);
        $booking->payment()->update([
            'status' => 'unpaid',
            'method' => 'credit_debit_card',
            'source' => 'paymongo_checkout_pending',
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('payments.paymongo.return', $booking))
            ->assertOk()
            ->assertSee('We are confirming your payment')
            ->assertSee('Please do not pay again or upload proof.')
            ->assertDontSee('Continue to PayMongo');
    }

    public function test_paymongo_confirmation_status_exposes_receipt_details_after_webhook_payment(): void
    {
        $customer = Customer::factory()->create();
        $booking = Booking::factory()->create(['customer_id' => $customer->id, 'status' => 'confirmed']);
        $booking->payment()->update([
            'status' => 'paid',
            'method' => 'credit_debit_card',
            'source' => 'paymongo_checkout',
            'transaction_reference' => 'GLH-TEST-PAID',
            'provider_payment_id' => 'pay_test_paid',
            'paid_at' => now(),
        ]);

        $this->actingAs($customer, 'customer')
            ->getJson(route('payments.paymongo.status', $booking))
            ->assertOk()
            ->assertJson([
                'paid' => true,
                'payment_status' => 'paid',
                'transaction_reference' => 'GLH-TEST-PAID',
                'provider_payment_id' => 'pay_test_paid',
            ]);

        $this->get(route('payments.paymongo.return', $booking))
            ->assertOk()
            ->assertSee('Your payment was successful')
            ->assertSee('GLH-TEST-PAID')
            ->assertSee('Download receipt');
    }

    public function test_online_payment_requires_reference_and_proof(): void
    {
        $user = Customer::factory()->create();
        $room = Room::factory()->create(['is_available' => true]);
        $booking = Booking::factory()->create([
            'customer_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'confirmed',
        ]);
        $booking->payment()->update(['status' => 'unpaid', 'method' => 'pending', 'paid_at' => null]);

        $response = $this->actingAs($user)->post(route('payments.process', $booking), [
            'method' => 'instapay',
        ]);

        $response->assertSessionHasErrors(['customer_reference', 'payment_proof', 'terms_accepted']);

        $booking->refresh();
        $this->assertSame('unpaid', $booking->payment_status);
    }

    public function test_payment_service_does_not_downgrade_completed_booking_status(): void
    {
        $booking = Booking::factory()->create([
            'status' => 'completed',
        ]);
        $booking->payment()->update(['status' => 'unpaid', 'method' => 'pending', 'paid_at' => null]);

        app(PaymentService::class)->charge($booking, 'cash');

        $booking->refresh();
        $this->assertSame('completed', $booking->status);
        $this->assertSame('paid', $booking->payment_status);
    }

    public function test_payment_service_is_idempotent_for_the_same_booking(): void
    {
        $booking = Booking::factory()->create([
            'status' => 'confirmed',
        ]);
        $booking->payment()->update(['status' => 'unpaid', 'method' => 'pending', 'paid_at' => null]);

        $service = app(PaymentService::class);

        $firstPayment = $service->charge($booking, 'cash');
        $secondPayment = $service->charge($booking, 'gcash', [
            'qr_reference' => 'DUPLICATE-CALL-TEST',
        ]);

        $booking->refresh();

        $this->assertSame($firstPayment->id, $secondPayment->id);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertDatabaseCount('payments', 1);
    }
}
