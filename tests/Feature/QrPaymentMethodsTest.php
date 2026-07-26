<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Room;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QrPaymentMethodsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_supported_online_payment_methods(): void
    {
        $user = Customer::factory()->create();
        Storage::fake('public');

        foreach (['instapay', 'credit_debit_card'] as $method) {
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

        $response->assertSessionHasErrors(['customer_reference', 'payment_proof']);

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
