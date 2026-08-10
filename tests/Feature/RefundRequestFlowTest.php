<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\RefundRequest;
use App\Models\Room;
use App\Models\Staff;
use App\Mail\RefundStatusMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RefundRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_customer_paid_cancellation_creates_pending_refund_request(): void
    {
        $customer = Customer::factory()->create();
        $booking = $this->createPaidBooking($customer);
        $refundReason = 'Change of travel plans due to a family emergency.';

        $response = $this->actingAs($customer, 'customer')->patch(route('bookings.cancel', $booking), [
            'cancellation_reason' => $refundReason,
            'cancellation_confirmation' => 'CANCEL',
        ]);

        $response->assertRedirect(route('bookings.show', $booking));

        $payment = $booking->fresh()->payment()->firstOrFail();

        $this->assertDatabaseHas('refund_requests', [
            'payment_id' => $payment->payment_id,
            'reason' => $refundReason,
            'status' => 'pending',
        ]);

        $refundRequest = RefundRequest::query()->where('payment_id', $payment->payment_id)->firstOrFail();
        $this->assertStringContainsString('original payment method: Cash', (string) $refundRequest->notes);
    }

    public function test_customer_paid_cancellation_requires_refund_reason(): void
    {
        $customer = Customer::factory()->create();
        $booking = $this->createPaidBooking($customer);

        $response = $this->actingAs($customer, 'customer')
            ->from(route('bookings.show', $booking))
            ->patch(route('bookings.cancel', $booking));

        $response->assertRedirect(route('bookings.show', $booking));
        $response->assertSessionHasErrors('cancellation_reason');

        $booking->refresh();

        $this->assertSame('confirmed', $booking->status);
        $this->assertDatabaseCount('refund_requests', 0);
    }

    public function test_admin_paid_cancellation_creates_pending_refund_request(): void
    {
        $admin = Admin::factory()->create();
        $customer = Customer::factory()->create();
        $booking = $this->createPaidBooking($customer);

        $response = $this->actingAs($admin, 'admin')->patch(
            route('admin.bookings.update-status', $booking),
            ['status' => 'cancelled']
        );

        $response->assertRedirect(route('admin.bookings.show', $booking));

        $payment = $booking->fresh()->payment()->firstOrFail();

        $this->assertDatabaseHas('refund_requests', [
            'payment_id' => $payment->payment_id,
            'status' => 'pending',
        ]);

        $refundRequest = RefundRequest::query()->where('payment_id', $payment->payment_id)->firstOrFail();
        $this->assertStringContainsString('original payment method: Cash', (string) $refundRequest->notes);
    }

    public function test_staff_paid_cancellation_creates_pending_refund_request(): void
    {
        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $booking = $this->createPaidBooking($customer);

        $response = $this->actingAs($staff, 'staff')->patch(route('staff.bookings.cancel', $booking));

        $response->assertRedirect(route('staff.bookings.show', $booking));

        $payment = $booking->fresh()->payment()->firstOrFail();

        $this->assertDatabaseHas('refund_requests', [
            'payment_id' => $payment->payment_id,
            'status' => 'pending',
        ]);

        $refundRequest = RefundRequest::query()->where('payment_id', $payment->payment_id)->firstOrFail();
        $this->assertStringContainsString('original payment method: Cash', (string) $refundRequest->notes);
    }

    public function test_admin_can_approve_and_complete_refund(): void
    {
        $admin = Admin::factory()->create();
        $customer = Customer::factory()->create();
        $booking = $this->createPaidBooking($customer);
        $payment = $booking->payment;
        $payment->update(['status' => 'refund_pending']);
        $refund = RefundRequest::create([
            'payment_id' => $payment->id,
            'reason' => 'Customer cancellation.',
            'status' => RefundRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);
        $refundAmount = (float) $payment->amount;

        $this->actingAs($admin, 'admin')->patch(route('admin.refunds.approve', $refund), [
            'amount' => $refundAmount,
            'refund_method' => 'cash',
            'notes' => 'Approved after payment review.',
        ])->assertRedirect(route('admin.refunds.show', $refund));

        $refund->refresh();
        $this->assertSame(RefundRequest::STATUS_APPROVED, $refund->status);
        $this->assertSame(number_format($refundAmount, 2, '.', ''), $refund->amount);
        $this->assertSame($admin->id, $refund->handled_by_admin_id);

        $this->actingAs($admin, 'admin')->patch(route('admin.refunds.process', $refund), [
            'transaction_reference' => 'RF-2026-0001',
        ])->assertRedirect(route('admin.refunds.show', $refund));

        $refund->refresh();
        $this->assertSame(RefundRequest::STATUS_PROCESSED, $refund->status);
        $this->assertSame('RF-2026-0001', $refund->transaction_reference);
        $this->assertSame('refunded', $payment->fresh()->status);
        Mail::assertQueued(RefundStatusMail::class, 2);
    }

    public function test_admin_can_return_a_paymongo_payment_to_the_original_method(): void
    {
        $admin = Admin::factory()->create();
        $booking = $this->createPaidBooking(Customer::factory()->create());
        $booking->payment->update([
            'status' => 'refund_pending',
            'provider_payment_id' => 'pay_test_original',
        ]);
        $refund = RefundRequest::create([
            'payment_id' => $booking->payment->id,
            'status' => RefundRequest::STATUS_APPROVED,
            'amount' => $booking->payment->amount,
            'refund_method' => 'credit_debit_card',
            'requested_at' => now(),
            'approved_at' => now(),
        ]);

        config(['services.paymongo.secret_key' => 'sk_test_example']);
        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response([
                'data' => ['id' => 'ref_test_123', 'attributes' => ['status' => 'succeeded']],
            ]),
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.refunds.process', $refund), ['notes' => 'Approved cancellation refund.'])
            ->assertRedirect(route('admin.refunds.show', $refund));

        $refund->refresh();
        $this->assertSame(RefundRequest::STATUS_PROCESSED, $refund->status);
        $this->assertSame('ref_test_123', $refund->provider_refund_id);
        $this->assertSame('succeeded', $refund->provider_refund_status);
        $this->assertSame('refunded', $booking->payment->fresh()->status);
        Http::assertSent(fn ($request): bool =>
            $request->url() === 'https://api.paymongo.com/v1/refunds'
            && $request['data']['attributes']['payment_id'] === 'pay_test_original'
        );
    }

    public function test_admin_can_reject_refund_and_payment_returns_to_paid(): void
    {
        $admin = Admin::factory()->create();
        $booking = $this->createPaidBooking(Customer::factory()->create());
        $booking->payment->update(['status' => 'refund_pending']);
        $refund = RefundRequest::create([
            'payment_id' => $booking->payment->id,
            'reason' => 'Requested refund.',
            'status' => RefundRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')->patch(route('admin.refunds.reject', $refund), [
            'rejection_reason' => 'The booking falls outside the refundable period.',
        ])->assertRedirect(route('admin.refunds.show', $refund));

        $this->assertSame(RefundRequest::STATUS_REJECTED, $refund->fresh()->status);
        $this->assertSame('paid', $booking->payment->fresh()->status);
        Mail::assertQueued(RefundStatusMail::class);
    }

    public function test_refund_cannot_exceed_original_payment(): void
    {
        $admin = Admin::factory()->create();
        $booking = $this->createPaidBooking(Customer::factory()->create());
        $booking->payment->update(['status' => 'refund_pending']);
        $refund = RefundRequest::create([
            'payment_id' => $booking->payment->id,
            'status' => RefundRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.refunds.show', $refund))
            ->patch(route('admin.refunds.approve', $refund), [
                'amount' => (float) $booking->payment->amount + 1,
                'refund_method' => 'cash',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(RefundRequest::STATUS_PENDING, $refund->fresh()->status);
    }

    private function createPaidBooking(Customer $customer): Booking
    {
        $room = Room::factory()->create();

        $booking = Booking::factory()->create([
            'customer_id' => $customer->customer_id,
            'room_id' => $room->room_id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'actual_check_in_at' => null,
            'actual_check_out_at' => null,
        ]);

        $booking->payment()->update([
            'amount' => (float) $booking->total_price,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return $booking->fresh(['payment']);
    }
}
