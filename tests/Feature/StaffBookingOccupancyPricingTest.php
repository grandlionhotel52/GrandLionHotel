<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomStatus;
use App\Models\Staff;
use App\Notifications\ExtraBeddingResponseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StaffBookingOccupancyPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['pricing.extra_bedding_fee_per_night' => 500]);
    }

    public function test_staff_recalculates_unpaid_booking_amount_when_occupancy_changes(): void
    {
        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $room = $this->createRoom([
            'price_per_night' => 2000,
        ]);

        $booking = $this->createBooking($customer, $room, $staff);

        $booking->payment()->create([
            'amount' => 4000,
            'method' => 'pending',
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($staff, 'staff')->patch(route('staff.bookings.occupancy', $booking), [
            'adults' => 3,
            'kids' => 0,
            'extra_bedding_confirmed' => 1,
        ]);

        $response->assertRedirect(route('staff.bookings.show', $booking));

        $booking->refresh()->load('payment', 'guestDetail');

        $this->assertSame(3, $booking->guests);
        $this->assertSame('5223.21', number_format((float) $booking->payment->amount, 2, '.', ''));
        $this->assertSame(3, (int) $booking->guestDetail->adults);
    }

    public function test_staff_blocks_paid_booking_occupancy_changes_that_would_change_total(): void
    {
        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $room = $this->createRoom([
            'price_per_night' => 2000,
        ]);

        $booking = $this->createBooking($customer, $room, $staff);

        $booking->payment()->create([
            'amount' => 4000,
            'method' => Payment::METHOD_CASH,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($staff, 'staff')
            ->from(route('staff.bookings.show', $booking))
            ->patch(route('staff.bookings.occupancy', $booking), [
                'adults' => 3,
                'kids' => 0,
                'extra_bedding_confirmed' => 1,
            ]);

        $response->assertRedirect(route('staff.bookings.show', $booking));
        $response->assertSessionHasErrors('occupancy');

        $booking->refresh()->load('payment', 'guestDetail');

        $this->assertSame(2, $booking->guests);
        $this->assertSame('4000.00', number_format((float) $booking->payment->amount, 2, '.', ''));
        $this->assertSame(2, (int) $booking->guestDetail->adults);
    }

    public function test_customer_and_staff_can_coordinate_an_extra_bedding_request(): void
    {
        Notification::fake();

        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $room = $this->createRoom();
        $booking = $this->createBooking($customer, $room, $staff);

        $this->actingAs($customer, 'customer')
            ->post(route('bookings.request-extra-bedding', $booking), [
                'requested_count' => 1,
                'customer_message' => 'Please prepare the extra bed before our afternoon arrival.',
            ])
            ->assertRedirect(route('bookings.show', $booking))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('booking_extra_bedding_requests', [
            'booking_id' => $booking->id,
            'requested_count' => 1,
            'status' => 'pending',
            'customer_message' => 'Please prepare the extra bed before our afternoon arrival.',
        ]);

        $this->actingAs($staff, 'staff')
            ->get(route('staff.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Please prepare the extra bed before our afternoon arrival.');

        $this->actingAs($staff, 'staff')
            ->get(route('staff.bookings.index'))
            ->assertOk()
            ->assertSee('Bedding request')
            ->assertSee('New extra bedding message');

        $this->actingAs($staff, 'staff')
            ->patch(route('staff.bookings.extra-bedding-response', $booking), [
                'extra_bedding_status' => 'approved',
                'approved_count' => 1,
                'staff_response' => 'Approved. The extra bed will be ready before check-in.',
            ])
            ->assertRedirect(route('staff.bookings.show', $booking));

        $this->assertDatabaseHas('booking_extra_bedding_requests', [
            'booking_id' => $booking->id,
            'status' => 'approved',
            'approved_count' => 1,
            'staff_response' => 'Approved. The extra bed will be ready before check-in.',
            'responded_by_staff_id' => $staff->id,
        ]);

        $booking->refresh()->load(['payment', 'extraBeddingRequest']);
        $this->assertSame(1, $booking->extra_bedding_count);
        $this->assertSame(
            number_format((float) $booking->billingQuote()['total'], 2, '.', ''),
            number_format((float) $booking->payment->amount, 2, '.', '')
        );

        Notification::assertSentTo($customer, ExtraBeddingResponseNotification::class);

        $this->actingAs($customer, 'customer')
            ->get(route('bookings.show', $booking))
            ->assertOk()
            ->assertSee('Approved')
            ->assertSee('The extra bed will be ready before check-in.');
    }

    public function test_approved_extra_bedding_becomes_a_balance_due_after_existing_payment(): void
    {
        Notification::fake();

        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $room = $this->createRoom(['price_per_night' => 2000]);
        $booking = $this->createBooking($customer, $room, $staff);
        $originalAmount = (float) $booking->billingQuote()['total'];

        $booking->payment()->create([
            'amount' => $originalAmount,
            'balance_due' => 0,
            'method' => Payment::METHOD_CASH,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->actingAs($customer, 'customer')->post(route('bookings.request-extra-bedding', $booking), [
            'requested_count' => 1,
            'customer_message' => 'Please add one extra bed.',
        ])->assertSessionHasNoErrors();

        $this->actingAs($staff, 'staff')->patch(route('staff.bookings.extra-bedding-response', $booking), [
            'extra_bedding_status' => 'approved',
            'approved_count' => 1,
            'staff_response' => 'Approved and added to your payment balance.',
        ])->assertSessionHasNoErrors();

        $payment = $booking->fresh()->payment;

        $this->assertSame('unpaid', $payment->status);
        $this->assertGreaterThan($originalAmount, (float) $payment->amount);
        $this->assertSame(
            number_format((float) $payment->amount - $originalAmount, 2, '.', ''),
            number_format((float) $payment->balance_due, 2, '.', '')
        );
    }

    private function createRoom(array $attributes = []): Room
    {
        $cleanStatusId = (int) RoomStatus::query()->where('slug', 'clean')->value('room_status_id');

        return Room::factory()->create(array_merge([
            'room_status_id' => $cleanStatusId,
        ], $attributes));
    }

    private function createBooking(Customer $customer, Room $room, Staff $staff): Booking
    {
        $booking = Booking::query()->create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
            'status' => 'confirmed',
            'notes' => 'Occupancy pricing test booking',
            'staff_id' => $staff->id,
        ]);

        $booking->guestDetail()->create([
            'first_name' => 'Occupancy',
            'last_name' => 'Guest',
            'email' => $customer->email,
            'phone' => $customer->phone,
            'adults' => 2,
            'kids' => 0,
        ]);

        return $booking;
    }
}
