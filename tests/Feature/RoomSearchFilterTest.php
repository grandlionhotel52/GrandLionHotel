<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_search_filters_by_type_or_view(): void
    {
        Room::factory()->create([
            'name' => 'Affordable Garden Suite',
            'type' => 'Suite',
            'view_type' => 'Garden View',
            'price_per_night' => 2500,
        ]);
        Room::factory()->create([
            'name' => 'Expensive Garden Suite',
            'type' => 'Suite',
            'view_type' => 'Garden View',
            'price_per_night' => 6500,
        ]);
        Room::factory()->create([
            'name' => 'Affordable Standard Room',
            'type' => 'Standard',
            'view_type' => 'Courtyard View',
            'price_per_night' => 2000,
        ]);

        $this->get(route('rooms.index', [
            'type' => 'Garden',
        ]))
            ->assertOk()
            ->assertSee('Affordable Garden Suite')
            ->assertSee('Expensive Garden Suite')
            ->assertDontSee('Affordable Standard Room');
    }

    public function test_room_search_dates_exclude_rooms_with_overlapping_bookings(): void
    {
        $availableRoom = Room::factory()->create(['name' => 'Available Date Room']);
        $bookedRoom = Room::factory()->create(['name' => 'Booked Date Room']);
        $checkIn = now()->addDays(10)->toDateString();
        $checkOut = now()->addDays(12)->toDateString();

        Booking::factory()->create([
            'room_id' => $bookedRoom->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => 'confirmed',
        ]);

        $this->get(route('rooms.index', [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ]))
            ->assertOk()
            ->assertSee($availableRoom->name)
            ->assertDontSee($bookedRoom->name);
    }

    public function test_room_search_does_not_render_a_filter_that_cannot_change_results(): void
    {
        $this->get(route('rooms.index'))
            ->assertOk()
            ->assertDontSee('name="available_only"', false)
            ->assertDontSee('name="max_price"', false)
            ->assertDontSee('name="sort"', false)
            ->assertSee('Only guest-ready rooms are shown.')
            ->assertDontSee('Apply filters');
    }
}
