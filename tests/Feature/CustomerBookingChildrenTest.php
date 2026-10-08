<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Room;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBookingChildrenTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_add_children_and_staff_can_see_the_breakdown(): void
    {
        config(['pricing.extra_bedding_fee_per_night' => 500]);

        $customer = Customer::factory()->create([
            'phone' => '09170000001',
            'address_line' => 'Family Street',
            'city' => 'Manila',
            'province' => 'Metro Manila (NCR)',
        ]);
        $room = Room::factory()->create([
            'is_available' => true,
            'price_per_night' => 2000,
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('bookings.create', $room))
            ->assertOk()
            ->assertSee('Children')
            ->assertSee('name="kids"', false);

        $response = $this->actingAs($customer, 'customer')->post(route('bookings.store'), [
            'room_id' => $room->id,
            'check_in' => today()->addDay()->toDateString(),
            'check_out' => today()->addDays(3)->toDateString(),
            'adults' => 2,
            'kids' => 1,
            'meal_plan' => 'room_only',
            'discount_type' => 'none',
        ]);

        $booking = Booking::query()->firstOrFail()->load(['guestDetail', 'payment']);

        $response->assertRedirect(route('payments.checkout', $booking));
        $this->assertSame(2, $booking->guestDetail->adults);
        $this->assertSame(1, $booking->guestDetail->kids);
        $this->assertSame(3, $booking->guests);
        $this->assertSame(1, $booking->extra_bedding_count);
        $this->assertGreaterThan(4000, (float) $booking->payment->amount);

        $booking->update(['status' => 'pending']);
        $staff = Staff::factory()->create();

        $this->actingAs($staff, 'staff')
            ->get(route('staff.bookings.show', $booking))
            ->assertOk()
            ->assertSeeInOrder(['Adults', '2', 'Children', '1']);

        $this->actingAs($staff, 'staff')
            ->get(route('staff.bookings.index'))
            ->assertOk()
            ->assertSee('2 adults, 1 child');

        $this->actingAs($staff, 'staff')
            ->get(route('staff.arrivals', ['date' => $booking->check_in->toDateString()]))
            ->assertOk()
            ->assertSee('2 adults, 1 child');
    }

    public function test_customer_cannot_exceed_the_supported_total_occupancy(): void
    {
        config(['pricing.max_extra_bedding_per_booking' => 5]);

        $customer = Customer::factory()->create([
            'phone' => '09170000002',
            'address_line' => 'Family Street',
            'city' => 'Manila',
            'province' => 'Metro Manila (NCR)',
        ]);
        $room = Room::factory()->create(['is_available' => true]);

        $this->actingAs($customer, 'customer')->post(route('bookings.store'), [
            'room_id' => $room->id,
            'check_in' => today()->addDay()->toDateString(),
            'check_out' => today()->addDays(2)->toDateString(),
            'adults' => 3,
            'kids' => 5,
            'meal_plan' => 'room_only',
            'discount_type' => 'none',
        ])->assertSessionHasErrors('kids');

        $this->assertDatabaseCount('bookings', 0);
    }
}
