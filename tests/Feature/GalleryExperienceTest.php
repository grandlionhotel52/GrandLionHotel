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
        $dirtyStatus = RoomStatus::query()->create([
            'name' => 'Dirty',
            'slug' => 'dirty',
            'description' => 'Room is awaiting cleaning.',
        ]);

        Room::factory()->create([
            'name' => 'Room Awaiting Cleaning',
            'room_status_id' => $dirtyStatus->room_status_id,
        ]);

        $this->get(route('gallery'))
            ->assertOk()
            ->assertDontSee('Room Awaiting Cleaning')
            ->assertSee('No rooms are available for gallery preview yet.');
    }
}
