<?php

namespace Tests\Feature;

use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomAmenitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_room_has_common_and_type_specific_amenities(): void
    {
        $standard = Room::factory()->create(['type' => 'Standard']);
        $suite = Room::factory()->create(['type' => 'Suite']);

        $this->assertContains('Free Wi-Fi', array_column($standard->amenities, 'label'));
        $this->assertContains('Daily housekeeping', array_column($standard->amenities, 'label'));
        $this->assertContains('Dedicated workspace', array_column($suite->amenities, 'label'));
    }

    public function test_amenities_are_visible_on_room_search_and_details(): void
    {
        $room = Room::factory()->create(['type' => 'Deluxe']);

        $this->get(route('rooms.index'))
            ->assertSuccessful()
            ->assertSee('Free Wi-Fi')
            ->assertSee('Air conditioning');

        $this->get(route('rooms.show', $room))
            ->assertSuccessful()
            ->assertSee('Free Wi-Fi')
            ->assertSee('Private bathroom')
            ->assertSee('Premium bedding')
            ->assertSee('Rainfall shower');
    }
}
