<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Room;
use App\Models\RoomStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingProfileRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_incomplete_customer_returns_to_booking_after_completing_profile(): void
    {
        $status = RoomStatus::query()->firstOrCreate(['slug' => 'clean'], [
            'name' => 'Clean',
        ]);

        $room = Room::factory()->create([
            'room_status_id' => $status->room_status_id,
        ]);

        $customer = Customer::factory()->create([
            'address_line' => null,
            'city' => null,
            'province' => null,
        ]);

        $bookingUrl = route('bookings.create', [
            'room' => $room,
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-12',
        ]);
        $bookingPath = parse_url($bookingUrl, PHP_URL_PATH).'?'.parse_url($bookingUrl, PHP_URL_QUERY);

        $this->actingAs($customer, 'customer')
            ->get($bookingUrl)
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('profile.return_to', $bookingPath);

        $this->actingAs($customer, 'customer')
            ->put(route('profile.update'), [
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'phone' => '09171234567',
                'address_line' => '123 Mabini Street',
                'city' => 'Manila',
                'province' => 'Abra',
                'country' => 'Philippines',
            ])
            ->assertRedirect($bookingUrl);

        $this->assertDatabaseHas('customers', [
            'customer_id' => $customer->customer_id,
            'name' => 'Juan Dela Cruz',
            'city' => 'Manila',
            'province' => 'Abra',
        ]);
    }

    public function test_incomplete_customer_can_view_room_details(): void
    {
        $status = RoomStatus::query()->firstOrCreate(['slug' => 'clean'], [
            'name' => 'Clean',
        ]);

        $room = Room::factory()->create([
            'room_status_id' => $status->room_status_id,
        ]);

        $customer = Customer::factory()->create([
            'address_line' => null,
            'city' => null,
            'province' => null,
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('rooms.show', $room))
            ->assertOk();
    }

    public function test_guest_is_sent_to_login_and_booking_destination_is_preserved(): void
    {
        $status = RoomStatus::query()->firstOrCreate(['slug' => 'clean'], [
            'name' => 'Clean',
        ]);

        $room = Room::factory()->create([
            'room_status_id' => $status->room_status_id,
        ]);

        $bookingUrl = route('bookings.create', [
            'room' => $room,
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-12',
        ]);

        $this->get($bookingUrl)
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', $bookingUrl);
    }
}
