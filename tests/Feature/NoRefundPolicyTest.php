<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Room;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NoRefundPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_refund_storage_is_not_created(): void
    {
        $this->assertFalse(Schema::hasTable('refund_requests'));
    }

    public function test_customer_cancellation_keeps_collected_payment_paid(): void
    {
        $customer = Customer::factory()->create();
        $booking = $this->createPaidBooking($customer);

        $this->actingAs($customer, 'customer')->patch(route('bookings.cancel', $booking), [
            'cancellation_reason' => 'Travel plans changed.',
            'cancellation_confirmation' => 'CANCEL',
        ])->assertRedirect(route('bookings.show', $booking));

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('paid', $booking->payment->fresh()->status);
    }

    public function test_admin_and_staff_cancellations_keep_collected_payments_paid(): void
    {
        $adminBooking = $this->createPaidBooking(Customer::factory()->create());
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')->patch(
            route('admin.bookings.update-status', $adminBooking),
            ['status' => 'cancelled']
        )->assertRedirect(route('admin.bookings.show', $adminBooking));

        $staffBooking = $this->createPaidBooking(Customer::factory()->create());
        $staff = Staff::factory()->create();

        $this->actingAs($staff, 'staff')
            ->patch(route('staff.bookings.cancel', $staffBooking))
            ->assertRedirect(route('staff.bookings.show', $staffBooking));

        $this->assertSame('paid', $adminBooking->payment->fresh()->status);
        $this->assertSame('paid', $staffBooking->payment->fresh()->status);
    }

    private function createPaidBooking(Customer $customer): Booking
    {
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
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
