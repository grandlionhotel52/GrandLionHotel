<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateActiveBookingCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_duplicate_active_bookings_are_removed_without_removing_distinct_stays(): void
    {
        $customer = Customer::factory()->create();
        $room = Room::factory()->create();
        $otherRoom = Room::factory()->create();
        $stay = [
            'customer_id' => $customer->customer_id,
            'room_id' => $room->room_id,
            'check_in' => '2026-09-30',
            'check_out' => '2026-10-02',
            'status' => 'confirmed',
        ];

        $keptBooking = Booking::factory()->create($stay);
        $duplicateBooking = Booking::factory()->create($stay);
        $distinctBooking = Booking::factory()->create(array_merge($stay, ['room_id' => $otherRoom->room_id]));

        $migration = require database_path('migrations/2026_09_30_000002_remove_duplicate_active_bookings.php');
        $migration->up();

        $this->assertDatabaseHas('bookings', ['booking_id' => $keptBooking->booking_id]);
        $this->assertDatabaseMissing('bookings', ['booking_id' => $duplicateBooking->booking_id]);
        $this->assertDatabaseHas('bookings', ['booking_id' => $distinctBooking->booking_id]);
        $this->assertSame(1, Booking::query()
            ->where('customer_id', $customer->customer_id)
            ->where('room_id', $room->room_id)
            ->whereDate('check_in', '2026-09-30')
            ->whereDate('check_out', '2026-10-02')
            ->where('status', 'confirmed')
            ->count());
    }
}
