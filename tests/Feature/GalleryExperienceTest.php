<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_displays_bookable_rooms_with_filters_and_accessible_previews(): void
    {
        $cleanStatus = RoomStatus::query()->where('slug', 'clean')->firstOrFail();

        $suite = Room::factory()->create([
            'name' => 'Sunset Suite',
            'type' => 'Suite',
            'price_per_night' => 5000,
            'capacity' => Room::standardGuestCapacity(),
            'room_status_id' => $cleanStatus->room_status_id,
        ]);
        Room::factory()->create([
            'name' => 'Garden Deluxe',
            'type' => 'Deluxe',
            'room_status_id' => $cleanStatus->room_status_id,
        ]);

        $this->get(route('gallery'))
            ->assertOk()
            ->assertSee('Sunset Suite')
            ->assertSee('Garden Deluxe')
            ->assertSee('data-gallery-filter="suite"', false)
            ->assertSee('data-gallery-filter="deluxe"', false)
            ->assertSee('loading="lazy"', false)
            ->assertSee('Standard occupancy: '.Room::standardGuestCapacity().' guests')
            ->assertSee('&#8369;5,000 / night', false)
            ->assertSee('data-gallery-preview', false)
            ->assertSee('https://images.unsplash.com/photo-', false)
            ->assertSee('View room', false)
            ->assertSee(route('rooms.show', $suite), false);
    }

    public function test_gallery_excludes_rooms_that_are_not_bookable(): void
    {
        $dirtyStatus = RoomStatus::query()->firstOrCreate(['slug' => 'dirty'], [
            'name' => 'Dirty',
            'description' => 'Room is awaiting cleaning.',
        ]);

        Room::factory()->create([
            'name' => 'Room Awaiting Cleaning',
            'room_status_id' => $dirtyStatus->room_status_id,
        ]);

        $this->get(route('gallery'))
            ->assertOk()
            ->assertDontSee('Room Awaiting Cleaning');
    }

    public function test_customer_room_pages_hide_dirty_and_makeup_rooms(): void
    {
        $dirtyStatus = RoomStatus::query()->where('slug', 'dirty')->firstOrFail();
        $makeupStatus = RoomStatus::query()->where('slug', 'being_cleaned')->firstOrFail();

        $dirtyRoom = Room::factory()->create([
            'name' => 'Hidden Dirty Room',
            'room_status_id' => $dirtyStatus->room_status_id,
        ]);
        $makeupRoom = Room::factory()->create([
            'name' => 'Hidden Makeup Room',
            'room_status_id' => $makeupStatus->room_status_id,
        ]);

        $this->get(route('rooms.index'))
            ->assertOk()
            ->assertDontSee('Hidden Dirty Room')
            ->assertDontSee('Hidden Makeup Room');

        $this->get(route('rooms.show', $dirtyRoom))->assertNotFound();
        $this->get(route('rooms.show', $makeupRoom))->assertNotFound();
    }
}
